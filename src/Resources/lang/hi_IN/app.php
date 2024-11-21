<?php

return [
    'admin' => [
        'components' => [
            'layouts' => [
                'sidebar' => [
                    'dam' => 'DAM',
                ],
            ],
            'modal' => [
                'confirm' => [
                    'message' => 'इस डायरेक्टरी को हटाने से भी इसके अंदर सभी उप-डायरेक्टरी हटा दिए जाएंगे। यह क्रिया �permanent है और पूर्वरूप में वापस नहीं ली जा सकती।',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'संपत्ति जोड़ें',
                    'assign-assets' => 'संपत्तियां असाइन करें',
                    'assign'        => 'असाइन करें',
                    'preview-asset' => 'संपत्ति पूर्वावलोकन',
                    'preview'       => 'पूर्वावलोकन',
                    'remove'        => 'हटाएं',
                    'download'      => 'डाउनलोड करें',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'फ़ाइल का नाम',
                    'tags'           => 'टैग',
                    'property-name'  => 'गुण का नाम',
                    'property-value' => 'गुण की मान',
                    'created-at'     => 'बनाया गया',
                    'updated-at'     => 'अपडेट किया गया',
                    'extension'      => 'एक्सटेंशन',
                    'path'           => 'पथ',
                ],

                'directory' => [
                    'title'        => 'डायरेक्टरी',
                    'create'       => [
                        'title'    => 'डायरेक्टरी बनाएं',
                        'name'     => 'नाम',
                        'save-btn' => 'डायरेक्टरी सहेजें',
                    ],

                    'rename' => [
                        'title' => 'डायरेक्टरी का नाम बदलें',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'संपत्ति का नाम बदलें',
                            'save-btn' => 'संपत्ति सहेजें',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'हटाएं',
                        'rename'                    => 'नाम बदलें',
                        'copy'                      => 'कॉपी करें',
                        'download'                  => 'डाउनलोड करें',
                        'download-zip'              => 'डाउनलोड ज़िप',
                        'paste'                     => 'पेस्ट करें',
                        'add-directory'             => 'डायरेक्टरी जोड़ें',
                        'upload-files'              => 'फ़ाइलें अपलोड करें',
                        'copy-directory-structured' => 'डायरेक्टरी संरचना कॉपी करें',
                    ],

                    'not-found'                                 => 'कोई डायरेक्टरी नहीं मिली',
                    'created-success'                           => 'डायरेक्टरी सफलतापूर्वक बनाई गई',
                    'updated-success'                           => 'डायरेक्टरी सफलतापूर्वक अपडेट की गई',
                    'moved-success'                             => 'डायरेक्टरी सफलतापूर्वक स्थानांतरित की गई',
                    'can-not-deleted'                           => 'डायरेक्टरी को हटाया नहीं जा सकता क्योंकि यह रूट डायरेक्टरी है।',
                    'deleting-in-progress'                      => 'डायरेक्टरी हटाने में प्रगति में है',
                    'can-not-copy'                              => 'डायरेक्टरी कॉपी नहीं की जा सकती क्योंकि यह रूट डायरेक्टरी है।',
                    'coping-in-progress'                        => 'डायरेक्टरी संरचना कॉपी करने में प्रगति में है।',
                    'asset-not-found'                           => 'कोई संपत्ति नहीं मिली',
                    'asset-renamed-success'                     => 'संपत्ति सफलतापूर्वक नाम बदला गया',
                    'asset-moved-success'                       => 'संपत्ति सफलतापूर्वक स्थानांतरित की गई',
                    'asset-name-already-exist'                  => 'नया नाम एक अन्य संपत्ति के साथ पहले से मौजूद है :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'संपत्ति का नाम एक ही डायरेक्टरी में एक मौजूद फ़ाइल के साथ मेल खाता है।',
                    'old-file-not-found'                        => 'पथ पर अनुरोधित फ़ाइल :old_path नहीं मिली।',
                    'image-name-is-the-same'                    => 'यह नाम पहले से ही मौजूद है। कृपया एक अलग नाम दर्ज करें।',
                    'not-writable'                              => 'आपको :actionType इस :type को इस स्थान पर नहीं करने की अनुमति है ":path"।',
                    'empty-directory'                           => 'यह डायरेक्टरी खाली है।',
                    'failed-download-directory'                 => 'ज़िप फ़ाइल बनाने में विफल।',
                ],

                'title'       => 'DAM',
                'description' => 'आपको अपनी सभी मीडिया संपत्तियों को एक स्थान में व्यवस्थित, संरक्षित और प्रबंधित करने की अनुमति देता है',
                'root'        => 'रूट',
                'upload'      => 'अपलोड करें',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'संपत्ति गुण',
                        'create-btn' => 'संपत्ति बनाएँ',

                        'datagrid'      => [
                            'name'     => 'नाम',
                            'type'     => 'प्रकार',
                            'language' => 'भाषा',
                            'value'    => 'मूल्य',
                            'edit'     => 'संपादित करें',
                            'delete'   => 'हटाएँ',
                        ],

                        'create'     => [
                            'title'    => 'प्रॉपर्टी बनाएं',
                            'name'     => 'नाम',
                            'type'     => 'प्रकार',
                            'language' => 'भाषा',
                            'value'    => 'मूल्य',
                            'save-btn' => 'सहेजें',
                        ],
                        'edit' => [
                            'title' => 'संपत्ति संपादित करें',
                        ],
                        'delete-success' => 'संपत्ति संपत्ति सफलतापूर्वक हटाई गई',
                        'create-success' => 'संपत्ति संपत्ति सफलतापूर्वक बनाई गई',
                        'update-success' => 'संपत्ति संपत्ति सफलतापूर्वक अपडेट की गई',
                    ],
                ],
                'comments' => [
                    'index'  => 'टिप्पणी जोड़ना',
                    'create' => [
                        'create-success' => 'टिप्पणी सफलतापूर्वक जोड़ दी गई है',
                    ],
                    'post-comment' => 'टिप्पणी पोस्ट करें',
                    'post-reply'   => 'उत्तर पोस्ट करें',
                    'reply'        => 'उत्तर दें',
                    'add-reply'    => 'उत्तर जोड़ें',
                    'add-comment'  => 'टिप्पणी जोड़ें',
                    'no-comments'  => 'अभी तक कोई टिप्पणी नहीं',

                ],
                'edit' => [
                    'title'              => 'एसेट संपादित करें',
                    'name'               => 'नाम',
                    'value'              => 'मूल्य',
                    'back-btn'           => 'वापस',
                    'save-btn'           => 'सहेजें',
                    'embedded_meta_info' => 'एम्बेडेड मेटा जानकारी',
                    'custom_meta_info'   => 'कस्टम मेटा जानकारी',
                    'tags'               => 'टैग',
                    'select-tags'        => 'टैग चुनें या बनाएँ',
                    'tag'                => 'टैग',
                    'directory-path'     => 'निर्देशिका पथ',
                    'add_tags'           => 'टैग जोड़ें',
                    'tab'                => [
                        'preview'          => 'पूर्वावलोकन',
                        'properties'       => 'गुण',
                        'comments'         => 'टिप्पणियाँ',
                        'linked_resources' => 'लिंक किए गए संसाधन',
                        'history'          => 'इतिहास',
                    ],
                    'button' => [
                        'download'        => 'डाउनलोड करें',
                        'custom_download' => 'कस्टम डाउनलोड करें',
                        'rename'          => 'नाम बदलें',
                        're_upload'       => 'पुनः अपलोड करें',
                        'delete'          => 'हटाएं',
                    ],

                    'custom-download' => [
                        'title'              => 'कस्टम डाउनलोड',
                        'format'             => 'प्रारूप',
                        'width'              => 'चौड़ाई (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'ऊंचाई (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'डाउनलोड',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'मूल',
                        ],
                    ],

                    'tag-already-exists'        => 'टैग पहले से मौजूद है',
                    'image-source-not-readable' => 'छवि स्रोत पठनीय नहीं है',
                    'failed-to-read'            => 'छवि मेटाडेटा पढ़ने में विफल:exception',
                    'file_re_upload_success'    => 'फ़ाइलें सफलतापूर्वक पुनः अपलोड की गईं।',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'उत्पाद',
                            'category'      => 'श्रेणी',
                            'product-sku'   => 'उत्पाद Sku: ',
                            'category code' => 'श्रेणी कोड: ',
                            'resource-type' => 'संसाधन प्रकार',
                            'resource'      => 'संसाधन',
                            'resource-view' => 'संसाधन दृश्य',
                        ],
                    ],
                ],
                'delete-success'                          => 'संपत्ति सफलतापूर्वक हटा दी गई',
                'delete-failed-due-to-attached-resources' => 'संपत्ति को हटाना विफल रहा क्योंकि यह संसाधनों से लिंक है (संपत्ति का नाम: :assetNames)',
                'datagrid'                                => [
                    'mass-delete-success'  => 'सामूहिक रूप से सफलतापूर्वक हटा दिया गया।',
                    'files_upload_success' => 'फ़ाइलें सफलतापूर्वक अपलोड की गईं।',
                    'file_upload_success'  => 'फ़ाइल सफलतापूर्वक अपलोड की गईं।',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'संपत्ति',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'एसेट',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'एसेट',
            'property'         => 'प्रॉपर्टी',
            'comment'          => 'टिप्पणी',
            'linked_resources' => 'लिंक किए गए संसाधन',
            'directory'        => 'निर्देशिका',
            'tag'              => 'टैग',
            'create'           => 'बनाएँ',
            'edit'             => 'संपादित करें',
            'update'           => 'अपडेट करें',
            'delete'           => 'हटाएँ',
            'list'             => 'सूची',
            'view'             => 'देखें',
            'upload'           => 'अपलोड करें',
            're_upload'        => 'पुनः अपलोड करें',
            'mass_update'      => 'बड़े पैमाने पर अपडेट करें',
            'mass_delete'      => 'बड़े पैमाने पर हटाएं',
            'download'         => 'डाउनलोड करें',
            'custom_download'  => 'कस्टम डाउनलोड करें',
            'rename'           => 'नाम बदलें',
            'move'             => 'स्थानांतरित करें',
            'copy'             => 'कॉपी करें',
            'copy-structure'   => 'निर्देशिका संरचना की प्रतिलिपि बनाएँ',
            'download-zip'     => 'ज़िप डाउनलोड करें',
            'asset-assign'     => 'एसेट असाइन करें',
        ],

        'validation' => [
            'asset' => [
                'required' => ':attribute फ़ील्ड आवश्यक है.',
            ],

            'comment' => [
                'required' => 'टिप्पणी संदेश आवश्यक है.',
            ],

            'property' => [
                'name' => [
                    'required' => 'नाम फ़ील्ड आवश्यक है|',
                    'unique'   => 'नाम पहले ही लिया जा चुका है।',
                ],
            ],
        ],

        'errors' => [
            '401' => 'यह कार्रवाई अनधिकृत है.',
        ],
    ],
];
