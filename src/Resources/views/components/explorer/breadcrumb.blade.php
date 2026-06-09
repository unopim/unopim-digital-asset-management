@once('v-dam-explorer-breadcrumb')
@push('scripts')
<script type="text/x-template" id="v-dam-explorer-breadcrumb-template">
    <div class="flex items-center gap-2 flex-1 min-w-0">
        {{-- Back / Forward --}}
        <div class="flex items-center gap-0.5 shrink-0">
            <button
                type="button"
                class="w-7 h-7 flex items-center justify-center rounded-md transition-colors"
                :class="canGoBack ? 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer' : 'text-gray-300 dark:text-gray-600 cursor-not-allowed'"
                :disabled="!canGoBack"
                @click="$emit('back')"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <button
                type="button"
                class="w-7 h-7 flex items-center justify-center rounded-md transition-colors"
                :class="canGoForward ? 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer' : 'text-gray-300 dark:text-gray-600 cursor-not-allowed'"
                :disabled="!canGoForward"
                @click="$emit('forward')"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>

        {{-- Crumb trail --}}
        <nav class="flex items-center gap-1 text-sm flex-1 flex-wrap min-w-0">
            <template v-for="(crumb, i) in breadcrumbs" :key="crumb.id ?? i">
                <span v-if="i > 0" class="text-gray-300 dark:text-gray-600">/</span>
                <button
                    type="button"
                    class="px-1 py-0.5 rounded transition-colors max-w-[120px] truncate"
                    :class="i === breadcrumbs.length - 1
                        ? 'text-violet-700 dark:text-violet-300 font-semibold cursor-default'
                        : crumbDropTarget === crumb.id
                            ? 'text-violet-700 dark:text-violet-300 cursor-pointer bg-violet-100 dark:bg-violet-900/40 ring-1 ring-violet-400'
                            : 'text-gray-500 dark:text-gray-300 hover:text-violet-700 hover:underline cursor-pointer'"
                    @click="i < breadcrumbs.length - 1 ? $emit('navigate', crumb) : null"
                    @contextmenu.prevent.stop="showCrumbCtx($event, crumb)"
                    @dragover.prevent="onDragOver($event, crumb)"
                    @dragleave="onDragLeave($event, crumb)"
                    @drop.prevent="onDrop($event, crumb)"
                >@{{ crumb.name }}</button>
            </template>
        </nav>

        {{-- Crumb right-click context menu --}}
        <teleport to="body">
            <div
                v-if="crumbCtx.on"
                class="fixed bg-white dark:bg-cherry-900 border border-gray-200 dark:border-cherry-600 rounded-lg shadow-lg py-1"
                style="z-index:10001;"
                :style="{ left: crumbCtx.x + 'px', top: crumbCtx.y + 'px' }"
            >
                <button
                    type="button"
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-cherry-800 whitespace-nowrap"
                    @click="openCrumbInNewTab"
                >@lang('dam::app.admin.explorer.context.open-new-tab')</button>
            </div>
        </teleport>
    </div>
</script>

<script type="module">
app.component('v-dam-explorer-breadcrumb', {
    template: '#v-dam-explorer-breadcrumb-template',
    emits: ['back', 'forward', 'navigate', 'open-new-tab', 'drop'],

    props: {
        breadcrumbs:  { type: Array,   default: () => [] },
        canGoBack:    { type: Boolean, default: false },
        canGoForward: { type: Boolean, default: false },
        currentDirId: { type: Number,  default: null },
    },

    data() {
        return {
            crumbDropTarget: null,
            crumbCtx: { on: false, x: 0, y: 0, crumb: null },
            springTimer: null,
        };
    },

    methods: {
        showCrumbCtx(e, crumb) {
            this.crumbCtx = { on: true, x: e.clientX, y: e.clientY, crumb };
            const close = () => { this.crumbCtx = { on: false, x: 0, y: 0, crumb: null }; };
            document.addEventListener('click', close, { once: true });
        },

        openCrumbInNewTab() {
            const crumb = this.crumbCtx.crumb;
            this.crumbCtx = { on: false, x: 0, y: 0, crumb: null };
            if (crumb) this.$emit('open-new-tab', crumb);
        },

        onDragOver(e, crumb) {
            if (! e.dataTransfer.types.includes('application/json')) return;
            this.crumbDropTarget = crumb.id;
            if (crumb.id === this.currentDirId) return;
            if (this.springTimer?.id === crumb.id) return;
            clearTimeout(this.springTimer?.timerId);
            const timerId = setTimeout(() => {
                if (this.crumbDropTarget === crumb.id) {
                    this.$emit('navigate', crumb);
                    this.springTimer = null;
                }
            }, 700);
            this.springTimer = { id: crumb.id, timerId };
        },

        onDragLeave(e, crumb) {
            if (this.crumbDropTarget === crumb.id && ! e.currentTarget.contains(e.relatedTarget)) {
                this.crumbDropTarget = null;
                clearTimeout(this.springTimer?.timerId);
                this.springTimer = null;
            }
        },

        onDrop(e, crumb) {
            this.crumbDropTarget = null;
            clearTimeout(this.springTimer?.timerId);
            this.springTimer = null;
            if (e.dataTransfer?.types?.includes('Files')) return;
            let payload;
            try { payload = JSON.parse(e.dataTransfer.getData('application/json')); } catch { return; }
            if (! payload?.type) return;
            this.$emit('drop', { payload, targetDir: crumb });
        },
    },
});
</script>
@endpush
@endonce
