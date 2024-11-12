<x-admin::layouts>
    @push('styles')
        @unoPimVite(['src/Resources/assets/css/app.css', 'src/Resources/assets/js/app.js'], 'admin')
        
    @endpush

    <x-slot:title>
        @lang('dam::app.admin.dam.index.title')
    </x-slot:title>
     

    {!! view_render_event('unopim.dam.admin.main.before') !!}

    <v-dam-main></v-dam-main>

    {!! view_render_event('unopim.dam.admin.main.after') !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-dam-main-template"
        >
            <div>
                {!! view_render_event('dam.admin.main.form.before') !!}
                    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                        <!-- left sub component -->
                        <div class="flex flex-col gap-5 max-w-[28%] h-full max-sm:w-full p-4 bg-white dark:bg-cherry-900 rounded-lg box-shadow">
                            
                                {!! view_render_event('dam.admin.main.form.directory.before') !!}
                                <div class="flex flex-col gap-2">
                                    <p class="text-xl text-zinc-800 dark:text-slate-50 font-bold !leading-normal">
                                        @lang('dam::app.admin.dam.index.title')
                                    </p>
                                    <p class="text-sm text-zinc-600 !leading-normal dark:text-slate-300">
                                        @lang('dam::app.admin.dam.index.description')
                                    </p>    
                                </div>

                                <div class="dark:bg-cherry-700 border-b dark:border-cherry-800"></div>

                                <div class="flex flex-col gap-5">
                                    <p class="text-base	text-zinc-800 dark:text-slate-50 font-bold !leading-normal">
                                        @lang('dam::app.admin.dam.index.directory.title')
                                    </p>
                                    <x-dam::tree.damdirectories />
                                </div>
                                {!! view_render_event('dam.admin.main.form.directory.after') !!}
                             
                        </div>

                        <!-- right sub-component -->
                        <div class="flex flex-col gap-2 max-w-[70%] flex-1 max-xl:flex-auto max-w-full p-4 bg-white dark:bg-cherry-900 rounded-lg box-shadow">
                            {!! view_render_event('dam.admin.main.form.grid.before') !!}
                            <v-dam-upload></v-dam-upload> 
                            {!! view_render_event('dam.admin.main.form.grid.before') !!}
                        </div>
                    </div>
                {!! view_render_event('dam.admin.main.form.after') !!}
            </div>
        </script>

        <script type="module">
            app.component('v-dam-main', {
                template: '#v-dam-main-template',

                data() {
                    return {}
                },

                methods: {

                }
            })
        </script>
    @endPushOnce

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-dam-upload-template"
        >
            <div>
                <div class="flex justify-between items-center w-full">
                    <p
                        class="text-base text-gray-600 dark:text-gray-300 font-bold"
                        v-if="currentDirectory"
                    >
                        @{{currentDirectory.name}}
                    </p>
                    <p
                        class="text-base text-gray-600 dark:text-gray-300 font-bold"
                        v-else
                    >
                        @lang('dam::app.admin.dam.index.root')
                    </p>
                    <input type="file"
                        multiple="multiple"
                        name="files[]"
                        id="file-upload"
                        class="hidden"
                        @change="onFileChange"
                    />
                    <label
                        for="file-upload"
                        class="secondary-button cursor-pointer"
                    >
                        <span class="icon-dam-upload"></span>
                        @lang('dam::app.admin.dam.index.upload')
                    </label>
                    
                </div>
    
                {!! view_render_event('unopim.admin.dam.assets.list.before') !!}
                
                <x-dam::datagrid.dam 
                    :src="route('admin.dam.assets.index')"
                    ref="datagrid"
                />
    
                {!! view_render_event('unopim.admin.dam.assets.list.after') !!}
            </div>
    
        </script>
        <script type="module">
    
            app.component('v-dam-upload', {
                template: '#v-dam-upload-template',
    
                data() {
                    return {
                        currentDirectory: null,
                    }
                },

                mounted() {
                    this.$emitter.on('current-directory', (data) => {
                        this.currentDirectory = data;
                    });
                },
    
                methods: {
                    onFileChange(e) {
                        let fileInput = e.target.files;
    
                        if (fileInput.length > 0) {
                            let formData = new FormData();
                                    
                            for (let index = 0; index < fileInput.length; index++) {
                                formData.append('files[]', fileInput[index]);
                            }

                            if (this.currentDirectory) {
                                formData.append('directory_id', this.currentDirectory.id);
                            }
                            
                            this.handleFileUpload(formData);
                        }
                    },
                    handleFileUpload(formData) {   
                        this.$axios.post("{{ route('admin.dam.assets.upload') }}", formData, {
                            headers: {
                                'Content-Type': 'multipart/form-data',
                            }
                        }).then((response) => {
                            this.$refs.datagrid.get();
                            this.$emitter.emit('uploaded-assets', response.data.files);
                            this.$emitter.emit('add-flash', {type: 'success', message: response.data.message});
                        }).catch((error) => {
                            console.log(error);
                            this.$emitter.emit('add-flash', {type: 'error', message: error.response.data.message});
                                console.error('Upload failed:', error);
                        });
                    }
                }
            })
        </script>
    @endPushOnce
</x-admin::layouts>
