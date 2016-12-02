<?php


namespace oog\uitzendingen\admin;


use oog\uitzendingen\Uitzending;
use oog\uitzendingen\Youtube;

class EditUitzending
{
    public function __construct()
    {
        if (!defined('OOG_UITZENDINGEN_CLI_MODE')) {
            add_filter('acf/load_field/name=youtube_video', [$this, 'loadPrivateYoutubeVideos'], 10);
            add_filter('acf/load_field/name=youtube_category', [$this, 'loadYoutubeCategories'], 10);
            add_filter('acf/prepare_field/name=youtube_video', [$this, 'prepareYTVideo'], 10);
            add_filter('wp_insert_post_data', [$this, 'preSavePost'], '99', 2);
            add_action('acf/save_post', [$this, 'savePost'], 20);
        }
    }

    public function prepareYTVideo($field)
    {
        if ($field['value']) {

            if (!array_key_exists($field['value'], $field['choices'])) {
                $field['choices'][$field['value']] = $field['value'] . ': -- ongewijzigd -- ';
            }
        }
        return $field;
    }

    /**
     * Before saving, set the title & content from the related post
     * @param $data
     * @param $postArr
     * @return mixed
     */
    public function preSavePost($data, $postArr)
    {

        $relatedPost = get_field('related_post', $postArr['ID']);
        if ($relatedPost) {
            $relatedPost = $relatedPost[0];
            $data['post_title'] = $data['post_title'] !== '' ? $data['post_title'] : $relatedPost->post_title;
            if ($data['post_content'] === '') {
                $data['post_content'] = str_replace('<!--more-->', '', $relatedPost->post_content);
            }
        }

        return $data;

    }

    public function loadYoutubeCategories($field)
    {
        $cats = get_option(Uitzending::OPTION_CATEGORIES);
        if ($cats) {
            $cats = json_decode($cats);
            array_walk($cats, function ($cat) use (&$field) {
                $field['choices'][$cat->id] = $cat->title;
            });
        }

        return $field;
    }

    public function loadPrivateYoutubeVideos($field)
    {
        $youtube = new Youtube();
        $field['choices'] = [0 => '-- Kies een Youtube Video --'];
        $field['choices'] = array_merge($field['choices'], $youtube->getVideos(true));

        return $field;
    }

    /**
     * Save post hook, called when ANY post is saved. Checks if post is TV broadcast,
     * updates youtube vid with title, description, tags & category
     * @param $post_id
     */
    public function savePost($post_id)
    {
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        if (Uitzending::POST_TYPE_TV !== get_post_type($post_id)) {
            return;
        }

        $programme = wp_get_post_terms( $post_id, Uitzending::TAXONOMY_PROGRAMME );

        if(empty($programme)) {
            wp_set_object_terms( $post_id, 'nieuws', Uitzending::TAXONOMY_PROGRAMME );
        }

        if ('publish' === get_post_status($post_id)) {

            if (get_field('youtube_video', $post_id)) {
                $tags = get_the_tags($post_id);
                $meta = get_fields($post_id);
                $meta['title'] = get_the_title($post_id);
                $meta['description'] = get_the_content();
                if ($tags && !is_wp_error($tags)) {
                    $meta['tags'] = array_map(
                        function ($tag) {
                            return $tag->name;
                        },
                        $tags);

                }
                $youtube = new Youtube();
                $youtube->updateVideo($meta['youtube_video'], $meta);
            }
        }
    }

    /**
     * Convert BR tags to nl
     *
     * @param string $string The string to convert
     * @return string The converted string
     */
    public function br2nl($string)
    {
        return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
    }
}
