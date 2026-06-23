{{--
    Shared "Assign Tags" mass-action modal.

    A single global Vue component reused by BOTH the legacy asset datagrid and the
    explorer. It is driven entirely through the emitter so callers stay decoupled:

      • open  →  emit  'dam:open-tag-assign-modal'  with { assetIds: [...], context }
      • done  →  it emits 'dam:tag-assign:done' with { context } after a successful assign
                 so the caller can refresh its own grid / explorer.

    Mount it once per page with <x-dam::tag.assign-modal />.
--}}
@pushOnce('scripts')
    <script type="text/x-template" id="v-dam-tag-assign-modal-template">
        <x-admin::modal ref="assignTagModal">
            <x-slot:header>
                <p class="text-lg text-gray-800 dark:text-white font-bold">
                    @lang('dam::app.admin.dam.tag.mass-action.modal-title')
                </p>
            </x-slot>

            <x-slot:content>
                <div class="flex flex-col gap-3">
                    <p class="text-sm text-gray-600 dark:text-slate-300">
                        @{{ subtitle }}
                    </p>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-300 mb-1">
                            @lang('dam::app.admin.dam.tag.mass-action.tags-label')
                        </label>

                        <v-multiselect
                            v-model="selectedTags"
                            :options="options"
                            :multiple="true"
                            :taggable="true"
                            :close-on-select="false"
                            :clear-on-select="false"
                            :hide-selected="true"
                            :show-labels="false"
                            :tag-placeholder="@js(trans('dam::app.admin.dam.tag.mass-action.add-tag'))"
                            :placeholder="@js(trans('dam::app.admin.dam.tag.mass-action.tags-placeholder'))"
                            @tag="addTag"
                        ></v-multiselect>
                    </div>

                    <div class="flex items-center justify-end gap-2 mt-2">
                        <button type="button" class="secondary-button" @click="close">
                            @lang('dam::app.admin.dam.tag.mass-action.cancel')
                        </button>

                        <button
                            type="button"
                            class="primary-button"
                            :disabled="isAssigning || !selectedTags.length"
                            @click="assign"
                        >
                            <span v-if="!isAssigning">@lang('dam::app.admin.dam.tag.mass-action.assign')</span>
                            <span v-else>@lang('dam::app.admin.dam.tag.mass-action.assigning')</span>
                        </button>
                    </div>
                </div>
            </x-slot>
        </x-admin::modal>
    </script>

    <script type="module">
        app.component('v-dam-tag-assign-modal', {
            template: '#v-dam-tag-assign-modal-template',

            data() {
                return {
                    assetIds: [],
                    context: null,
                    options: [],
                    selectedTags: [],
                    isAssigning: false,
                };
            },

            computed: {
                subtitle() {
                    return "@lang('dam::app.admin.dam.tag.mass-action.modal-subtitle')"
                        .replace(':count', this.assetIds.length);
                },
            },

            mounted() {
                this.$emitter.on('dam:open-tag-assign-modal', ({ assetIds, context }) => {
                    this.assetIds = Array.isArray(assetIds) ? assetIds.map(Number).filter(Boolean) : [];
                    this.context  = context ?? null;
                    this.selectedTags = [];

                    if (! this.assetIds.length) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: @js(trans('dam::app.admin.dam.tag.mass-action.no-assets')),
                        });
                        return;
                    }

                    this.loadTags();
                    this.$refs.assignTagModal.toggle();
                });
            },

            methods: {
                loadTags() {
                    this.$axios.get('{{ route('admin.dam.tags.list') }}')
                        .then(({ data }) => {
                            this.options = (data?.data ?? []).map(t => t.name);
                        })
                        .catch(() => {
                            this.options = [];
                        });
                },

                addTag(newTag) {
                    const value = (newTag || '').trim();
                    if (! value) return;

                    // Avoid duplicates (case-insensitive) in both the option list and the selection.
                    const lower = value.toLowerCase();
                    if (! this.options.some(o => o.toLowerCase() === lower)) {
                        this.options.push(value);
                    }
                    if (! this.selectedTags.some(t => t.toLowerCase() === lower)) {
                        this.selectedTags.push(value);
                    }
                },

                close() {
                    this.$refs.assignTagModal.toggle();
                },

                assign() {
                    if (this.isAssigning) return;

                    const tags = this.selectedTags.map(t => (t || '').trim()).filter(Boolean);

                    if (! tags.length) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: @js(trans('dam::app.admin.dam.tag.mass-action.no-tags')),
                        });
                        return;
                    }

                    this.isAssigning = true;

                    this.$axios.post('{{ route('admin.dam.assets.mass_assign_tags') }}', {
                            indices: this.assetIds,
                            tags,
                        })
                        .then(({ data }) => {
                            this.$emitter.emit('add-flash', { type: 'success', message: data.message });
                            this.$refs.assignTagModal.toggle();
                            this.$emitter.emit('dam:tag-assign:done', { context: this.context });
                        })
                        .catch(error => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error?.response?.data?.message
                                    ?? @js(trans('dam::app.admin.dam.tag.mass-action.assign-failed')),
                            });
                        })
                        .finally(() => {
                            this.isAssigning = false;
                        });
                },
            },
        });
    </script>
@endPushOnce

<v-dam-tag-assign-modal></v-dam-tag-assign-modal>
