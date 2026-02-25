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
                    'message' => 'इस निर्देशिका को हटाने से इसके अंदर सभी उपनिर्देशिकाएँ भी हट जाएँगी। यह क्रिया स्थायी है और इसे पूर्ववत नहीं किया जा सकता।',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'संपत्ति जोड़ें',
                    'assign-assets' => 'संपत्तियाँ सौंपें',
                    'assign'        => 'सौंपें',
                    'preview-asset' => 'संपत्ति का पूर्वावलोकन करें',
                    'preview'       => 'पूर्वावलोकन',
                    'remove'        => 'हटाएँ',
                    'download'      => 'डाउनलोड करें',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'फाइल का नाम',
                    'tags'           => 'टैग',
                    'property-name'  => 'गुणवत्ता का नाम',
                    'property-value' => 'गुणवत्ता की मान',
                    'created-at'     => 'निर्मित हुआ',
                    'updated-at'     => 'अपडेट किया गया',
                    'extension'      => 'विस्तार',
                    'path'           => 'पथ',
                    'size'           => 'आकार',
                ],

                'directory' => [
                    'title'        => 'निर्देशिका',
                    'create'       => [
                        'title'    => 'निर्देशिका बनाएँ',
                        'name'     => 'नाम',
                        'save-btn' => 'निर्देशिका सहेजें',
                    ],

                    'rename' => [
                        'title' => 'निर्देशिका का नाम बदलें',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'संपत्ति का नाम बदलें',
                            'save-btn' => 'संपत्ति सहेजें',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'हटाएँ',
                        'rename'                    => 'नाम बदलें',
                        'copy'                      => 'कॉपी करें',
                        'download'                  => 'डाउनलोड करें',
                        'download-zip'              => 'ज़िप डाउनलोड करें',
                        'paste'                     => 'पेस्ट करें',
                        'add-directory'             => 'निर्देशिका जोड़ें',
                        'upload-files'              => 'फाइलें अपलोड करें',
                        'copy-directory-structured' => 'निर्देशिका संरचना कॉपी करें',
                    ],

                    'not-found'                                 => 'कोई निर्देशिका नहीं मिली',
                    'created-success'                           => 'निर्देशिका सफलतापूर्वक बनाई गई',
                    'updated-success'                           => 'निर्देशिका सफलतापूर्वक अपडेट की गई',
                    'moved-success'                             => 'निर्देशिका सफलतापूर्वक स्थानांतरित की गई',
                    'fetch-all-success'                         => 'निर्देशिकाएँ सफलतापूर्वक प्राप्त की गई',
                    'can-not-deleted'                           => 'निर्देशिका को हटाया नहीं जा सकता क्योंकि यह मूल निर्देशिका है।',
                    'deleting-in-progress'                      => 'निर्देशिका हटाने की प्रक्रिया चल रही है',
                    'can-not-copy'                              => 'निर्देशिका को कॉपी नहीं किया जा सकता क्योंकि यह मूल निर्देशिका है।',
                    'coping-in-progress'                        => 'निर्देशिका संरचना की कॉपीिंग चल रही है।',
                    'asset-not-found'                           => 'कोई संपत्ति नहीं मिली',
                    'asset-renamed-success'                     => 'संपत्ति का नाम सफलतापूर्वक बदल दिया गया',
                    'asset-moved-success'                       => 'संपत्ति सफलतापूर्वक स्थानांतरित की गई',
                    'asset-name-already-exist'                  => 'नया नाम पहले से किसी अन्य संपत्ति के साथ मौजूद है जिसका नाम :asset_name है',
                    'asset-name-conflict-in-the-same-directory' => 'संपत्ति का नाम उसी निर्देशिका में पहले से मौजूद फाइल से मेल खाता है।',
                    'old-file-not-found'                        => 'जो फाइल पथ :old_path पर अनुरोध की गई थी, वह नहीं मिली।',
                    'image-name-is-the-same'                    => 'यह नाम पहले से मौजूद है। कृपया एक अलग नाम दर्ज करें।',
                    'not-writable'                              => 'आपको इस स्थान ":path" में :actionType करने की अनुमति नहीं है।',
                    'empty-directory'                           => 'यह निर्देशिका खाली है।',
                    'failed-download-directory'                 => 'ज़िप फ़ाइल बनाने में विफल',
                    'not-allowed'                               => 'स्क्रिप्ट फाइलें अपलोड करना अनुमति नहीं है।',
                ],

                'title'       => 'DAM',
                'description' => 'यह उपकरण आपको सभी मीडिया संपत्तियों को एक स्थान पर व्यवस्थित, संग्रहित और प्रबंधित करने में मदद करेगा',
                'root'        => 'मूल',
                'upload'      => 'अपलोड करें',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'संपत्ति गुण',
                        'create-btn' => 'गुणवत्ता बनाएँ',

                        'datagrid'      => [
                            'name'     => 'नाम',
                            'type'     => 'प्रकार',
                            'language' => 'भाषा',
                            'value'    => 'मान',
                            'edit'     => 'संपादित करें',
                            'delete'   => 'हटाएँ',
                        ],

                        'create'     => [
                            'title'    => 'गुणवत्ता बनाएँ',
                            'name'     => 'नाम',
                            'type'     => 'प्रकार',
                            'language' => 'भाषा',
                            'value'    => 'मान',
                            'save-btn' => 'सहेजें',
                        ],
                        'edit' => [
                            'title' => 'गुणवत्ता संपादित करें',
                        ],
                        'delete-success' => 'संपत्ति गुण सफलतापूर्वक हटा दिया गया',
                        'create-success' => 'संपत्ति गुण सफलतापूर्वक बनाया गया',
                        'update-success' => 'संपत्ति गुण सफलतापूर्वक अपडेट किया गया',
                        'not-found'      => 'गुणवत्ता नहीं मिली',
                        'found-success'  => 'गुणवत्ता सफलतापूर्वक मिली',
                    ],
                ],
                'comments' => [
                    'index'  => 'टिप्पणी जोड़ें',
                    'create' => [
                        'create-success' => 'टिप्पणी सफलतापूर्वक जोड़ी गई',
                        'create-failure' => 'टिप्पणी जोड़ने में विफल',
                    ],
                    'post-comment'    => 'टिप्पणी पोस्ट करें',
                    'post-reply'      => 'जवाब पोस्ट करें',
                    'reply'           => 'जवाब',
                    'add-reply'       => 'जवाब जोड़ें',
                    'add-comment'     => 'टिप्पणी जोड़ें',
                    'no-comments'     => 'अभी तक कोई टिप्पणी नहीं',
                    'not-found'       => 'टिप्पणियाँ नहीं मिली',
                    'updated-success' => 'टिप्पणी सफलतापूर्वक अपडेट की गई',
                    'update-failed'   => 'टिप्पणी अपडेट करने में विफल',
                    'delete-success'  => 'संपत्ति टिप्पणी सफलतापूर्वक हटा दी गई',
                    'delete-failed'   => 'संपत्ति टिप्पणी हटाने में विफल',
                ],
                'edit' => [
                    'title'              => 'संपत्ति संपादित करें',
                    'name'               => 'नाम',
                    'value'              => 'मान',
                    'back-btn'           => 'वापस',
                    'save-btn'           => 'सहेजें',
                    'embedded_meta_info' => 'एंबेडेड मेटा जानकारी',
                    'custom_meta_info'   => 'कस्टम मेटा जानकारी',
                    'tags'               => 'टैग',
                    'select-tags'        => 'टैग चुनें या बनाएं',
                    'tag'                => 'टैग',
                    'directory-path'     => 'निर्देशिका का पथ',
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
                        'custom_download' => 'कस्टम डाउनलोड',
                        'rename'          => 'नाम बदलें',
                        're_upload'       => 'फिर से अपलोड करें',
                        'delete'          => 'हटाएं',
                    ],

                    'custom-download' => [
                        'title'              => 'कस्टम डाउनलोड',
                        'format'             => 'फॉर्मेट',
                        'width'              => 'चौड़ाई (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'ऊंचाई (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'डाउनलोड करें',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'मूल',
                        ],
                    ],

                    'tag-already-exists'        => 'टैग पहले से मौजूद है',
                    'image-source-not-readable' => 'चित्र स्रोत पढ़ा नहीं जा सका',
                    'failed-to-read'            => 'चित्र मेटाडेटा पढ़ने में विफल :exception',
                    'file-re-upload-success'    => 'फाइलें सफलतापूर्वक फिर से अपलोड की गईं।',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'उत्पाद',
                            'category'      => 'श्रेणी',
                            'product-sku'   => 'उत्पाद SKU: ',
                            'category code' => 'श्रेणी कोड: ',
                            'resource-type' => 'संसाधन प्रकार',
                            'resource'      => 'संसाधन',
                            'resource-view' => 'संसाधन दृश्य',
                        ],
                    ],
                    'found-success' => 'संसाधन सफलतापूर्वक पाया गया',
                    'not-found'     => 'संसाधन नहीं मिला',
                ],
                'tags' => [
                    'index'  => 'टैग जोड़ें',
                    'create' => [
                        'create-success' => 'टैग सफलतापूर्वक जोड़े गए',
                        'create-failure' => 'टैग जोड़ने में विफल',
                    ],

                    'no-comments'    => 'अभी तक कोई टैग नहीं हैं',
                    'found-success'  => 'टैग सफलतापूर्वक मिला',
                    'not-found'      => 'टैग नहीं मिला',
                    'update-success' => 'टैग सफलतापूर्वक अपडेट किए गए',
                    'update-failed'  => 'टैग अपडेट करने में विफल',
                    'delete-success' => 'एसेट टैग सफलतापूर्वक हटाए गए',
                    'delete-failed'  => 'संपत्ति टैग हटाने में विफल',
                ],
                'delete-success'                          => 'संपत्ति सफलतापूर्वक हटा दी गई',
                'delete-failed-due-to-attached-resources' => 'एसेट उपयोग में है। हटाने से पहले अनलिंक करें',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'संपत्ति समूह में सफलतापूर्वक हटा दी गई।',
                    'files-upload-success'                => 'फाइलें सफलतापूर्वक अपलोड की गईं।',
                    'file-upload-success'                 => 'फाइल सफलतापूर्वक अपलोड की गई।',
                    'not-found'                           => 'फाइल नहीं मिली',
                    'edit-success'                        => 'फाइल सफलतापूर्वक अपलोड की गई',
                    'show-success'                        => 'फाइल सफलतापूर्वक पाई गई',
                    'update-success'                      => 'फाइल सफलतापूर्वक अपडेट की गई',
                    'not-found-to-update'                 => 'फाइल मौजूद नहीं है',
                    'not-found-to-destroy'                => 'फाइल मौजूद नहीं है',
                    'files-upload-failed'                 => 'फाइलें अपलोड करने में विफल',
                    'file-upload-failed'                  => 'फाइल अपलोड करने में विफल',
                    'invalid-file'                        => 'अमान्य फाइल प्रदान की गई',
                    'invalid-file-format'                 => 'अमान्य फॉर्मेट',
                    'invalid-file-format-or-not-provided' => 'कोई फाइल प्रदान नहीं की गई या अमान्य फॉर्मेट है।',
                    'download-image-failed'               => 'URL से चित्र डाउनलोड करने में विफल',
                    'file-process-failed'                 => 'कुछ फाइलें प्रोसेस करने में विफल',
                    'file-forbidden-type'                 => 'फाइल का प्रकार या विस्तार निषिद्ध है।',
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
                    'asset' => 'संपत्ति',
                ],
            ],
        ],

        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'संपत्ति',
            'property'         => 'गुण',
            'comment'          => 'टिप्पणी',
            'linked_resources' => 'लिंक किए गए संसाधन',
            'directory'        => 'निर्देशिका',
            'tag'              => 'टैग',
            'create'           => 'बनाएं',
            'edit'             => 'संपादित करें',
            'update'           => 'अपडेट करें',
            'delete'           => 'हटाएं',
            'list'             => 'सूची',
            'view'             => 'देखें',
            'upload'           => 'अपलोड करें',
            're_upload'        => 'फिर से अपलोड करें',
            'mass_update'      => 'समूह में अपडेट करें',
            'mass_delete'      => 'समूह में हटाएं',
            'download'         => 'डाउनलोड करें',
            'custom_download'  => 'कस्टम डाउनलोड',
            'rename'           => 'नाम बदलें',
            'move'             => 'स्थानांतरित करें',
            'copy'             => 'कॉपी करें',
            'copy-structure'   => 'निर्देशिका संरचना कॉपी करें',
            'download-zip'     => 'ज़िप डाउनलोड करें',
            'asset-assign'     => 'संपत्ति सौंपें',
        ],

        'validation' => [
            'asset' => [
                'required' => ':attribute फ़ील्ड आवश्यक है।',
            ],

            'comment' => [
                'required' => 'टिप्पणी संदेश आवश्यक है।',
            ],
            'tag' => [
                'name' => [
                    'required' => 'टैग फ़ील्ड आवश्यक है।',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'नाम फ़ील्ड आवश्यक है।',
                    'unique'   => 'नाम पहले से लिया गया है।',
                ],
                'language' => [
                    'not-found' => 'चयनित भाषा नहीं मिली या वर्तमान में अक्षम है।',
                ],

            ],
        ],

        'errors' => [
            '401' => 'यह क्रिया अस्वीकृत है।',
        ],
    ],
];
