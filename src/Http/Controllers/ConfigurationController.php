<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\DAM\Models\DamConfiguration;

class ConfigurationController extends Controller
{
    public function index(): View
    {
        if (! bouncer()->hasPermission('dam.configuration.index')) {
            abort(403);
        }

        return view('dam::configuration.index', [
            'settings' => [
                'DAM_TREE_SHOW_ASSETS'            => config('dam.tree.show_assets'),
                'DAM_EXPLORER_ENABLED'            => config('dam.explorer.enabled'),
                'DAM_EXPLORER_BOOKMARKS_ENABLED'  => config('dam.explorer.bookmarks_enabled'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if (! bouncer()->hasPermission('dam.configuration.update')) {
            abort(403);
        }

        $keys = ['DAM_TREE_SHOW_ASSETS', 'DAM_EXPLORER_ENABLED', 'DAM_EXPLORER_BOOKMARKS_ENABLED'];

        foreach ($keys as $key) {
            DamConfiguration::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0']
            );
        }

        \Artisan::call('config:clear');
        \Artisan::call('route:clear');

        return redirect()->route('admin.dam.configuration.index')
            ->with('success', trans('dam::app.admin.configuration.saved'));
    }
}
