<?php

declare(strict_types=1);

namespace Webkul\DAM\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\DamConfiguration;
use Webkul\DAM\Repositories\AssetTagRepository;
use Webkul\MagicAI\Contracts\MagicAIPlatform;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;

class AssetAutoTaggingService
{
    public function __construct(
        protected MagicAIPlatformRepository $platformRepository,
        protected AssetTagRepository $tagRepository,
    ) {}

    /**
     * Best-effort: swallows every failure so a broken AI call never fails the upload.
     */
    public function tagAsset(Asset $asset, string $disk): void
    {
        if (! $this->isEnabled() || $asset->file_type !== 'image') {
            return;
        }

        try {
            $platform = $this->resolvePlatform();

            if (! $platform) {
                return;
            }

            $dataUri = $this->readAsDataUri($asset, $disk);

            if (! $dataUri) {
                return;
            }

            $apiClient = app(AiApiClient::class);
            $apiClient->configure(new CredentialConfig(
                id: $platform->id,
                label: $platform->label,
                provider: $platform->provider,
                apiUrl: $platform->api_url ?: AiProvider::from($platform->provider)->defaultUrl(),
                apiKey: $platform->api_key,
                model: $platform->model_list[0] ?? '',
            ));

            $maxTags = $this->maxTags();

            $response = $apiClient->chat($this->buildTaggingMessages($dataUri, $maxTags), maxTokens: 512, temperature: 0.2);

            $tags = $this->parseTags((string) ($response['content'] ?? ''), $maxTags);

            if ($tags !== []) {
                $this->tagRepository->attachTagsByName($asset, $tags);
            }
        } catch (\Throwable $e) {
            Log::warning('DAM asset auto-tagging failed.', [
                'asset'   => $asset->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Configured platform id, else the active default; null when nothing
     * usable is resolvable so tagging can silently no-op.
     */
    protected function resolvePlatform(): ?MagicAIPlatform
    {
        $platformId = $this->platformId();

        $platform = $platformId
            ? $this->platformRepository->find($platformId)
            : $this->platformRepository->getActiveDefault();

        if (! $platform || ! $platform->status) {
            return null;
        }

        return AiProvider::from($platform->provider)->supportsImages() ? $platform : null;
    }

    /**
     * Reads DAM_AI_TAGGING_ENABLED straight from the DB. config('dam.ai_tagging.*')
     * is only populated by the DAM HTTP middleware, which a queue worker never runs.
     */
    protected function isEnabled(): bool
    {
        $value = DamConfiguration::find('DAM_AI_TAGGING_ENABLED')?->value;

        return $value === null
            ? (bool) config('dam.ai_tagging.enabled')
            : filter_var($value, FILTER_VALIDATE_BOOLEAN);
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

    protected function readAsDataUri(Asset $asset, string $disk): ?string
    {
        if (! Storage::disk($disk)->exists($asset->path)) {
            return null;
        }

        $raw = Storage::disk($disk)->get($asset->path);

        return 'data:'.($asset->mime_type ?: 'image/jpeg').';base64,'.base64_encode($raw);
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
