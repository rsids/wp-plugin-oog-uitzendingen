<?php
/**
 * Created by PhpStorm.
 * User: ids
 * Date: 10-11-16
 * Time: 9:35
 */

namespace oog\uitzendingen;

use oog\uitzendingen\admin\Admin;

class Bootstrap
{

    private $createTaxonomies = false;
    private $admin;
    private $filters;
    private $archive;

    function __construct()
    {
        $this->admin = new Admin();
        $this->admin->addActionsAndHooks();

        $this->setupACF();

        $this->filters = new Filters();
        $this->filters->init();
        $this->archive = new Archive();

        register_activation_hook(OOG_UITZENDINGEN_PLUGIN_FILE, [$this, 'activation']);
        register_deactivation_hook(OOG_UITZENDINGEN_PLUGIN_FILE, [$this, 'deactivation']);
        add_action('init', [$this, 'registerPostsAndTaxonomies']);
        add_action('admin_init', [$this->admin, 'init']);
        add_action('widgets_init', function(){
            register_widget( '\\oog\\uitzendingen\\widgets\\LatestEpisodeWidget' );
        });

    }

    public function activation()
    {
        $this->createTaxonomies = true;
        update_option('oog-uitzending-activated', 1);
        add_action('init', [$this, 'createDefaultTaxonomies']);
    }

    /**
     * @return boolean
     */
    public function deactivation()
    {
        delete_option('oog-uitzending-activated');
    }

    public function registerPostsAndTaxonomies()
    {
        $this->registerTaxonomies();
        $this->registerPosts();
        if (get_option('oog-uitzending-activated', 0) == 1) {
            $this->createDefaultTaxonomies();
        }

    }

    private function createDefaultTaxonomies()
    {
        delete_option('oog-uitzending-activated');

        $programmes = Uitzending::GetDefaultProgrammes();

        if (!term_exists($programmes[0], Uitzending::TAXONOMY_PROGRAMME)) {

            foreach ($programmes as $programme) {
                wp_insert_term(
                    $programme,
                    Uitzending::TAXONOMY_PROGRAMME);
            }
        }
    }

    private function registerTaxonomies()
    {
        $taxSettings = ['hierarchical' => true,

            'labels' => [
                'name' => 'Programma',
                'singular_name' => 'Programma',
                'menu_name' => 'Programma\'s',
            ],
            'public' => true,
            // Control the slugs used for this taxonomy
            'rewrite' => [
                'slug' => 'programma', // This controls the base slug that will display before each term
                'with_front' => false // Don't display the category base before "/locations/"
            ]
        ];

        $taxCategorySettings = ['hierarchical' => true,

            'labels' => [
                'name' => 'Categorie',
                'singular_name' => 'Categorie',
                'menu_name' => 'Categorieën',
            ],
            'public' => true,
            // Control the slugs used for this taxonomy
            'rewrite' => [
                'slug' => 'categorie', // This controls the base slug that will display before each term
                'with_front' => false // Don't display the category base before "/locations/"
            ]
        ];

        register_taxonomy(Uitzending::TAXONOMY_PROGRAMME, Uitzending::POST_TYPE_TV, $taxSettings);
        register_taxonomy(Uitzending::TAXONOMY_PROGRAMME, Uitzending::POST_TYPE_RADIO, $taxSettings);

        register_taxonomy(Uitzending::TAXONOMY_CATEGORY, Uitzending::POST_TYPE_TV, $taxCategorySettings);
        register_taxonomy(Uitzending::TAXONOMY_CATEGORY, Uitzending::POST_TYPE_RADIO, $taxCategorySettings);
    }

    private function registerPosts()
    {
        $postSettings = [

            'public' => true,
            'supports' => ['title', 'editor', 'author', 'thumbnail'],
            'taxonomies' => [
                Uitzending::TAXONOMY_PROGRAMME,
                Uitzending::TAXONOMY_CATEGORY,
                'post_tag'
            ],
            'show_in_nav_menus' => false,
            'menu_position' => 100,
            'map_meta_cap' => true,
            'menu_icon' => 'dashicons-format-video',
            'has_archive' => true,
        ];

        $tvSettings = array_merge($postSettings, [
            'labels' => [
                'menu_name' => __('Uitzendingen'),
                'name' => __('TV Uitzendingen'),
                'singular_name' => __('TV Uitzending'),
                'add_new' => __('Nieuwe tv uitzending'),
                'edit_item' => __('Uitzending bewerken'),
                'view_item' => __('Uitzending bekijken'),
                'not_found' => __('Geen uitzendingen gevonden')
            ],
            'rewrite' => [
                'slug' => get_option('oog-uitzendingen-tv-slug', 'uitzending-gemist/tv')
            ]
        ]);

        $radioSettings = array_merge($postSettings, [
            'labels' => [
                'name' => __('Radio Uitzending'),
                'singular_name' => __('Radio Uitzending'),
                'add_new' => __('Nieuwe radio uitzending'),
                'edit_item' => __('Uitzending bewerken'),
                'view_item' => __('Uitzending bekijken'),
                'not_found' => __('Geen uitzendingen gevonden')
            ],
            'show_in_menu' => 'edit.php?post_type=' . Uitzending::POST_TYPE_TV,
            'rewrite' => [
                'slug' => get_option('oog-uitzendingen-tv-slug', 'uitzending-gemist/radio')
            ]
        ]);

        register_post_type(Uitzending::POST_TYPE_RADIO, $radioSettings);
        register_post_type(Uitzending::POST_TYPE_TV, $tvSettings);


        flush_rewrite_rules(false);
    }

    private function setupACF()
    {
        if (function_exists('acf_add_local_field_group')) {

            acf_add_local_field_group([
                'key' => 'group_58360c21ea795',
                'title' => 'radio',
                'fields' => [
                    [
                        'key' => 'field_58360c2785665',
                        'label' => 'Bestandsnaam',
                        'name' => 'filename',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'uitzending-radio',
                        ],
                    ],
                ],
                'menu_order' => 0,
                'position' => 'acf_after_title',
                'style' => 'seamless',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => [
                    0 => 'the_content',
                    1 => 'excerpt',
                    2 => 'discussion',
                    3 => 'comments',
                    4 => 'revisions',
                    5 => 'format',
                    6 => 'page_attributes',
                    7 => 'featured_image',
                    8 => 'send-trackbacks',
                ],
                'active' => 1,
                'description' => '',
            ]);

            acf_add_local_field_group([
                'key' => 'group_58360a6e8c3e5',
                'title' => 'youtube',
                'fields' => [
                    [
                        'key' => 'field_58360a6f17637',
                        'label' => 'Youtube Video',
                        'name' => 'youtube_video',
                        'type' => 'select',
                        'instructions' => 'Selecteer de Youtube video',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'choices' => [
                            0 => '-- Kies een Youtube Video --',
                        ],
                        'default_value' => [],
                        'allow_null' => 0,
                        'multiple' => 0,
                        'ui' => 0,
                        'ajax' => 0,
                        'placeholder' => '',
                        'return_format' => 'value',
                    ],
                    [
                        'key' => 'field_58360a6f176bc',
                        'label' => 'Youtube Categorie',
                        'name' => 'youtube_category',
                        'type' => 'select',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_58360a6f17637',
                                    'operator' => '!=',
                                    'value' => '0',
                                ],
                            ],
                        ],
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'choices' => [
                            1 => 'Film & animatie',
                            2 => 'Auto\'s & voertuigen',
                            10 => 'Muziek',
                            15 => 'Huisdieren & dieren',
                            17 => 'Sport',
                            19 => 'Reizen en evenementen',
                            20 => 'Games',
                            22 => 'Mensen & blogs',
                            23 => 'Humor',
                            24 => 'Amusement',
                            25 => 'Nieuws & politiek',
                            26 => 'Zo-doe-je-dat en stijl',
                            27 => 'Onderwijs',
                            28 => 'Wetenschap en technologie',
                        ],
                        'default_value' => [25],
                        'allow_null' => 0,
                        'multiple' => 0,
                        'ui' => 0,
                        'ajax' => 0,
                        'return_format' => 'value',
                        'placeholder' => '',
                    ],
                    [
                        'key' => 'field_58360a6f17775',
                        'label' => 'Externe Youtube video',
                        'name' => 'external_youtube_video',
                        'type' => 'text',
                        'instructions' => 'Youtube video ID',
                        'required' => 0,
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_58360a6f17637',
                                    'operator' => '==',
                                    'value' => '0',
                                ],
                            ],
                        ],
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'uitzending-tv',
                        ],
                    ],
                ],
                'menu_order' => 0,
                'position' => 'acf_after_title',
                'style' => 'seamless',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => [
                    0 => 'permalink',
                    1 => 'the_content',
                    2 => 'excerpt',
                    3 => 'discussion',
                    4 => 'comments',
                    5 => 'revisions',
                    6 => 'slug',
                    7 => 'author',
                    8 => 'format',
                    9 => 'page_attributes',
                    10 => 'featured_image',
                    11 => 'send-trackbacks',
                ],
                'active' => 1,
                'description' => '',
            ]);

            acf_add_local_field_group([
                'key' => 'group_5830c3f13ab19',
                'title' => 'uitzending',
                'fields' => [
                    [
                        'key' => 'field_5830c63d280ff',
                        'label' => 'Verslaggever',
                        'name' => 'author',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ],
                    [
                        'key' => 'field_5831f22dc57ba',
                        'label' => 'Editor',
                        'name' => 'editor',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ],
                    [
                        'key' => 'field_5830c65528100',
                        'label' => 'Cameraman',
                        'name' => 'camera',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ],
                    [
                        'key' => 'field_5830c67028101',
                        'label' => 'Sprekers',
                        'name' => 'speakers',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ],
                    [
                        'key' => 'field_58317b25928a3',
                        'label' => 'Omschrijving',
                        'name' => 'description',
                        'type' => 'textarea',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'default_value' => '',
                        'placeholder' => '',
                        'maxlength' => '',
                        'rows' => '',
                        'new_lines' => 'wpautop',
                    ],
                    [
                        'key' => 'field_5825e4d98d887',
                        'label' => 'Gekoppeld Artikel',
                        'name' => 'related_post',
                        'type' => 'relationship',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'return_format' => 'object',
                        'post_type' => [
                            0 => 'post',
                        ],
                        'taxonomy' => [],
                        'filters' => [
                            0 => 'search',
                        ],
                        'result_elements' => '',
                        'max' => 1,
                        'min' => 0,
                        'elements' => [],
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'uitzending-tv',
                        ],
                    ],
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'uitzending-radio',
                        ],
                    ],
                ],
                'menu_order' => 1,
                'position' => 'acf_after_title',
                'style' => 'seamless',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => [
                    0 => 'permalink',
                    1 => 'the_content',
                    2 => 'excerpt',
                    3 => 'discussion',
                    4 => 'comments',
                    5 => 'revisions',
                    6 => 'slug',
                    7 => 'author',
                    8 => 'format',
                    9 => 'page_attributes',
                    10 => 'featured_image',
                    11 => 'send-trackbacks',
                ],
                'active' => 1,
                'description' => '',
            ]);

        } else {
            // Missing ACF plugin
        }
    }

}