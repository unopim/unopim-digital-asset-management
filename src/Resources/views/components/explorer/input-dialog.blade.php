@once('v-dam-input-dialog')
@push('scripts')
<script type="text/x-template" id="v-dam-input-dialog-template">
    <teleport to="body">
        <div
            v-if="isOpen"
            class="fixed inset-0 flex items-center justify-center bg-gray-500 bg-opacity-50"
            style="z-index:10000;"
            @click.self="$emit('close')"
        >
            <div class="bg-white dark:bg-cherry-900 rounded-xl border border-gray-200 dark:border-cherry-600 w-full mx-4 p-6" style="max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.25);">
                <h3 class="text-base font-bold text-gray-800 dark:text-white mb-4">@{{ title }}</h3>
                <input
                    ref="input"
                    v-model="localValue"
                    type="text"
                    class="w-full border border-gray-300 dark:border-cherry-600 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 bg-white dark:bg-cherry-800 outline-none mb-4"
                    :placeholder="placeholder"
                    @keydown.enter.prevent="submit"
                    @keydown.escape="$emit('close')"
                />
                <div class="flex gap-2 justify-end">
                    <button type="button" class="secondary-button" @click="$emit('close')">
                        @lang('dam::app.admin.explorer.dialog.cancel')
                    </button>
                    <button
                        type="button"
                        class="primary-button"
                        :disabled="isLoading || !localValue.trim()"
                        @click="submit"
                    >
                        <svg v-if="isLoading" class="animate-spin inline-block h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        @lang('dam::app.admin.explorer.dialog.save')
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</script>

<script type="module">
app.component('v-dam-input-dialog', {
    template: '#v-dam-input-dialog-template',
    emits: ['submit', 'close'],

    props: {
        isOpen:       { type: Boolean, default: false },
        title:        { type: String,  default: '' },
        placeholder:  { type: String,  default: '' },
        initialValue: { type: String,  default: '' },
        isLoading:    { type: Boolean, default: false },
    },

    data() {
        return { localValue: this.initialValue };
    },

    watch: {
        isOpen(val) {
            if (val) {
                this.localValue = this.initialValue;
                this.$nextTick(() => this.$refs.input?.focus());
            }
        },
        initialValue(val) {
            if (this.isOpen) this.localValue = val;
        },
    },

    methods: {
        submit() {
            const v = this.localValue.trim();
            if (! v || this.isLoading) return;
            this.$emit('submit', v);
        },
    },
});
</script>
@endpush
@endonce
