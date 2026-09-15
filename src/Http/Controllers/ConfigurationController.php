<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Webkul\DAM\Models\DamConfiguration;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;

class ConfigurationController extends Controller
{
    public function __construct(
        protected MagicAIPlatformRepository $platformRepository,
    ) {}

    public function index(): View
    {
        if (! bouncer()->hasPermission('dam.configuration.index')) {
            abort(403);
        }

        $platforms = $this->visionCapablePlatforms();

        return view('dam::configuration.index', [
            'settings' => [
                'DAM_TREE_SHOW_ASSETS'            => config('dam.tree.show_assets'),
                'DAM_EXPLORER_ENABLED'            => config('dam.explorer.enabled'),
                'DAM_EXPLORER_BOOKMARKS_ENABLED'  => config('dam.explorer.bookmarks_enabled'),
                'DAM_EXPLORER_SHOW_TREE'          => config('dam.explorer.show_tree'),
                'DAM_AI_TAGGING_ENABLED'          => config('dam.ai_tagging.enabled'),
                'DAM_AI_TAGGING_PLATFORM_ID'      => config('dam.ai_tagging.platform_id'),
                'DAM_AI_TAGGING_MAX_TAGS'         => config('dam.ai_tagging.max_tags', 8),
                'DAM_AI_TAGGING_RATE_LIMIT'       => config('dam.ai_tagging.rate_limit_per_minute', 60),
            ],
            'hasAiTaggingPlatforms'     => $platforms->isNotEmpty(),
            'selectedAiTaggingPlatform' => $platforms->firstWhere('id', (int) config('dam.ai_tagging.platform_id')),
        ]);
    }

    /**
     * Paginated option source for the async platform select, in the shape
     * the shared v-async-select-handler component expects.
     */
    public function aiTaggingPlatforms(Request $request): JsonResponse
    {
        if (! bouncer()->hasPermission('dam.configuration.index')) {
            abort(403);
        }

        $query = mb_strtolower((string) $request->query('query', ''));

        $options = $this->visionCapablePlatforms()
            ->when($query !== '', fn ($platforms) => $platforms->filter(
                fn (array $platform) => str_contains(mb_strtolower($platform['label']), $query)
            ))
            ->map(fn (array $platform) => [
                'id'    => (string) $platform['id'],
                'label' => $platform['label'].($platform['is_default'] ? ' *' : ''),
            ])
            ->values();

        return new JsonResponse([
            'options'  => $options->all(),
            'page'     => 1,
            'lastPage' => 1,
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function visionCapablePlatforms()
    {
        return collect($this->platformRepository->getActivePlatformOptions())
            ->filter(fn (array $platform) => AiProvider::from($platform['provider'])->supportsImages())
            ->values();
    }

    public function update(Request $request): RedirectResponse
    {
        if (! bouncer()->hasPermission('dam.configuration.update')) {
            abort(403);
        }

        $request->validate([
            'DAM_AI_TAGGING_PLATFORM_ID' => 'nullable|integer|exists:magic_ai_platforms,id',
            'DAM_AI_TAGGING_MAX_TAGS'    => 'nullable|integer|min:1|max:20',
            'DAM_AI_TAGGING_RATE_LIMIT'  => 'nullable|integer|min:1|max:120',
        ]);

        $keys = ['DAM_TREE_SHOW_ASSETS', 'DAM_EXPLORER_ENABLED', 'DAM_EXPLORER_BOOKMARKS_ENABLED', 'DAM_EXPLORER_SHOW_TREE', 'DAM_AI_TAGGING_ENABLED'];

        foreach ($keys as $key) {
            DamConfiguration::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0']
            );
        }

        DamConfiguration::updateOrCreate(
            ['key' => 'DAM_AI_TAGGING_PLATFORM_ID'],
            ['value' => (string) $request->input('DAM_AI_TAGGING_PLATFORM_ID', '')]
        );

        DamConfiguration::updateOrCreate(
            ['key' => 'DAM_AI_TAGGING_MAX_TAGS'],
            ['value' => (string) $request->input('DAM_AI_TAGGING_MAX_TAGS', '')]
        );

        DamConfiguration::updateOrCreate(
            ['key' => 'DAM_AI_TAGGING_RATE_LIMIT'],
            ['value' => (string) $request->input('DAM_AI_TAGGING_RATE_LIMIT', '')]
        );

        \Artisan::call('config:clear');
        \Artisan::call('route:clear');

        return redirect()->route('admin.dam.configuration.index')
            ->with('success', trans('dam::app.admin.configuration.saved'));
    }
}
