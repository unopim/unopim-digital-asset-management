<v-comments>
    <v-comment-box></v-comment-box>
</v-comments>

{!! view_render_event('dam.admin.dam.asset.comments.create.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-comments-template">
        <div class="flex flex-col flex-1 gap-2 overflow-auto bg-white dark:bg-cherry-900 rounded-lg box-shadow items-center justify-between">
            <div class="flex flex-col gap-6 w-full max-w-6xl h-[calc(100vh-180px)] m-6 p-6">
                <v-comment-box />
                @if (bouncer()->hasPermission('dam.asset.comment.index'))
                    <div v-if="comments?.length" class="flex flex-col gap-8" v-for="(comment, index) in comments" :key="index">
                        <v-comment-panel :comment="comment" />
                        <div v-for="(subComment, subIndex) in comment.children" :key="subIndex">
                            <v-comment-panel :comment="subComment" :isChild="true" />
                        </div>
                        <v-comment-box :parentId="comment.id" />
                    </div>

                    <div class="flex flex-col px-4 py-4 justify-center gap-2 text-center items-center text-xl text-zinc-800 dark:text-slate-50 font-bold leading-normal m-auto" v-else>
                        <svg width="96" height="97" viewBox="0 0 96 97" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M50.0009 12.185C46.4387 12.1301 42.8758 12.2196 39.3209 12.453C22.5849 13.565 9.25687 27.081 8.16087 44.053C7.94898 47.4096 7.94898 50.7763 8.16087 54.133C8.56087 60.313 11.2929 66.037 14.5129 70.869C16.3809 74.249 15.1489 78.469 13.2009 82.161C11.8009 84.821 11.0969 86.149 11.6609 87.109C12.2209 88.069 13.4809 88.101 15.9969 88.161C20.9769 88.281 24.3329 86.873 26.9969 84.909C28.5049 83.793 29.2609 83.237 29.7809 83.173C30.3009 83.109 31.3289 83.533 33.3769 84.373C35.2169 85.133 37.3569 85.601 39.3169 85.733C45.0169 86.109 50.9729 86.109 56.6849 85.733C73.4169 84.621 86.7449 71.101 87.8409 54.133C88.0089 51.513 88.0449 48.821 87.9489 46.169M64.0009 8.16898L88.0009 32.169M64.0009 32.169L88.0009 8.16898M34.0009 60.169H62.0009M34.0009 40.169H48.0009" stroke="#7C3AEC" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
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
            <div :class="[{ 'ml-8 pl-4 border-l-2': parentId !== null }, 'flex flex-col gap-2 w-full text-base font-medium text-gray-600 dark:text-gray-300']">
                <p class="text-gray-600 dark:text-gray-300" v-if="parentId">@lang('dam::app.admin.dam.asset.comments.reply')</p>
                <p class="text-gray-600 dark:text-gray-300" v-else>@lang('dam::app.admin.dam.asset.comments.index')</p>
                <textarea 
                    class="overflow-hidden border rounded-lg p-3 dark:text-gray-400" 
                    rows="4" 
                    :placeholder="parentId !== null ? '{{ trans('dam::app.admin.dam.asset.comments.add-reply') }}' : '{{ trans('dam::app.admin.dam.asset.comments.add-comment') }}'" 
                />

                <div v-if="parentId">
                    <button class="secondary-button" @click="addComment(parentId)">
                        @lang('dam::app.admin.dam.asset.comments.post-reply')
                    </button>    
                </div>
                
                <div v-else>
                    <button class="secondary-button" @click="addComment">@lang('dam::app.admin.dam.asset.comments.post-comment')</button>    
                </div>
            </div>
        @endif
    </script>
    
    <script
        type="text/x-template"
        id="v-comment-panel-template">
        
        <div :class="['flex', 'flex-col', 'gap-4', isChild ? 'ml-8 pl-4 border-l-2' : '']">
            <div class="flex flex-row gap-3 items-center">

                <button class="flex w-9 h-9 overflow-hidden rounded-full cursor-pointer hover:opacity-80 focus:opacity-80" v-if=comment.admin?.user.image>
                    <img
                        :src="comment.admin?.user.image_url"
                        class="w-full h-full object-cover object-top"
                        :alt="comment.admin?.user.image_url" />
                </button>

                <button class="flex justify-center items-center w-12 h-12 bg-[#7268A6] rounded-full text-sm text-white font-semibold cursor-pointer leading-6 transition-all hover:bg-violet-500 focus:bg-violet-500" v-else
                    v-text="comment.admin?.user.name.charAt(0)">
                </button>

                <div class="flex flex-col">
                    <span class="text-base font-bold text-gray-800 dark:text-gray-300" v-text="comment.admin?.user.name"></span>
                    <span class="text-base  text-gray-600 dark:text-gray-300">
                        @{{ displayDate(comment.updated_at) }}
                    </span>
                </div>

            </div>
            <p :class="[isChild ? 'indent-2' : '', 'text-gray-800 dark:text-gray-300']" v-text="commentDetails.comments" />
        </div>

    </script>

    <script type="module">
        app.component('v-comments', {
            template: '#v-comments-template',

            data() {
                return {
                    comments: @json($asset->comments)
                }
            },

            methods: {
                
            }
        })

        app.component('v-comment-box', {
            template: '#v-comment-box-template',

            props: {
                parentId: {
                    type: Number,
                    default: null,
                    required: false
                }
            },

            methods: {
                addComment() {
                    let comments = this.$el.querySelector('textarea').value;

                    if (comments) {
                        this.$axios.post("{{ route('admin.dam.asset.comment.store', $id) }}", {
                            comments: comments,
                            parent_id: this.parentId
                        }, {
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        }).then((response) => {
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message
                            });
                            this.$el.querySelector('textarea').value = '';
                            location.reload();

                        }).catch((error) => {

                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response.data.message
                            });
                        });
                    }
                }
            }
        })

        app.component('v-comment-panel', {
            template: '#v-comment-panel-template',
            props: {
                isChild: false,
                comment: {
                    type: Object,
                    required: true,
                    default: function() {
                        return {
                            id: null,
                            admin_id: null,
                            parent_id: null,
                            comments: '',
                            dam_asset_id: null,
                            created_at: null,
                            updated_at: null
                        }
                    }
                }
            },

            mounted() {
                this.fetchAdminDetails();
            },

            methods: {
                fetchAdminDetails() {
                    if (this.comment.admin_id) {

                        this.$axios.get("{{ route('admin.dam.asset.comments.get_user_info', 'admin_id') }}".replace('admin_id', this.comment.admin_id))
                            .then(response => {
                                this.commentDetails.admin = response.data;
                            })
                            .catch(error => {
                                console.error('Error fetching admin details:', error);
                            });
                    }
                },
                displayDate(dateString) {
                    const date = new Date(dateString);
                    const now = new Date();

                    const diffMs = now - date;
                    const diffMins = Math.floor(diffMs / (1000 * 60));
                    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));

                    if (now.toDateString() === date.toDateString()) {
                        if (diffMins < 1) {
                            return "just now";
                        } else if (diffMins < 60) {
                            return `${diffMins} min ago`;
                        } else {
                            return `${diffHours} hour${diffHours > 1 ? "s" : ""} ago`;
                        }
                    } else {
                        return date.toLocaleString("en-US", {
                            year: "numeric",
                            month: "long",
                            day: "numeric",
                            hour: "2-digit",
                            minute: "2-digit"
                        });
                    }
                }

            },

            data() {
                return {
                    commentDetails: this.comment
                }
            },

        })
    </script>
@endPushOnce
