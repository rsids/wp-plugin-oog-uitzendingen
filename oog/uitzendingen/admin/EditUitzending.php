<?php


namespace oog\uitzendingen\admin;


use oog\uitzendingen\Uitzending;
use oog\uitzendingen\Youtube;

class EditUitzending
{

    private $provider;

    public function __construct($provider)
    {
        $this->provider = $provider;
        if (!defined('OOG_UITZENDINGEN_CLI_MODE') && function_exists('add_filter') && function_exists('add_action')) {
            add_filter('acf/load_field/name=youtube_video', [$this, 'loadPrivateYoutubeVideos'], 10);
            add_filter('acf/load_field/name=youtube_category', [$this, 'loadYoutubeCategories'], 10);
            add_filter('acf/prepare_field/name=youtube_video', [$this, 'prepareYTVideo'], 10);
            add_filter('acf/fields/relationship/query/name=related_post', [$this, 'sortRelatedPosts'], 10);
            add_filter('wp_insert_post_data', [$this, 'preSavePost'], '99', 2);
            add_action('acf/save_post', [$this, 'savePost'], 10);
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

    public function sortRelatedPosts($posts)
    {
        $posts['order'] = 'DESC';
        $posts['orderby'] = 'date';
        return $posts;
    }

    /**
     * Before saving, set the title & content from the related post
     * @param $data
     * @param $postArr
     * @return mixed
     */
    public function preSavePost($data, $postArr)
    {

        // Check if related post is set
        if (array_key_exists('acf', $postArr)) {
            $relatedPost = $postArr['acf'][Uitzending::ACF_FIELD_RELATED_POST];
            if ($relatedPost && count($relatedPost) > 0) {

                // Fetch it
                $relatedPost = get_post($relatedPost[0]);

                if ($relatedPost && !is_wp_error($relatedPost)) {
                    // Set title & content if not set
                    $data['post_title'] = $data['post_title'] !== '' ? $data['post_title'] : $relatedPost->post_title;

                    if ($data['post_content'] === '') {
                        $data['post_content'] = str_replace('<!--more-->', '', $relatedPost->post_content);
                    }

                }
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
        $youtube = new Youtube($this->provider);
        $videos = $youtube->getVideos(true);
        $choices = [];
        foreach ($videos as $playlistItem) {
            if ($playlistItem['status']['privacyStatus'] === 'private') {
                $choices[$playlistItem['snippet']['resourceId']['videoId']] = sprintf(
                    '%s (%s)',
                    $playlistItem['snippet']['title'],
                    $playlistItem['snippet']['resourceId']['videoId']);
            }
        }
        $field['choices'] = [0 => '-- Kies een Youtube Video --'];
        $field['choices'] = array_merge($field['choices'], $choices);

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

        $programme = wp_get_post_terms($post_id, Uitzending::TAXONOMY_PROGRAMME);

        if (empty($programme)) {
            wp_set_object_terms($post_id, 'nieuws', Uitzending::TAXONOMY_PROGRAMME);
        }

        if ('publish' === get_post_status($post_id)) {

            if ($_POST['acf'][Uitzending::ACF_FIELD_YOUTUBE_VIDEO]) {

                $meta = [];
                $meta['tags'] = [];
                $meta['title'] = get_the_title($post_id);
                $meta['description'] = get_post_field('post_content', $post_id);

                $meta['youtube_video'] = $_POST['acf'][Uitzending::ACF_FIELD_YOUTUBE_VIDEO];
                $meta['youtube_category'] = $_POST['acf'][Uitzending::ACF_FIELD_YOUTUBE_CATEGORY];

                $meta['description'] = strip_tags($this->br2nl($meta['description']));

                $tags = get_the_tags($post_id);
                if ($tags && !is_wp_error($tags)) {
                    $meta['tags'] = array_map(
                        function ($tag) {
                            return $tag->name;
                        },
                        $tags);

                }
                $youtube = new Youtube($this->provider);
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
