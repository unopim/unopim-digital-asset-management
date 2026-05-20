<x-admin::layouts.anonymous>
    <x-slot:title>
        @lang('dam::app.share.public.not-found-title')
    </x-slot:title>

    <div class="min-h-screen bg-gray-50 dark:bg-cherry-950 flex items-center justify-center px-6">
        <div class="max-w-md w-full bg-white dark:bg-cherry-900 rounded-lg border border-gray-200 dark:border-cherry-800 p-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
                <span class="icon-dam-link text-3xl text-red-600 dark:text-red-400"></span>
            </div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-slate-50 mb-2">
                @lang('dam::app.share.public.not-found-title')
            </h1>
            <p class="text-sm text-gray-600 dark:text-slate-300">
                @lang('dam::app.share.public.not-found-message')
            </p>
        </div>
    </div>
</x-admin::layouts.anonymous>
