<?php


namespace oog\uitzendingen;


class Migrate
{
    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function doMigrate()
    {

        $programs = get_terms( array(
            'taxonomy' => Uitzending::TAXONOMY_PROGRAMME,
            'hide_empty' => false,
        ) );

        // Map of old field names and their corresponding ACF field
        $fieldMap = [
            'postername' => 'author',
            'cameraman' => 'camera',
            'sprekers' => 'speakers',
            'description' => 'description',
            'koppel' => 'related_post'
        ];

        $results = $this->wpdb->get_results(
            'SELECT ou.*, ouc.catname FROM o2_oogtv_uitzendingen ou
            LEFT JOIN o2_oogtv_uitzendingen_categories ouc ON ou.catid = ouc.id
            WHERE mediatype > 0', 'ARRAY_A');

        array_walk($results, function ($item) use ($programs, $fieldMap) {
            $add = ($item['mediatype'] == 5 && strlen($item['youtube'] > 2) || $item['mediatype'] == 4);
            if(!$add) {
                return;
            }

            $type = $item['mediatype'] == 5 ? Uitzending::POST_TYPE_TV : Uitzending::POST_TYPE_RADIO;

            $post = [
                'post_title' => $item['titletext'],
                'post_type' => $type,
                'post_date' => $item['date'],
                'post_status' => 'publish',
                'tags_input' => explode(',', $item['tags'])
            ];

            $post_id = wp_insert_post( $post );

            if($post_id) {
                echo sprintf("inserted post %s\n", $item['titletext']);
                array_walk($programs, function (\WP_Term $program) use ($post_id, $item) {
                    if($program->name === $item['catname']) {
                        wp_set_object_terms($post_id, $program->slug, Uitzending::TAXONOMY_PROGRAMME);
                    }
                });

                foreach($fieldMap as $ouField => $acfField) {
                    update_field($acfField, $item[$ouField], $post_id);
                }

                if($type === Uitzending::POST_TYPE_TV) {
                    update_field('youtube_video', $item['youtube'], $post_id);
                } else {

                    $file = $item['maintext'] ? $item['maintext'] : $item['rawurl'];

                    update_field('filename', $file, $post_id);
                }
            }

        });

    }
}