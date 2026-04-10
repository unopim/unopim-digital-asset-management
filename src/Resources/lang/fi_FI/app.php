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
                    'message' => 'Tämän hakemiston poistaminen poistaa myös kaikki sen sisällä olevat alihakemistot. Tämä toiminto on pysyvä eikä sitä voi kumota.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Lisää resurssi',
                    'assign-assets' => 'Liitä resursseja',
                    'assign'        => 'Liitä',
                    'preview-asset' => 'Esikatsele resurssia',
                    'preview'       => 'Esikatselu',
                    'remove'        => 'Poista',
                    'download'      => 'Lataa',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Tiedoston nimi',
                    'tags'           => 'Tunnisteet',
                    'property-name'  => 'Ominaisuuden nimi',
                    'property-value' => 'Ominaisuuden arvo',
                    'created-at'     => 'Luotu',
                    'updated-at'     => 'Päivitetty',
                    'extension'      => 'Tiedostopääte',
                    'path'           => 'Polku',
                    'size'           => 'Koko',
                ],

                'directory' => [
                    'title'        => 'Hakemisto',
                    'create'       => [
                        'title'    => 'Luo hakemisto',
                        'name'     => 'Nimi',
                        'save-btn' => 'Tallenna hakemisto',
                    ],

                    'rename' => [
                        'title' => 'Nimeä hakemisto uudelleen',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Nimeä resurssi uudelleen',
                            'save-btn' => 'Tallenna resurssi',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Poista',
                        'rename'                    => 'Nimeä uudelleen',
                        'copy'                      => 'Kopioi',
                        'download'                  => 'Lataa',
                        'download-zip'              => 'Lataa zip',
                        'paste'                     => 'Liitä',
                        'add-directory'             => 'Lisää hakemisto',
                        'upload-files'              => 'Lähetä tiedostoja',
                        'copy-directory-structured' => 'Kopioi hakemistorakenne',
                        'get-by-id'                 => 'Hae tunnuksella',
                        'comment'                   => 'Kommentti',
                    ],

                    'linked-resources'                          => 'Linkitetyt resurssit',
                    'not-found'                                 => 'Hakemistoa ei löytynyt',
                    'created-success'                           => 'Hakemisto luotu onnistuneesti',
                    'updated-success'                           => 'Hakemisto päivitetty onnistuneesti',
                    'moved-success'                             => 'Hakemisto siirretty onnistuneesti',
                    'fetch-all-success'                         => 'Hakemistot haettu onnistuneesti',
                    'can-not-deleted'                           => 'Hakemistoa ei voi poistaa, koska se on juurihakemisto.',
                    'deleting-in-progress'                      => 'Hakemiston poistaminen on käynnissä',
                    'can-not-copy'                              => 'Hakemistoa ei voi kopioida, koska se on juurihakemisto.',
                    'coping-in-progress'                        => 'Hakemistorakenteen kopiointi on käynnissä.',
                    'asset-not-found'                           => 'Resurssia ei löytynyt',
                    'asset-renamed-success'                     => 'Resurssi nimetty uudelleen onnistuneesti',
                    'asset-moved-success'                       => 'Resurssi siirretty onnistuneesti',
                    'asset-name-already-exist'                  => 'Uusi nimi on jo käytössä toisella resurssilla nimeltä :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Resurssin nimi on ristiriidassa olemassa olevan tiedoston kanssa samassa hakemistossa.',
                    'old-file-not-found'                        => 'Polusta :old_path pyydettyä tiedostoa ei löytynyt.',
                    'image-name-is-the-same'                    => 'Tämä nimi on jo olemassa. Syötä toinen nimi.',
                    'not-writable'                              => 'Sinulla ei ole oikeutta suorittaa toimintoa :actionType kohteelle :type sijainnissa ":path".',
                    'empty-directory'                           => 'Tämä hakemisto on tyhjä.',
                    'failed-download-directory'                 => 'Zip-tiedoston luominen epäonnistui.',
                    'not-allowed'                               => 'Komentosarjatiedostojen lähettäminen ei ole sallittua.',
                ],

                'title'       => 'DAM',
                'description' => 'Työkalu auttaa sinua järjestämään, tallentamaan ja hallitsemaan kaikkia mediaresurssejasi yhdessä paikassa',
                'root'        => 'Juuri',
                'upload'      => 'Lähetä',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Resurssin ominaisuudet',
                        'create-btn' => 'Luo ominaisuus',

                        'datagrid'      => [
                            'name'     => 'Nimi',
                            'type'     => 'Tyyppi',
                            'language' => 'Kieli',
                            'value'    => 'Arvo',
                            'edit'     => 'Muokkaa',
                            'delete'   => 'Poista',
                        ],

                        'create'     => [
                            'title'    => 'Luo ominaisuus',
                            'name'     => 'Nimi',
                            'type'     => 'Tyyppi',
                            'language' => 'Kieli',
                            'value'    => 'Arvo',
                            'save-btn' => 'Tallenna',
                        ],
                        'edit' => [
                            'title' => 'Muokkaa ominaisuutta',
                        ],
                        'delete-success' => 'Resurssin ominaisuus poistettu onnistuneesti',
                        'create-success' => 'Resurssin ominaisuus luotu onnistuneesti',
                        'update-success' => 'Resurssin ominaisuus päivitetty onnistuneesti',
                        'not-found'      => 'Ominaisuutta ei löytynyt',
                        'found-success'  => 'Ominaisuus löydetty onnistuneesti',
                    ],
                ],
                'comments' => [
                    'index'  => 'Lisää kommentti',
                    'create' => [
                        'create-success' => 'Kommentti lisätty onnistuneesti',
                        'create-failure' => 'Kommentin luominen epäonnistui',
                    ],
                    'post-comment'    => 'Julkaise kommentti',
                    'post-reply'      => 'Julkaise vastaus',
                    'reply'           => 'Vastaa',
                    'add-reply'       => 'Lisää vastaus',
                    'add-comment'     => 'Lisää kommentti',
                    'no-comments'     => 'Ei vielä kommentteja',
                    'not-found'       => 'Kommentteja ei löytynyt',
                    'updated-success' => 'Kommentti päivitetty onnistuneesti',
                    'update-failed'   => 'Kommentin päivittäminen epäonnistui',
                    'delete-success'  => 'Resurssin kommentti poistettu onnistuneesti',
                    'delete-failed'   => 'Resurssin kommentin poistaminen epäonnistui',
                ],
                'edit' => [
                    'title'              => 'Muokkaa resurssia',
                    'name'               => 'Nimi',
                    'value'              => 'Arvo',
                    'back-btn'           => 'Takaisin',
                    'save-btn'           => 'Tallenna',
                    'embedded_meta_info' => 'Upotetut metatiedot',
                    'custom_meta_info'   => 'Mukautetut metatiedot',
                    'tags'               => 'Tunnisteet',
                    'select-tags'        => 'Valitse tai luo tunniste',
                    'tag'                => 'Tunniste',
                    'directory-path'     => 'Hakemistopolku',
                    'add_tags'           => 'Lisää tunnisteita',
                    'tab'                => [
                        'preview'          => 'Esikatselu',
                        'properties'       => 'Ominaisuudet',
                        'comments'         => 'Kommentit',
                        'linked_resources' => 'Linkitetyt resurssit',
                        'history'          => 'Historia',
                    ],
                    'button' => [
                        'download'        => 'Lataa',
                        'custom_download' => 'Mukautettu lataus',
                        'rename'          => 'Nimeä uudelleen',
                        're_upload'       => 'Lähetä uudelleen',
                        'delete'          => 'Poista',
                    ],

                    'custom-download' => [
                        'title'              => 'Mukautettu lataus',
                        'format'             => 'Muoto',
                        'width'              => 'Leveys (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Korkeus (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Lataa',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Alkuperäinen',
                        ],
                    ],

                    'tag-already-exists'        => 'Tunniste on jo olemassa',
                    'image-source-not-readable' => 'Kuvan lähdettä ei voi lukea',
                    'failed-to-read'            => 'Kuvan metatietojen lukeminen epäonnistui :exception',
                    'file-re-upload-success'    => 'Tiedostot lähetetty uudelleen onnistuneesti.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Tuote',
                            'category'      => 'Kategoria',
                            'product-sku'   => 'Tuotteen Sku: ',
                            'category code' => 'Kategorian koodi: ',
                            'resource-type' => 'Resurssin tyyppi',
                            'resource'      => 'Resurssi',
                            'resource-view' => 'Resurssin näkymä',
                        ],
                    ],
                    'found-success' => 'Resurssi löydetty onnistuneesti',
                    'not-found'     => 'Resurssia ei löytynyt',
                ],
                'tags' => [
                    'index'  => 'Lisää tunnisteita',
                    'create' => [
                        'create-success' => 'Tunnisteet lisätty onnistuneesti',
                        'create-failure' => 'Tunnisteiden luominen epäonnistui',
                    ],

                    'no-comments'    => 'Ei vielä tunnisteita',
                    'found-success'  => 'Tunniste löydetty onnistuneesti',
                    'not-found'      => 'Tunnisteita ei löytynyt',
                    'update-success' => 'Tunnisteet päivitetty onnistuneesti',
                    'update-failed'  => 'Tunnisteiden päivittäminen epäonnistui',
                    'delete-success' => 'Resurssin tunnisteet poistettu onnistuneesti',
                    'delete-failed'  => 'Resurssin tunnisteiden poistaminen epäonnistui',
                ],
                'delete-success'                          => 'Resurssi poistettu onnistuneesti',
                'delete-failed-due-to-attached-resources' => 'Resurssi on käytössä. Poista linkitys ennen poistamista',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Joukkopoisto onnistui.',
                    'files-upload-success'                => 'Tiedostot lähetetty onnistuneesti.',
                    'file-upload-success'                 => 'Tiedosto lähetetty onnistuneesti.',
                    'not-found'                           => 'Tiedostoa ei löytynyt',
                    'edit-success'                        => 'Tiedosto lähetetty onnistuneesti',
                    'show-success'                        => 'Tiedosto löydetty onnistuneesti',
                    'update-success'                      => 'Tiedosto päivitetty onnistuneesti',
                    'not-found-to-update'                 => 'Tiedostoa ei ole olemassa',
                    'not-found-to-destroy'                => 'Tiedostoa ei ole olemassa',
                    'files-upload-failed'                 => 'Tiedostojen lähettäminen epäonnistui.',
                    'file-upload-failed'                  => 'Tiedoston lähettäminen epäonnistui',
                    'invalid-file'                        => 'Virheellinen tiedosto',
                    'invalid-file-format'                 => 'Virheellinen muoto',
                    'invalid-file-format-or-not-provided' => 'Tiedostoja ei annettu tai muoto on virheellinen.',
                    'download-image-failed'               => 'Kuvan lataaminen URL-osoitteesta epäonnistui',
                    'file-process-failed'                 => 'Joidenkin tiedostojen käsittely epäonnistui',
                    'file-forbidden-type'                 => 'Tiedoston tyyppi tai tiedostopääte on kielletty.',
                    'file-too-large'                      => 'Tiedosto on liian suuri. Suurin sallittu koko on :size.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Resurssi',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Resurssi',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Resurssi',
            'property'         => 'Ominaisuus',
            'comment'          => 'Kommentti',
            'linked_resources' => 'Linkitetyt resurssit',
            'directory'        => 'Hakemisto',
            'tag'              => 'Tunniste',
            'create'           => 'Luo',
            'edit'             => 'Muokkaa',
            'update'           => 'Päivitä',
            'delete'           => 'Poista',
            'list'             => 'Luettelo',
            'view'             => 'Näytä',
            'upload'           => 'Lähetä',
            're_upload'        => 'Lähetä uudelleen',
            'mass_update'      => 'Joukkopäivitys',
            'mass_delete'      => 'Joukkopoisto',
            'download'         => 'Lataa',
            'custom_download'  => 'Mukautettu lataus',
            'rename'           => 'Nimeä uudelleen',
            'move'             => 'Siirrä',
            'copy'             => 'Kopioi',
            'copy-structure'   => 'Kopioi hakemistorakenne',
            'download-zip'     => 'Lataa zip',
            'asset-assign'     => 'Liitä resurssi',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Kenttä :attribute on pakollinen.',
            ],

            'comment' => [
                'required' => 'Kommenttiviesti on pakollinen.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Tunnistekenttä on pakollinen.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Nimikenttä on pakollinen.',
                    'unique'   => 'Nimi on jo käytössä.',
                ],
                'language' => [
                    'not-found' => 'Valittua kieltä ei löytynyt tai se on tällä hetkellä poistettu käytöstä.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Tähän toimintoon ei ole valtuutusta.',
        ],
    ],
];
