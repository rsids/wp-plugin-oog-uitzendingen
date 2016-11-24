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

    function __construct()
    {
        $this->admin = new Admin();
        $this->admin->addActionsAndHooks();

        register_activation_hook(OOG_UITZENDINGEN_PLUGIN_FILE, [$this, 'activation']);
        register_deactivation_hook(OOG_UITZENDINGEN_PLUGIN_FILE, [$this, 'deactivation']);
        add_action('init', [$this, 'registerPostsAndTaxonomies']);
        add_action('admin_init', [$this->admin, 'init']);

    }

    public function activation()
    {
        $this->createTaxonomies = true;
        add_option('oog-uitzending-activated', 1);
        add_action('init', [$this, 'createDefaultTaxonomies']);
    }

    /**
     * @return boolean
     */
    public function deactivation()
    {
        delete_option('oog-uitzending-create-taxonomies');
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

        register_taxonomy(Uitzending::TAXONOMY_PROGRAMME, Uitzending::POST_TYPE_TV, $taxSettings);
        register_taxonomy(Uitzending::TAXONOMY_PROGRAMME, Uitzending::POST_TYPE_RADIO, $taxSettings);
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
                'name' => __('Uitzendingen'),
                'singular_name' => __('TV Uitzending'),
                'add_new' => __('Nieuwe uitzending'),
                'edit_item' => __('Uitzending bewerken'),
                'view_item' => __('Uitzending bekijken'),
                'not_found' => __('Geen uitzendingen gevonden')
            ],
            'rewrite' => ['slug' => get_option('oog-uitzendingen-tv-slug', 'radio')]
        ]);

        $radioSettings = array_merge($postSettings, [
            'labels' => [
                'name' => __('Radio Uitzending'),
                'singular_name' => __('Radio Uitzending'),
                'add_new' => __('Nieuwe uitzending'),
                'edit_item' => __('Uitzending bewerken'),
                'view_item' => __('Uitzending bekijken'),
                'not_found' => __('Geen uitzendingen gevonden')
            ],
            'show_in_menu' => 'edit.php?post_type=' . Uitzending::POST_TYPE_TV,
            'rewrite' => ['slug' => get_option('oog-uitzendingen-tv-slug', 'tv')]
        ]);

        register_post_type(Uitzending::POST_TYPE_RADIO, $radioSettings);
        register_post_type(Uitzending::POST_TYPE_TV, $tvSettings);


        flush_rewrite_rules(false);
    }

}