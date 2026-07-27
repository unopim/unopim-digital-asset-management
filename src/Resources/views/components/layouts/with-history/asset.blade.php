@props(['returnDirectoryId' => null])
@php
    $darkModePreference = request()->cookie('dark_mode', 'auto');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar_AE']) ? 'rtl' : 'ltr' }}" class="{{ $darkModePreference === 'dark' || $darkModePreference === '1' ? 'dark' : '' }}">
    <head>

        {!! view_render_event('unopim.admin.layout.head.before') !!}
        {!! view_render_event('unopim.admin.layout.head') !!}

        <title>{{ $title ?? '' }}</title>

        <meta charset="UTF-8">

        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="base-url" content="{{ url()->to('/') }}">
        <meta name="admin-url" content="{{ config('app.admin_url') }}">
        <meta name="currency-code" content="{{ core()->getBaseCurrencyCode() }}">
        <meta http-equiv="content-language" content="{{ app()->getLocale() }}">
        <script>
            (() => {
                const getCookie = (name) => {
                    const value = `; ${document.cookie}`;
                    const parts = value.split(`; ${name}=`);

                    return parts.length === 2 ? parts.pop().split(';').shift() : null;
                };

                const preference = getCookie('dark_mode') || 'auto';
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                const shouldUseDark = preference === 'dark' || preference === '1' || (preference === 'auto' && prefersDark);

                document.documentElement.classList.toggle('dark', shouldUseDark);
            })();
        </script>

        @stack('meta')

        @unoPimVite(['src/Resources/assets/css/app.css', 'src/Resources/assets/js/app.js'], 'admin')

        <link
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
            rel="stylesheet"
        />

        <link
            href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&display=swap"
            rel="stylesheet"
        />

        <link
            href="https://fonts.googleapis.com/css2?family=Inter&display=swap"
            rel="stylesheet"
        />

        @if ($favicon = core()->getConfigData('general.design.admin_logo.favicon'))
            <link
                type="image/x-icon"
                href="{{ Storage::url($favicon) }}"
                rel="shortcut icon"
                sizes="16x16"
            >
        @else
            <link
                type="image/x-icon"
                href="{{ unopim_asset('images/favicon.svg') }}"
                rel="shortcut icon"
                sizes="16x16"
            />
        @endif

        @stack('styles')

        <style>
            {!! core()->getConfigData('general.content.custom_scripts.custom_css') !!}
        </style>

        {!! view_render_event('unopim.admin.layout.head.after') !!}
    </head>

    <body class="h-full dark:bg-cherry-800">
        {!! view_render_event('unopim.admin.layout.body.before') !!}

        <div id="app" class="h-screen flex flex-col">
            <x-admin::flash-group />

            <x-admin::modal.history />

            <x-admin::modal.confirm />

            {!! view_render_event('unopim.admin.layout.content.before') !!}

            <x-admin::layouts.header />

            <div
                class="flex flex-1 min-h-0 overflow-hidden group/container {{ (request()->cookie('sidebar_collapsed') ?? 0) ? 'sidebar-collapsed' : 'sidebar-not-collapsed' }}"
                ref="appLayout"
            >
                <x-admin::layouts.sidebar />

                <div class="flex-1 min-w-0 overflow-y-auto overflow-x-hidden px-4 pt-3 pb-6 bg-transparent dark:bg-cherry-800 max-lg:!px-4 transition-all duration-300">
                    {!! view_render_event('unopim.admin.layouts.tabs.before') !!}

                    <div class="flex flex-wrap justify-between gap-2 items-center">
                        <div class="flex min-w-0">

                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="{{ route('admin.dam.index') }}" class="transparent-button">
                                    <i class="icon-left text-xl -mt-px" aria-hidden="true"></i>
                                    @lang('dam::app.admin.dam.asset.edit.back')
                                </a>

                                @isset($breadcrumb){{ $breadcrumb }}@endisset

                                @isset($fileIcon){{ $fileIcon }}@endisset

                                <v-dam-asset-label initial-label="{{ $label ?? '' }}"></v-dam-asset-label>

                                @isset($counter)
                                {{ $counter }}
                                @endisset
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 items-center">
                            {{ $buttonOne }}
                            {{ $buttonTwo }}
                            {{ $buttonThree }}
                            {{ $buttonFour }}
                            {{ $buttonFive ?? '' }}
                        </div>
                    </div>

                    <v-asset-lock-zone>
                    <div class="tabs">
                        @php

                            $defaultTabs = [
                                [
                                    'url'    => '?',
                                    'code'   => 'general',
                                    'name'   => 'admin::app.components.layouts.sidebar.general',
                                    'icon'   => 'icon-general'
                                ], [
                                    'url'    => '?history',
                                    'code'   => 'history',
                                    'name'   => 'admin::app.components.layouts.sidebar.history',
                                    'icon'   => 'icon-history'
                                ],
                            ];

                            $items = isset($addTabs) ? optional($addTabs->attributes)->getAttributes()['items'] : $defaultTabs;

                            $queryString = request()->getQueryString();

                            $queryString = rtrim($queryString, '=');

                            $activeTab = collect($items)->firstWhere('code', $queryString)['code'] ?? $items[0]['code'];

                        @endphp

                        <div class="flex flex-wrap gap-4 my-4 border-b-2 max-sm:hidden dark:border-gray-800 items-center">
                            @foreach ($items as $key => $item)
                                <a href="{{ $item['url'] }}" class="self-stretch flex items-end">
                                    <div class="{{  $item['code'] === $activeTab ? "-mb-px border-violet-700  border-b-2 transition" : '' }} pb-3.5 px-2.5 text-base  font-medium text-gray-600 dark:text-gray-300 cursor-pointer flex items-center gap-2 justify-center">
                                        <span class="text-xl {{ $item['icon'] }}"></span>
                                        @lang($item['name'])
                                        @if (array_key_exists('badge', $item))
                                            <v-dam-tab-badge tab-code="{{ $item['code'] }}" :initial-count="{{ (int)($item['badge'] ?? 0) }}"></v-dam-tab-badge>
                                        @endif
                                    </div>
                                </a>
                            @endforeach

                            @isset($navButtons)
                            <div class="ml-auto flex gap-2 items-center">
                                {{ $navButtons }}
                            </div>
                            @endisset
                        </div>

                    </div>

                    @if ($activeTab === 'history')
                        {!! view_render_event('unopim.settings.channels.list.before') !!}

                        <x-admin::history src="{{ route('admin.history.index',[$entityName, request()->id]) }}" >
                        </x-admin::history>

                        {!! view_render_event('unopim.settings.channels.list.after') !!}

                    @elseif ($activeTab === 'preview')
                        {!! view_render_event('unopim.settings.slot.content.before') !!}

                        {{ $slot }}

                        {!! view_render_event('unopim.settings.slot.content.after') !!}

                    @elseif ($activeTab === 'properties')
                        {{$properties}}

                    @elseif ($activeTab === 'comments')
                        {{$comments}}

                    @elseif ($activeTab === 'linked-resources')
                        {{$linked_resources}}

                    @elseif ($activeTab === 'meta-data')
                        {{$meta_data}}
                    @endif
                    </v-asset-lock-zone>

                    {!! view_render_event('unopim.admin.layouts.tabs.after') !!}
                </div>
            </div>

            {!! view_render_event('unopim.admin.layout.content.after') !!}
        </div>

        {!! view_render_event('unopim.admin.layout.body.after') !!}

        @pushOnce('scripts')
            <script
                type="text/x-template"
                id="v-asset-lock-zone-template"
            >
                <div
                    :class="{ 'cursor-not-allowed': isLocked }"
                    :aria-busy="isLocked"
                >
                    <div :class="{ 'opacity-60 pointer-events-none': isLocked }">
                        <slot></slot>
                    </div>
                </div>
            </script>

            <script type="module">
                app.component('v-asset-lock-zone', {
                    template: '#v-asset-lock-zone-template',
                    data() {
                        return {
                            isLocked: false,
                            onLockChange: null,
                        };
                    },
                    mounted() {
                        this.onLockChange = (locked) => { this.isLocked = !!locked; };
                        this.$emitter.on('dam-asset-action-locked', this.onLockChange);
                    },
                    unmounted() {
                        if (this.onLockChange) {
                            this.$emitter.off('dam-asset-action-locked', this.onLockChange);
                        }
                    },
                });
            </script>

            <script type="module">
                app.component('v-dam-asset-label', {
                    props: {
                        initialLabel: { type: String, default: '' },
                    },
                    template: `<span class="text-base text-gray-600 dark:text-gray-300 font-bold min-w-0 break-all">@{{ label }}</span>`,
                    data() {
                        return { label: this.initialLabel };
                    },
                    mounted() {
                        this._onAssetChange = (data) => {
                            if (data.asset?.file_name) {
                                this.label = data.asset.file_name;
                            }
                        };
                        this.$emitter.on('dam-asset-changed', this._onAssetChange);
                    },
                    beforeUnmount() {
                        if (this._onAssetChange) {
                            this.$emitter.off('dam-asset-changed', this._onAssetChange);
                        }
                    },
                });
            </script>
        @endPushOnce

        @stack('scripts')

        @if ($returnDirectoryId)
        <script>
            try { sessionStorage.setItem('dam_return_dir', '{{ (int) $returnDirectoryId }}'); } catch {}
        </script>
        @endif

        {!! view_render_event('unopim.admin.layout.vue-app-mount.before') !!}

        {!! view_render_event('bagisto.admin.layout.vue-app-mount.after') !!}
    </body>
</html>