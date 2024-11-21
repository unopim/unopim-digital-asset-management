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
                    'message' => 'La suppression de ce répertoire supprimera également tous les sous-répertoires qu\'il contient. Cette action est permanente et ne peut pas être annulée.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Ajouter un élément',
                    'assign-assets' => 'Affecter des éléments',
                    'assign'        => 'Affecter',
                    'preview-asset' => 'Aperçu de l\'élément',
                    'preview'       => 'Aperçu',
                    'remove'        => 'Supprimer',
                    'download'      => 'Télécharger',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Nom de fichier',
                    'tags'           => 'Tags',
                    'property-name'  => 'Nom de la propriété',
                    'property-value' => 'Valeur de la propriété',
                    'created-at'     => 'Créé à',
                    'updated-at'     => 'Mis à jour à',
                    'extension'      => 'Extension',
                    'path'           => 'Chemin',
                ],

                'directory' => [
                    'title'        => 'Annuaire',
                    'create'       => [
                        'title'    => 'Créer un répertoire',
                        'name'     => 'Nom',
                        'save-btn' => 'Enregistrer le répertoire',
                    ],

                    'rename' => [
                        'title' => 'Renommer le répertoire',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Renommer l\'actif',
                            'save-btn' => 'Enregistrer l\'actif',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Supprimer',
                        'rename'                    => 'Renommer',
                        'copy'                      => 'Copier',
                        'download'                  => 'Télécharger',
                        'download-zip'              => 'Télécharger le fichier Zip',
                        'paste'                     => 'Coller',
                        'add-directory'             => 'Ajouter un répertoire',
                        'upload-files'              => 'Télécharger des fichiers',
                        'copy-directory-structured' => 'Copier le répertoire structuré',
                    ],

                    'not-found'                                 => 'Aucun répertoire trouvé',
                    'created-success'                           => 'Répertoire créé avec succès',
                    'updated-success'                           => 'Répertoire mis à jour avec succès',
                    'moved-success'                             => 'Répertoire déplacé avec succès',
                    'can-not-deleted'                           => 'Le répertoire ne peut pas être supprimé car il s\'agit du répertoire racine.',
                    'deleting-in-progress'                      => 'Suppression du répertoire en cours',
                    'can-not-copy'                              => 'Le répertoire ne peut pas être copié car il s\'agit du répertoire racine.',
                    'coping-in-progress'                        => 'Copie de la structure du répertoire en cours.',
                    'asset-not-found'                           => 'Aucun élément trouvé',
                    'asset-renamed-success'                     => 'Élément renommé avec succès',
                    'asset-moved-success'                       => 'Élément déplacé avec succès',
                    'asset-name-already-exist'                  => 'Le nouveau nom existe déjà avec un autre élément nommé :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Le nom de l\'élément est en conflit avec un fichier existant dans le même répertoire.',
                    'old-file-not-found'                        => 'Le fichier demandé au chemin :old_path n\'a pas été trouvé.',
                    'image-name-is-the-same'                    => 'Ce nom existe déjà. Veuillez en saisir un autre.',
                    'not-writable'                              => 'Vous n\'êtes pas autorisé à :actionType un :type à cet emplacement ":path".',
                    'empty-directory'                           => 'Ce répertoire est vide.',
                    'failed-download-directory'                 => 'Échec de la création du fichier zip.',
                ],

                'title'       => 'DAM',
                'description' => 'L\'outil peut vous aider à organiser, stocker et gérer tous vos contenus multimédias en un seul endroit',
                'root'        => 'Racine',
                'upload'      => 'Télécharger',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Propriétés de l\'actif',
                        'create-btn' => 'Créer une propriété',

                        'datagrid'      => [
                            'name'     => 'Nom',
                            'type'     => 'Type',
                            'language' => 'Langue',
                            'value'    => 'Valeur',
                            'edit'     => 'Modifier',
                            'delete'   => 'Supprimer',
                        ],

                        'create'     => [
                            'title'    => 'Créer une propriété',
                            'name'     => 'Nom',
                            'type'     => 'Type',
                            'language' => 'Langue',
                            'value'    => 'Valeur',
                            'save-btn' => 'Enregistrer',
                        ],
                        'edit' => [
                            'title' => 'Modifier la propriété',
                        ],
                        'delete-success' => 'Propriété de l\'actif supprimée avec succès',
                        'create-success' => 'Propriété de l\'actif créée avec succès',
                        'update-success' => 'Propriété de l\'actif mise à jour avec succès',
                    ],
                ],
                'comments' => [
                    'index'  => 'Ajouter un commentaire',
                    'create' => [
                        'create-success' => 'Le commentaire a été ajouté avec succès',
                    ],
                    'post-comment' => 'Poster un commentaire',
                    'post-reply'   => 'Poster une réponse',
                    'reply'        => 'Répondre',
                    'add-reply'    => 'Ajouter une réponse',
                    'add-comment'  => 'Ajouter un commentaire',
                    'no-comments'  => 'Aucun commentaire pour le moment',

                ],
                'edit' => [
                    'title'              => 'Modifier l\'actif',
                    'name'               => 'Nom',
                    'value'              => 'Valeur',
                    'back-btn'           => 'Retour',
                    'save-btn'           => 'Enregistrer',
                    'embedded_meta_info' => 'Méta-informations intégrées',
                    'custom_meta_info'   => 'Méta-informations personnalisées',
                    'tags'               => 'Balises',
                    'select-tags'        => 'Choisir ou créer une balise',
                    'tag'                => 'Balise',
                    'directory-path'     => 'Chemin du répertoire',
                    'add_tags'           => 'Ajouter des balises',
                    'tab'                => [
                        'preview'          => 'Aperçu',
                        'properties'       => 'Propriétés',
                        'comments'         => 'Commentaires',
                        'linked_resources' => 'Ressources liées',
                        'history'          => 'Historique',
                    ],
                    'button' => [
                        'download'        => 'Télécharger',
                        'custom_download' => 'Téléchargement personnalisé',
                        'rename'          => 'Renommer',
                        're_upload'       => 'Re-télécharger',
                        'delete'          => 'Supprimer',
                    ],

                    'custom-download' => [
                        'title'              => 'Téléchargement personnalisé',
                        'format'             => 'Format',
                        'width'              => 'Largeur (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Hauteur (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Télécharger',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Origine',
                        ],
                    ],

                    'tag-already-exists'        => 'La balise existe déjà',
                    'image-source-not-readable' => 'La source de l\'image n\'est pas lisible',
                    'failed-to-read'            => 'Échec de la lecture des métadonnées de l\'image :exception',
                    'file_re_upload_success'    => 'Fichiers re-téléchargés avec succès.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Produit',
                            'category'      => 'Catégorie',
                            'product-sku'   => 'Référence produit :',
                            'category code' => 'Code catégorie :',
                            'resource-type' => 'Type de ressource',
                            'resource'      => 'Ressource',
                            'resource-view' => 'Affichage de la ressource',
                        ],
                    ],
                ],
                'delete-success'                          => 'L\'actif a été supprimé avec succès',
                'delete-failed-due-to-attached-resources' => 'Échec de la suppression de l\'actif car il est lié à des ressources (Nom de l\'actif : :assetNames)',
                'datagrid'                                => [
                    'mass-delete-success'  => 'Suppression massive réussie.',
                    'files_upload_success' => 'Fichiers téléchargés avec succès.',
                    'file_upload_success'  => 'Fichier téléchargé avec succès.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Actif',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Actif',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Actif',
            'property'         => 'Propriété',
            'comment'          => 'Commentaire',
            'linked_resources' => 'Ressources liées',
            'directory'        => 'Répertoire',
            'tag'              => 'Étiquette',
            'create'           => 'Créer',
            'edit'             => 'Modifier',
            'update'           => 'Mettre à jour',
            'delete'           => 'Supprimer',
            'list'             => 'Liste',
            'view'             => 'Afficher',
            'upload'           => 'Télécharger',
            're_upload'        => 'Re-télécharger',
            'mass_update'      => 'Mise à jour en masse',
            'mass_delete'      => 'Suppression en masse',
            'download'         => 'Télécharger',
            'custom_download'  => 'Téléchargement personnalisé',
            'rename'           => 'Renommer',
            'move'             => 'Déplacer',
            'copy'             => 'Copier',
            'copy-structure'   => 'Copier la structure du répertoire',
            'download-zip'     => 'Télécharger le fichier ZIP',
            'asset-assign'     => 'Attribuer un actif',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Le champ :attribute est obligatoire.',
            ],

            'comment' => [
                'required' => 'Le message de commentaire est obligatoire.',
            ],

            'property' => [
                'name' => [
                    'required' => 'Le champ Nom est obligatoire.',
                    'unique'   => 'Le nom est déjà pris.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Cette action n\'est pas autorisée.',
        ],
    ],
];
