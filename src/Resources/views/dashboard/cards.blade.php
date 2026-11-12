@inject('damDashboardStats', 'Webkul\DAM\Helpers\DashboardStats')

@if (bouncer()->hasPermission('dam'))
    @php
        $damTotals = $damDashboardStats->all()['totals'];

        $damCards = [
            [
                'label' => trans('dam::app.dashboard.total-assets'),
                'value' => number_format($damTotals['assets']),
                'key'   => 'assets',
                'url'   => route('admin.dam.index'),
            ],
            [
                'label' => trans('dam::app.dashboard.storage-used'),
                'value' => \Webkul\DAM\Helpers\DashboardStats::humanBytes($damTotals['storage']),
                'key'   => 'storage',
                'url'   => route('admin.dam.index'),
            ],
            [
                'label' => trans('dam::app.dashboard.total-directories'),
                'value' => number_format($damTotals['directories']),
                'key'   => 'directories',
                'url'   => route('admin.dam.index'),
            ],
            [
                'label' => trans('dam::app.dashboard.total-tags'),
                'value' => number_format($damTotals['tags']),
                'key'   => 'tags',
                'url'   => route('admin.dam.tags.index'),
            ],
        ];

        $damGlyphs = [
            'assets' => '<rect x="18" y="20" width="30" height="24" rx="3" class="fill-primary-100/70 stroke-primary-600" stroke-width="1.5"/><circle cx="26" cy="28" r="2.6" class="fill-none stroke-primary-600" stroke-width="1.5"/><path d="M20 42L30 32L36 38L41 34L46 39" class="fill-none stroke-primary-600" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
            'storage' => '<path d="M21 23C21 20.8 25.9 19 32 19C38.1 19 43 20.8 43 23V41C43 43.2 38.1 45 32 45C25.9 45 21 43.2 21 41V23Z" class="fill-primary-100/70 stroke-primary-600" stroke-width="1.5"/><path d="M21 23C21 25.2 25.9 27 32 27C38.1 27 43 25.2 43 23" class="fill-none stroke-primary-600" stroke-width="1.5"/><path d="M21 32C21 34.2 25.9 36 32 36C38.1 36 43 34.2 43 32" class="fill-none stroke-primary-600" stroke-width="1.5"/>',
            'directories' => '<path d="M19 25C19 23.3 20.3 22 22 22H28.2C28.9 22 29.6 22.3 30.1 22.8L32.5 25H44C45.7 25 47 26.3 47 28V41C47 42.7 45.7 44 44 44H22C20.3 44 19 42.7 19 41V25Z" class="fill-primary-100/70 stroke-primary-600" stroke-width="1.5" stroke-linejoin="round"/>',
            'tags' => '<path d="M44 20H32.5C31.7 20 30.9 20.3 30.4 20.9L19.9 31.4C18.7 32.6 18.7 34.5 19.9 35.6L30.4 46.1C31.6 47.3 33.5 47.3 34.6 46.1L45.1 35.6C45.7 35.1 46 34.3 46 33.5V22C46 20.9 45.1 20 44 20Z" class="fill-primary-100/70 stroke-primary-600" stroke-width="1.5" stroke-linejoin="round"/><circle cx="40" cy="26" r="2.3" class="fill-none stroke-primary-600" stroke-width="1.5"/>',
        ];
    @endphp

    <div class="flex items-center justify-between gap-3 mt-4 mb-4">
        <p class="text-base text-zinc-800 dark:text-slate-50 font-bold">
            @lang('dam::app.dashboard.overview')
        </p>
    </div>

    <div class="flex gap-4 flex-wrap mb-4">
        @foreach ($damCards as $card)
            <a
                href="{{ $card['url'] }}"
                class="flex gap-3 p-4 bg-white dark:bg-cherry-900 box-shadow rounded-lg flex-1 min-w-[200px] no-underline transition-all hover:shadow-md hover:-translate-y-0.5"
            >
                <svg class="shrink-0" width="60" height="60" viewBox="0 0 66 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0.306641 12.8C0.306641 5.73076 6.0374 0 13.1066 0H52.5833C59.6525 0 65.3833 5.73076 65.3833 12.8V51.2C65.3833 58.2692 59.6525 64 52.5833 64H13.1066C6.03739 64 0.306641 58.2692 0.306641 51.2V12.8Z" class="fill-primary-500/20"/>
                    {!! $damGlyphs[$card['key']] !!}
                </svg>

                <div class="grid gap-2.5 place-content-start">
                    <p class="text-sm text-zinc-600 dark:text-slate-300">
                        {{ $card['label'] }}
                    </p>
                    <p class="text-3xl text-zinc-800 leading-none dark:text-slate-50 font-semibold">
                        {{ $card['value'] }}
                    </p>
                </div>
            </a>
        @endforeach
    </div>
@endif
