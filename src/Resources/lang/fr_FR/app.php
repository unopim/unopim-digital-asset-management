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
                    'message' => 'La suppression de ce répertoire entraînera également la suppression de tous les sous-répertoires à l\'intérieur. Cette action est permanente et ne peut pas être annulée.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Ajouter un Actif',
                    'assign-assets' => 'Attribuer des Actifs',
                    'assign'        => 'Attribuer',
                    'preview-asset' => 'Prévisualiser l\'Actif',
                    'preview'       => 'Prévisualiser',
                    'remove'        => 'Supprimer',
                    'download'      => 'Télécharger',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Nom du Fichier',
                    'tags'           => 'Étiquettes',
                    'property-name'  => 'Nom de la Propriété',
                    'property-value' => 'Valeur de la Propriété',
                    'created-at'     => 'Créé le',
                    'updated-at'     => 'Mis à jour le',
                    'extension'      => 'Extension',
                    'path'           => 'Chemin',
                    'size'           => 'Taille',
                ],

                'directory' => [
                    'title'        => 'Répertoire',
                    'create'       => [
                        'title'    => 'Créer un Répertoire',
                        'name'     => 'Nom',
                        'save-btn' => 'Enregistrer le Répertoire',
                    ],

                    'rename' => [
                        'title' => 'Renommer le Répertoire',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Renommer l\'Actif',
                            'save-btn' => 'Enregistrer l\'Actif',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Supprimer',
                        'rename'                    => 'Renommer',
                        'copy'                      => 'Copier',
                        'download'                  => 'Télécharger',
                        'download-zip'              => 'Télécharger Zip',
                        'paste'                     => 'Coller',
                        'add-directory'             => 'Ajouter un Répertoire',
                        'upload-files'              => 'Télécharger des Fichiers',
                        'copy-directory-structured' => 'Copier la Structure du Répertoire',
                    ],

                    'not-found'                                 => 'Aucun répertoire trouvé',
                    'created-success'                           => 'Répertoire créé avec succès',
                    'updated-success'                           => 'Répertoire mis à jour avec succès',
                    'moved-success'                             => 'Répertoire déplacé avec succès',
                    'fetch-all-success'                         => 'Répertoires récupérés avec succès',
                    'can-not-deleted'                           => 'Le répertoire ne peut pas être supprimé car il s\'agit du répertoire racine.',
                    'deleting-in-progress'                      => 'Suppression du répertoire en cours',
                    'can-not-copy'                              => 'Le répertoire ne peut pas être copié car il s\'agit du répertoire racine.',
                    'coping-in-progress'                        => 'Copie de la structure du répertoire en cours.',
                    'asset-not-found'                           => 'Aucun actif trouvé',
                    'asset-renamed-success'                     => 'Actif renommé avec succès',
                    'asset-moved-success'                       => 'Actif déplacé avec succès',
                    'asset-name-already-exist'                  => 'Le nouveau nom existe déjà pour un autre actif nommé :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Le nom de l\'actif entre en conflit avec un fichier existant dans le même répertoire.',
                    'old-file-not-found'                        => 'Le fichier demandé à l\'emplacement :old_path n\'a pas été trouvé.',
                    'image-name-is-the-same'                    => 'Ce nom existe déjà. Veuillez en saisir un autre.',
                    'not-writable'                              => 'Vous n\'êtes pas autorisé à :actionType un :type dans cet emplacement ":path".',
                    'empty-directory'                           => 'Ce répertoire est vide.',
                    'failed-download-directory'                 => 'Échec de la création du fichier zip.',
                    'not-allowed'                               => 'Le téléchargement de fichiers de script n\'est pas autorisé.',
                ],

                'title'       => 'DAM',
                'description' => 'L\'outil peut vous aider à organiser, stocker et gérer tous vos actifs multimédias au même endroit',
                'root'        => 'Racine',
                'upload'      => 'Télécharger',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Propriétés des Actifs',
                        'create-btn' => 'Créer une Propriété',

                        'datagrid'      => [
                            'name'     => 'Nom',
                            'type'     => 'Type',
                            'language' => 'Langue',
                            'value'    => 'Valeur',
                            'edit'     => 'Éditer',
                            'delete'   => 'Supprimer',
                        ],

                        'create'     => [
                            'title'    => 'Créer une Propriété',
                            'name'     => 'Nom',
                            'type'     => 'Type',
                            'language' => 'Langue',
                            'value'    => 'Valeur',
                            'save-btn' => 'Enregistrer',
                        ],
                        'edit' => [
                            'title' => 'Éditer la Propriété',
                        ],
                        'delete-success' => 'Propriété de l\'actif supprimée avec succès',
                        'create-success' => 'Propriété de l\'actif créée avec succès',
                        'update-success' => 'Propriété de l\'actif mise à jour avec succès',
                        'not-found'      => 'Propriété non trouvée',
                        'found-success'  => 'Propriété trouvée avec succès',

                    ],
                ],
                'comments' => [
                    'index'  => 'Ajouter un Commentaire',
                    'create' => [
                        'create-success' => 'Le commentaire a été ajouté avec succès',
                        'create-failure' => 'Échec de la création du commentaire',
                    ],
                    'post-comment'    => 'Publier un Commentaire',
                    'post-reply'      => 'Publier une Réponse',
                    'reply'           => 'Répondre',
                    'add-reply'       => 'Ajouter une Réponse',
                    'add-comment'     => 'Ajouter un Commentaire',
                    'no-comments'     => 'Aucun Commentaire pour le moment',
                    'not-found'       => 'Commentaires non trouvés',
                    'updated-success' => 'Commentaire mis à jour avec succès',
                    'update-failed'   => 'Échec de la mise à jour du commentaire',
                    'delete-success'  => 'Commentaire de l\'actif supprimé avec succès',
                    'delete-failed'   => 'Échec de la suppression du commentaire de l\'actif',
                ],
                'edit' => [
                    'title'              => 'Modifier l\'Actif',
                    'name'               => 'Nom',
                    'value'              => 'Valeur',
                    'back-btn'           => 'Retour',
                    'save-btn'           => 'Enregistrer',
                    'embedded_meta_info' => 'Informations Métadonnées Embeddées',
                    'custom_meta_info'   => 'Informations Métadonnées Personnalisées',
                    'tags'               => 'Étiquettes',
                    'select-tags'        => 'Choisir ou Créer une Étiquette',
                    'tag'                => 'Étiquette',
                    'directory-path'     => 'Chemin du Répertoire',
                    'add_tags'           => 'Ajouter des Étiquettes',
                    'tab'                => [
                        'preview'          => 'Aperçu',
                        'properties'       => 'Propriétés',
                        'comments'         => 'Commentaires',
                        'linked_resources' => 'Ressources Liées',
                        'history'          => 'Historique',
                    ],
                    'button' => [
                        'download'        => 'Télécharger',
                        'custom_download' => 'Téléchargement Personnalisé',
                        'rename'          => 'Renommer',
                        're_upload'       => 'Re-Télécharger',
                        'delete'          => 'Supprimer',
                    ],

                    'custom-download' => [
                        'title'              => 'Téléchargement Personnalisé',
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
                            'original' => 'Original',
                        ],
                    ],

                    'tag-already-exists'        => 'L\'étiquette existe déjà',
                    'image-source-not-readable' => 'Source de l\'image non lisible',
                    'failed-to-read'            => 'Échec de la lecture des métadonnées de l\'image :exception',
                    'file-re-upload-success'    => 'Fichiers re-téléchargés avec succès.',
                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Produit',
                            'category'      => 'Catégorie',
                            'product-sku'   => 'Produit Sku: ',
                            'category code' => 'Code de la Catégorie: ',
                            'resource-type' => 'Type de Ressource',
                            'resource'      => 'Ressource',
                            'resource-view' => 'Vue de la Ressource',
                        ],
                    ],
                    'found-success' => 'Ressource trouvée avec succès',
                    'not-found'     => 'Ressource non trouvée',
                ],
                'tags' => [
                    'index'  => 'Ajouter des étiquettes',
                    'create' => [
                        'create-success' => 'Les étiquettes ont été ajoutées avec succès',
                        'create-failure' => 'L\'ajout des étiquettes a échoué',
                    ],

                    'no-comments'    => 'Pas encore d\'étiquettes',
                    'found-success'  => 'Étiquette trouvée avec succès',
                    'not-found'      => 'Étiquettes non trouvées',
                    'update-success' => 'Étiquettes mises à jour avec succès',
                    'update-failed'  => 'Échec de la mise à jour des étiquettes',
                    'delete-success' => 'Étiquettes de l’actif supprimées avec succès',
                    'delete-failed'  => 'Échec de la suppression des étiquettes d\'actif',
                ],
                'delete-success'                          => 'Actif supprimé avec succès',
                'delete-failed-due-to-attached-resources' => 'La ressource est utilisée. Détachez-la avant de supprimer',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Suppression en masse réussie.',
                    'files-upload-success'                => 'Fichiers téléchargés avec succès.',
                    'file-upload-success'                 => 'Fichier téléchargé avec succès.',
                    'not-found'                           => 'Fichier non trouvé',
                    'edit-success'                        => 'Fichier mis à jour avec succès',
                    'show-success'                        => 'Fichier trouvé avec succès',
                    'update-success'                      => 'Fichier mis à jour avec succès',
                    'not-found-to-update'                 => 'Le fichier n\'existe pas',
                    'not-found-to-destroy'                => 'Le fichier n\'existe pas',
                    'files-upload-failed'                 => 'Échec du téléchargement des fichiers.',
                    'file-upload-failed'                  => 'Échec du téléchargement du fichier',
                    'invalid-file'                        => 'Fichier invalide fourni',
                    'invalid-file-format'                 => 'Format invalide',
                    'invalid-file-format-or-not-provided' => 'Aucun fichier fourni ou format invalide.',
                    'download-image-failed'               => 'Échec du téléchargement de l\'image depuis l\'URL',
                    'file-process-failed'                 => 'Certains fichiers n\'ont pas pu être traités',
                    'file-forbidden-type'                 => 'Le fichier a un type ou une extension interdite.',
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
            'linked_resources' => 'Ressources Liées',
            'directory'        => 'Répertoire',
            'tag'              => 'Étiquette',
            'create'           => 'Créer',
            'edit'             => 'Modifier',
            'update'           => 'Mettre à jour',
            'delete'           => 'Supprimer',
            'list'             => 'Liste',
            'view'             => 'Voir',
            'upload'           => 'Télécharger',
            're_upload'        => 'Re-Télécharger',
            'mass_update'      => 'Mise à jour en masse',
            'mass_delete'      => 'Suppression en masse',
            'download'         => 'Télécharger',
            'custom_download'  => 'Téléchargement personnalisé',
            'rename'           => 'Renommer',
            'move'             => 'Déplacer',
            'copy'             => 'Copier',
            'copy-structure'   => 'Copier la structure du répertoire',
            'download-zip'     => 'Télécharger le Zip',
            'asset-assign'     => 'Attribuer un Actif',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Le champ :attribute est requis.',
            ],

            'comment' => [
                'required' => 'Le message du commentaire est requis.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Le champ Étiquette est requis.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Le champ Nom est requis.',
                    'unique'   => 'Le Nom est déjà pris.',
                ],
                'language' => [
                    'not-found' => 'La langue sélectionnée est introuvable ou actuellement désactivée.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Cette action est non autorisée.',
        ],
    ],
];
