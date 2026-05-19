<v-comments>
    <v-comment-box></v-comment-box>
</v-comments>

{!! view_render_event('dam.admin.dam.asset.comments.create.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-comments-template">
        <div class="flex flex-col flex-1 overflow-auto bg-white dark:bg-cherry-900 rounded-lg box-shadow">
            <div class="flex flex-col gap-6 w-full max-w-4xl mx-auto h-[calc(100vh-180px)] p-6">
                <v-comment-box @comment-added="onCommentPosted" />

                @if (bouncer()->hasPermission('dam.asset.comment.index'))
                    <div v-if="comments?.length" class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                                @lang('dam::app.admin.dam.asset.edit.tab.comments') (@{{ totalCount }})
                            </h3>
                        </div>

                        <div
                            v-for="(comment, index) in comments"
                            :key="comment.id"
                            class="flex flex-col gap-4 border border-gray-200 dark:border-cherry-800 rounded-lg p-4">
                            <v-comment-panel
                                :comment="comment"
                                :current-admin-id="currentAdminId"
                                @updated="onCommentUpdated(comment, $event)"
                                @deleted="onCommentDeleted(index)" />

                            <div
                                v-for="(subComment, subIndex) in comment.children"
                                :key="subComment.id"
                                class="ml-12 pl-4 border-l-2 border-gray-200 dark:border-cherry-800">
                                <v-comment-panel
                                    :comment="subComment"
                                    :current-admin-id="currentAdminId"
                                    @updated="onReplyUpdated(comment, subIndex, $event)"
                                    @deleted="onReplyDeleted(comment, subIndex)" />
                            </div>

                            <div class="ml-12">
                                <button
                                    v-if="!openReplies[comment.id]"
                                    type="button"
                                    class="inline-flex items-center gap-1 text-sm font-medium text-violet-600 dark:text-violet-400 hover:underline"
                                    @click="toggleReply(comment.id)">
                                    <span class="icon-reply"></span>
                                    @lang('dam::app.admin.dam.asset.comments.reply')
                                </button>

                                <v-comment-box
                                    v-else
                                    :parent-id="comment.id"
                                    show-cancel
                                    @comment-added="onReplyPosted(comment, $event)"
                                    @cancel="toggleReply(comment.id)" />
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex flex-col px-4 py-4 justify-center gap-2 text-center items-center text-xl text-zinc-800 dark:text-slate-50 font-bold leading-normal m-auto">
                        <svg class="text-violet-600 dark:text-violet-400" width="96" height="97" viewBox="0 0 96 97" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M50.0009 12.185C46.4387 12.1301 42.8758 12.2196 39.3209 12.453C22.5849 13.565 9.25687 27.081 8.16087 44.053C7.94898 47.4096 7.94898 50.7763 8.16087 54.133C8.56087 60.313 11.2929 66.037 14.5129 70.869C16.3809 74.249 15.1489 78.469 13.2009 82.161C11.8009 84.821 11.0969 86.149 11.6609 87.109C12.2209 88.069 13.4809 88.101 15.9969 88.161C20.9769 88.281 24.3329 86.873 26.9969 84.909C28.5049 83.793 29.2609 83.237 29.7809 83.173C30.3009 83.109 31.3289 83.533 33.3769 84.373C35.2169 85.133 37.3569 85.601 39.3169 85.733C45.0169 86.109 50.9729 86.109 56.6849 85.733C73.4169 84.621 86.7449 71.101 87.8409 54.133C88.0089 51.513 88.0449 48.821 87.9489 46.169M64.0009 8.16898L88.0009 32.169M64.0009 32.169L88.0009 8.16898M34.0009 60.169H62.0009M34.0009 40.169H48.0009" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <p>@lang('dam::app.admin.dam.asset.comments.no-comments')</p>
                    </div>
                @endif
            </div>
        </div>
    </script>

    <script
        type="text/x-template"
        id="v-comment-box-template">
        @if (bouncer()->hasPermission('dam.asset.comment.store'))
            <div class="flex flex-col gap-2 w-full">
                <label
                    v-if="parentId === null"
                    class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    @lang('dam::app.admin.dam.asset.comments.add-comment')
                </label>

                <textarea
                    ref="textarea"
                    v-model="commentText"
                    class="w-full border border-gray-300 dark:border-cherry-800 bg-white dark:bg-cherry-900 rounded-lg p-3 text-sm text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-violet-500 resize-y"
                    rows="3"
                    maxlength="1000"
                    :placeholder="placeholderLabel"
                    @keydown.meta.enter.prevent="submit"
                    @keydown.ctrl.enter.prevent="submit"
                    @keydown.esc.prevent="onEsc"></textarea>

                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        @{{ commentText.length }}/1000
                    </span>

                    <div class="flex gap-2">
                        <button
                            v-if="showCancel"
                            type="button"
                            class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:underline px-3"
                            @click="$emit('cancel')">
                            @lang('dam::app.admin.dam.asset.comments.cancel-btn')
                        </button>

                        <button
                            type="button"
                            class="primary-button"
                            :class="{ 'opacity-50 cursor-not-allowed': !canSubmit }"
                            :disabled="!canSubmit"
                            @click="submit">
                            <span v-if="isSubmitting" class="icon-loader animate-spin"></span>
                            <span v-else v-text="submitLabel"></span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </script>

    <script
        type="text/x-template"
        id="v-comment-panel-template">
        <div class="flex gap-3">
            <div
                class="flex-shrink-0 w-9 h-9 rounded-full overflow-hidden"
                v-if="comment.admin?.image_url">
                <img
                    :src="comment.admin?.image_url"
                    class="w-full h-full object-cover object-top"
                    :alt="comment.admin?.name" />
            </div>

            <div
                v-else
                class="flex-shrink-0 w-9 h-9 flex items-center justify-center rounded-full bg-violet-500 text-sm text-white font-semibold"
                v-text="initial"></div>

            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-baseline gap-x-2">
                    <span
                        class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                        v-text="displayName"></span>
                    <span
                        class="text-xs text-gray-500 dark:text-gray-400"
                        :title="absoluteDate(comment.updated_at)">
                        @{{ displayDate(comment.updated_at) }}
                    </span>

                    <div v-if="isOwner && !isEditing" class="ml-auto flex gap-3 text-xs">
                        <button
                            type="button"
                            class="text-violet-600 dark:text-violet-400 hover:underline"
                            @click="startEdit">
                            @lang('dam::app.admin.dam.asset.comments.edit-btn')
                        </button>
                        <button
                            type="button"
                            class="text-red-600 dark:text-red-400 hover:underline"
                            @click="confirmDelete">
                            @lang('dam::app.admin.dam.asset.comments.delete-btn')
                        </button>
                    </div>
                </div>

                <div v-if="isEditing" class="mt-2 flex flex-col gap-2">
                    <textarea
                        v-model="editText"
                        class="w-full border border-gray-300 dark:border-cherry-800 bg-white dark:bg-cherry-900 rounded-lg p-2 text-sm text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-violet-500 resize-y"
                        rows="3"
                        maxlength="1000"
                        @keydown.meta.enter.prevent="saveEdit"
                        @keydown.ctrl.enter.prevent="saveEdit"></textarea>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">@{{ editText.length }}/1000</span>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:underline"
                                @click="cancelEdit">
                                @lang('dam::app.admin.dam.asset.comments.cancel-btn')
                            </button>
                            <button
                                type="button"
                                class="primary-button"
                                :class="{ 'opacity-50 cursor-not-allowed': !canSave }"
                                :disabled="!canSave"
                                @click="saveEdit">
                                <span v-if="isSaving" class="icon-loader animate-spin"></span>
                                <span v-else>@lang('dam::app.admin.dam.asset.comments.save-btn')</span>
                            </button>
                        </div>
                    </div>
                </div>

                <p
                    v-else
                    class="mt-1 text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap break-words"
                    v-text="comment.comments"></p>
            </div>
        </div>
    </script>

    <script type="module">
        const currentAdminId = @json(auth()->guard('admin')->id());
        const updateUrl = "{{ route('admin.dam.asset.comment.update', $id) }}";
        const deleteUrl = "{{ route('admin.dam.asset.comment.delete', $id) }}";

        app.component('v-comments', {
            template: '#v-comments-template',

            data() {
                return {
                    comments: @json($asset->comments),
                    openReplies: {},
                    currentAdminId,
                }
            },

            computed: {
                totalCount() {
                    return this.comments.reduce((acc, c) => acc + 1 + (c.children?.length || 0), 0);
                }
            },

            methods: {
                toggleReply(commentId) {
                    this.openReplies = { ...this.openReplies, [commentId]: !this.openReplies[commentId] };
                },

                onCommentPosted(payload) {
                    if (! payload?.comment) {
                        location.reload();
                        return;
                    }

                    this.comments.unshift({ ...payload.comment, children: [] });
                },

                onReplyPosted(parent, payload) {
                    if (! payload?.comment) {
                        location.reload();
                        return;
                    }

                    if (! parent.children) parent.children = [];
                    parent.children.push(payload.comment);
                    this.openReplies = { ...this.openReplies, [parent.id]: false };
                },

                onCommentUpdated(comment, updated) {
                    Object.assign(comment, updated);
                },

                onReplyUpdated(parent, subIndex, updated) {
                    Object.assign(parent.children[subIndex], updated);
                },

                onCommentDeleted(index) {
                    this.comments.splice(index, 1);
                },

                onReplyDeleted(parent, subIndex) {
                    parent.children.splice(subIndex, 1);
                }
            }
        })

        app.component('v-comment-box', {
            template: '#v-comment-box-template',

            props: {
                parentId: {
                    type: Number,
                    default: null,
                    required: false
                },
                showCancel: {
                    type: Boolean,
                    default: false,
                }
            },

            emits: ['comment-added', 'cancel'],

            data() {
                return {
                    commentText: '',
                    isSubmitting: false,
                    labels: {
                        addComment: @json(trans('dam::app.admin.dam.asset.comments.add-comment')),
                        addReply: @json(trans('dam::app.admin.dam.asset.comments.add-reply')),
                        postComment: @json(trans('dam::app.admin.dam.asset.comments.post-comment')),
                        postReply: @json(trans('dam::app.admin.dam.asset.comments.post-reply')),
                    },
                }
            },

            mounted() {
                if (this.parentId !== null) {
                    this.$nextTick(() => this.$refs.textarea?.focus());
                }
            },

            computed: {
                canSubmit() {
                    return !this.isSubmitting && this.commentText.trim().length >= 2;
                },

                placeholderLabel() {
                    return this.parentId !== null ? this.labels.addReply : this.labels.addComment;
                },

                submitLabel() {
                    return this.parentId !== null ? this.labels.postReply : this.labels.postComment;
                }
            },

            methods: {
                onEsc() {
                    if (this.showCancel) {
                        this.$emit('cancel');
                    }
                },

                submit() {
                    if (!this.canSubmit) return;

                    this.isSubmitting = true;

                    this.$axios.post("{{ route('admin.dam.asset.comment.store', $id) }}", {
                        comments: this.commentText,
                        parent_id: this.parentId,
                    }, {
                        headers: { 'Content-Type': 'application/json' }
                    }).then((response) => {
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message
                        });
                        this.commentText = '';
                        this.$emit('comment-added', response.data);
                    }).catch((error) => {
                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: error.response?.data?.message ?? 'Error'
                        });
                    }).finally(() => {
                        this.isSubmitting = false;
                    });
                }
            }
        })

        app.component('v-comment-panel', {
            template: '#v-comment-panel-template',

            props: {
                comment: {
                    type: Object,
                    required: true,
                },
                currentAdminId: {
                    type: Number,
                    default: null,
                }
            },

            emits: ['updated', 'deleted'],

            data() {
                return {
                    isEditing: false,
                    editText: '',
                    isSaving: false,
                }
            },

            computed: {
                displayName() {
                    return this.comment.admin?.name || 'Deleted user';
                },

                initial() {
                    const name = this.comment.admin?.name;
                    return name ? name.charAt(0).toUpperCase() : '?';
                },

                isOwner() {
                    return this.currentAdminId !== null
                        && Number(this.comment.admin_id) === Number(this.currentAdminId);
                },

                canSave() {
                    return !this.isSaving
                        && this.editText.trim().length >= 2
                        && this.editText.trim() !== (this.comment.comments || '').trim();
                }
            },

            methods: {
                startEdit() {
                    this.editText = this.comment.comments || '';
                    this.isEditing = true;
                },

                cancelEdit() {
                    this.isEditing = false;
                    this.editText = '';
                },

                saveEdit() {
                    if (!this.canSave) return;
                    this.isSaving = true;

                    this.$axios.put(updateUrl, {
                        id: this.comment.id,
                        comments: this.editText,
                    }).then(response => {
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message,
                        });
                        this.$emit('updated', response.data.comment);
                        this.isEditing = false;
                    }).catch(error => {
                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: error.response?.data?.message ?? 'Error',
                        });
                    }).finally(() => {
                        this.isSaving = false;
                    });
                },

                confirmDelete() {
                    this.$emitter.emit('open-delete-modal', {
                        agree: () => this.performDelete(),
                    });
                },

                performDelete() {
                    this.$axios.delete(deleteUrl, {
                        data: { id: this.comment.id },
                    }).then(response => {
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message,
                        });
                        this.$emit('deleted');
                    }).catch(error => {
                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: error.response?.data?.message ?? 'Error',
                        });
                    });
                },

                displayDate(dateString) {
                    if (!dateString) return '';

                    const date = new Date(dateString);
                    const now = new Date();
                    const diffMs = now - date;
                    const diffMins = Math.floor(diffMs / 60000);
                    const diffHours = Math.floor(diffMs / 3600000);

                    if (now.toDateString() === date.toDateString()) {
                        if (diffMins < 1) return 'just now';
                        if (diffMins < 60) return `${diffMins} min ago`;
                        return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
                    }

                    return date.toLocaleString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },

                absoluteDate(dateString) {
                    if (!dateString) return '';
                    return new Date(dateString).toLocaleString();
                }
            }
        })
    </script>
@endPushOnce
