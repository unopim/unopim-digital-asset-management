<x-admin::layouts.anonymous>
    <x-slot:title>
        @lang('dam::app.share.public.expired-title')
    </x-slot:title>

    @php
        $isRevoked = $share->isRevoked();
        $title = $isRevoked
            ? trans('dam::app.share.public.revoked-title')
            : trans('dam::app.share.public.expired-title');
        $message = $isRevoked
            ? trans('dam::app.share.public.revoked-message')
            : trans('dam::app.share.public.expired-message');
    @endphp

    <div class="min-h-screen bg-gray-50 dark:bg-cherry-950 flex items-center justify-center px-6">
        <div class="max-w-md w-full bg-white dark:bg-cherry-900 rounded-lg border border-gray-200 dark:border-cherry-800 p-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-4">
                <span class="icon-dam-link text-3xl text-amber-600 dark:text-amber-400"></span>
            </div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-slate-50 mb-2">
                {{ $title }}
            </h1>
            <p class="text-sm text-gray-600 dark:text-slate-300">
                {{ $message }}
            </p>
        </div>
    </div>
</x-admin::layouts.anonymous>
