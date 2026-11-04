@inject('damDashboardStats', 'Webkul\DAM\Helpers\DashboardStats')

@if (bouncer()->hasPermission('dam'))
    @php
        $damStats   = $damDashboardStats->all();
        $damTotals  = $damStats['totals'];
        $damTypes   = $damStats['types'];
        $damRecent  = $damStats['recent'];
        $damMaxType = max(1, collect($damTypes)->max('count'));

        $damTypeMeta = [
            'image'    => ['label' => trans('dam::app.dashboard.types.image'),    'bar' => 'bg-emerald-500', 'dot' => 'bg-emerald-500'],
            'video'    => ['label' => trans('dam::app.dashboard.types.video'),    'bar' => 'bg-violet-500',  'dot' => 'bg-violet-500'],
            'audio'    => ['label' => trans('dam::app.dashboard.types.audio'),    'bar' => 'bg-amber-500',   'dot' => 'bg-amber-500'],
            'document' => ['label' => trans('dam::app.dashboard.types.document'), 'bar' => 'bg-sky-500',     'dot' => 'bg-sky-500'],
        ];
    @endphp

    <div class="flex items-center justify-between gap-3 mt-4 mb-4">
        <p class="text-base text-zinc-800 dark:text-slate-50 font-bold">
            @lang('dam::app.dashboard.asset-analytics')
        </p>
    </div>

    <div class="flex items-stretch gap-4 max-xl:flex-wrap mb-8">
        <!-- Assets by type -->
        <div class="flex flex-col flex-1 min-w-[300px] p-4 bg-white dark:bg-cherry-900 rounded-lg box-shadow">
            <p class="text-sm font-semibold text-zinc-800 dark:text-slate-50 mb-4">
                @lang('dam::app.dashboard.assets-by-type')
            </p>

            <div class="flex flex-col gap-3">
                @foreach ($damTypes as $type)
                    @php $meta = $damTypeMeta[$type['type']] ?? ['label' => ucfirst($type['type']), 'bar' => 'bg-zinc-400']; @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-zinc-600 dark:text-slate-300">{{ $meta['label'] }}</span>
                            <span class="font-semibold text-zinc-800 dark:text-slate-50">
                                {{ number_format($type['count']) }}
                                <span class="font-normal text-zinc-400 dark:text-slate-500">· {{ \Webkul\DAM\Helpers\DashboardStats::humanBytes($type['size']) }}</span>
                            </span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-zinc-100 dark:bg-cherry-800 overflow-hidden">
                            <div
                                class="h-full rounded-full {{ $meta['bar'] }}"
                                style="width: {{ $type['count'] > 0 ? max(4, round($type['count'] / $damMaxType * 100)) : 0 }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Recent uploads -->
        <div class="flex flex-col flex-1 min-w-[300px] p-4 bg-white dark:bg-cherry-900 rounded-lg box-shadow">
            <p class="text-sm font-semibold text-zinc-800 dark:text-slate-50 mb-4">
                @lang('dam::app.dashboard.recent-uploads')
            </p>

            @if (empty($damRecent))
                <p class="text-sm text-zinc-400 dark:text-slate-500 py-6 text-center">
                    @lang('dam::app.dashboard.no-assets')
                </p>
            @else
                <div class="flex flex-col divide-y divide-zinc-100 dark:divide-cherry-800">
                    @foreach ($damRecent as $asset)
                        @php $meta = $damTypeMeta[$asset->file_type] ?? ['dot' => 'bg-zinc-400']; @endphp
                        <div class="flex items-center gap-3 py-2.5">
                            <span class="w-2 h-2 rounded-full shrink-0 {{ $meta['dot'] ?? 'bg-zinc-400' }}"></span>
                            <span class="flex-1 truncate text-sm text-zinc-700 dark:text-slate-200" title="{{ $asset->file_name }}">
                                {{ $asset->file_name }}
                            </span>
                            <span class="text-xs text-zinc-400 dark:text-slate-500 shrink-0">
                                {{ \Webkul\DAM\Helpers\DashboardStats::humanBytes((int) $asset->file_size) }}
                            </span>
                            <span class="text-xs text-zinc-400 dark:text-slate-500 shrink-0 w-20 text-right">
                                {{ \Illuminate\Support\Carbon::parse($asset->created_at)->diffForHumans(null, true) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif
