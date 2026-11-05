<?php

declare(strict_types=1);

namespace Webkul\DAM\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\DamConfiguration;
use Webkul\DAM\Repositories\AssetTagRepository;
use Webkul\MagicAI\Contracts\MagicAIPlatform;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Support\ModelRecommender;

class AssetAutoTaggingService
{
    protected ?string $lastError = null;

    public function __construct(
        protected MagicAIPlatformRepository $platformRepository,
        protected AssetTagRepository $tagRepository,
    ) {}

    /**
     * Message from the most recent failed tagAsset() call.
     */
    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Swallows every failure; returns false only on a genuine error, not when disabled or no tags were suggested.
     */
    public function tagAsset(Asset $asset, string $disk): bool
    {
        if (! $this->isEnabled() || $asset->file_type !== 'image') {
            return true;
        }

        try {
            $platform = $this->resolvePlatform();

            if (! $platform) {
                return true;
            }

            $dataUri = $this->readAsDataUri($asset, $disk);

            if (! $dataUri) {
                return true;
            }

            $model = $this->modelFor($platform);

            $apiClient = app(AiApiClient::class);
            $apiClient->configure(new CredentialConfig(
                id: $platform->id,
                label: $platform->label,
                provider: $platform->provider,
                apiUrl: $platform->api_url ?: AiProvider::from($platform->provider)->defaultUrl(),
                apiKey: $platform->api_key,
                model: $model,
            ));

            $maxTags = $this->maxTags();

            $response = $apiClient->chat($this->buildTaggingMessages($dataUri, $maxTags), maxTokens: 512, temperature: $this->temperatureFor($model));

            $tags = $this->parseTags((string) ($response['content'] ?? ''), $maxTags);

            if ($tags !== []) {
                $this->tagRepository->attachTagsByName($asset, $tags);
            }

            return true;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();

            Log::warning('DAM asset auto-tagging failed.', [
                'asset'   => $asset->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Configured platform, else the active default; null lets tagging no-op.
     */
    protected function resolvePlatform(): ?MagicAIPlatform
    {
        return $this->platformFor($this->platformId());
    }

    /**
     * The given platform, or the active default when none is given, provided it is
     * active, accepts images and has at least one model that can tag.
     */
    public function platformFor(?int $platformId): ?MagicAIPlatform
    {
        $platform = $platformId
            ? $this->platformRepository->find($platformId)
            : $this->platformRepository->getActiveDefault();

        if (! $platform || ! $platform->status || ! AiProvider::from($platform->provider)->supportsImages()) {
            return null;
        }

        return $this->taggingModels($platform) === [] ? null : $platform;
    }

    /**
     * The platform's vision-capable chat models, in its own order.
     *
     * @return list<string>
     */
    public function taggingModels(MagicAIPlatform $platform): array
    {
        $models = array_values(array_filter($platform->model_list, fn (string $model): bool => $model !== ''));

        $chatModels = array_intersect(ModelRecommender::chatCapable($models), ModelRecommender::textModels($models));

        $patterns = (array) config('dam.ai_tagging.vision_model_patterns', []);

        return array_values(array_filter(
            $models,
            fn (string $model): bool => in_array($model, $chatModels, true)
                && array_any($patterns, fn (string $pattern): bool => preg_match($pattern, $model) === 1),
        ));
    }

    /**
     * The saved model while the platform still offers it for tagging, else the
     * platform's first compatible model.
     */
    protected function modelFor(MagicAIPlatform $platform): string
    {
        $models = $this->taggingModels($platform);
        $saved = $this->savedModel();

        return $saved !== null && in_array($saved, $models, true) ? $saved : ($models[0] ?? '');
    }

    protected function savedModel(): ?string
    {
        $value = DamConfiguration::find('DAM_AI_TAGGING_MODEL')?->value ?? config('dam.ai_tagging.model');

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Reads the DB directly, because queue workers never run the middleware that fills config('dam.ai_tagging.*').
     */
    public function isEnabled(): bool
    {
        $value = DamConfiguration::find('DAM_AI_TAGGING_ENABLED')?->value;

        return $value === null
            ? (bool) config('dam.ai_tagging.enabled')
            : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Whether a tagging request could actually reach an AI platform right now:
     * the feature is on and an active, image-capable platform resolves.
     */
    public function isAvailable(): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        try {
            return $this->resolvePlatform() !== null;
        } catch (\ValueError) {
            return false;
        }
    }

    /**
     * Adds a pending claim; the asset stays pending until every claim is released, bounded by the TTL.
     *
     * @return string the claim token for clearPending()
     */
    public function markPending(int $assetId): string
    {
        $key = $this->pendingCacheKey($assetId);

        Cache::add($key, 0, now()->addHour());

        $count = Cache::increment($key);

        if ($count === false || $count < 1) {
            Cache::put($key, 1, now()->addHour());
        }

        return (string) Str::uuid();
    }

    /**
     * Releases one claim; a token is released at most once so a retried job cannot drop another job's claim.
     */
    public function clearPending(int $assetId, ?string $claim = null): void
    {
        if ($claim !== null && ! Cache::add("dam.ai_tagging.released.{$claim}", true, now()->addHour())) {
            return;
        }

        $key = $this->pendingCacheKey($assetId);

        $count = Cache::decrement($key);

        if ($count !== false && $count < 0) {
            Cache::put($key, 0, now()->addHour());
        }
    }

    public function isPending(Asset $asset): bool
    {
        return $asset->file_type === 'image'
            && (int) Cache::get($this->pendingCacheKey($asset->id), 0) > 0
            && $this->isEnabled();
    }

    protected function pendingCacheKey(int $assetId): string
    {
        return "dam.ai_tagging.pending.{$assetId}";
    }

    protected function platformId(): ?int
    {
        $value = DamConfiguration::find('DAM_AI_TAGGING_PLATFORM_ID')?->value;

        if ($value === null || $value === '') {
            return config('dam.ai_tagging.platform_id') ? (int) config('dam.ai_tagging.platform_id') : null;
        }

        return (int) $value;
    }

    protected function maxTags(): int
    {
        $value = DamConfiguration::find('DAM_AI_TAGGING_MAX_TAGS')?->value;

        if ($value === null || $value === '') {
            return (int) config('dam.ai_tagging.max_tags', 8);
        }

        return max(1, min(20, (int) $value));
    }

    /**
     * Skips oversized images before reading, since base64 needs the whole binary resident.
     */
    protected function readAsDataUri(Asset $asset, string $disk): ?string
    {
        $storage = Storage::disk($disk);

        if (! $storage->exists($asset->path)) {
            return null;
        }

        $maxFileSize = $this->maxFileSize();

        if ($maxFileSize > 0 && $storage->size($asset->path) > $maxFileSize) {
            Log::info('DAM asset skipped for auto-tagging: larger than the configured limit.', [
                'asset' => $asset->id,
                'limit' => $maxFileSize,
            ]);

            return null;
        }

        $raw = $storage->get($asset->path);

        return 'data:'.($asset->mime_type ?: 'image/jpeg').';base64,'.base64_encode($raw);
    }

    /**
     * OpenAI reasoning models reject any temperature but 1; a provider prefix such as "openai/" is ignored.
     */
    protected function temperatureFor(string $model): float
    {
        return preg_match('/^(?:.*\/)?(chat-latest|o[1-9]|gpt-([5-9]|\d{2,}))/i', $model) ? 1.0 : 0.2;
    }

    /**
     * Bytes; 0 or less disables the cap.
     */
    protected function maxFileSize(): int
    {
        return (int) config('dam.ai_tagging.max_file_size', 10 * 1024 * 1024);
    }

    /**
     * @return array<int, array{role: string, content: mixed}>
     */
    protected function buildTaggingMessages(string $dataUri, int $maxTags): array
    {
        return [
            [
                'role'    => 'system',
                'content' => "Return a JSON object {\"tags\": string[]} with at most {$maxTags} short, lowercase tags describing this image. No markdown fences, no generic filler tags.",
            ], [
                'role'    => 'user',
                'content' => [
                    ['type' => 'text', 'text' => 'Suggest tags for this image.'],
                    ['type' => 'image_url', 'image_url' => ['url' => $dataUri]],
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    protected function parseTags(string $raw, int $maxTags): array
    {
        $clean = trim((string) preg_replace('/^```(?:json)?|```$/m', '', trim($raw)));
        $decoded = json_decode($clean, true);

        $list = match (true) {
            is_array($decoded) && isset($decoded['tags']) && is_array($decoded['tags']) => $decoded['tags'],
            is_array($decoded) && array_is_list($decoded)                               => $decoded,
            default                                                                     => [],
        };

        return collect($list)
            ->map(fn ($tag) => mb_strtolower(trim((string) $tag)))
            ->filter(fn ($tag) => $tag !== '' && mb_strlen($tag) <= 40)
            ->unique()
            ->values()
            ->take($maxTags)
            ->all();
    }
}
