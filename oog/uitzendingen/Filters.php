<?php


namespace oog\uitzendingen;


class Filters
{

    function __construct()
    {
    }

    public function init()
    {
        add_filter('oog-uitzending-latest-video', [$this, 'getLatest']);
        add_filter('oog-uitzending-has-video', [$this, 'hasVideo']);
        add_filter('oog-uitzending-has-radio', [$this, 'hasRadio']);
        add_filter('oog-uitzending-get-youtube-player', [$this, 'getYoutubePlayer']);
        add_filter('oog-uitzending-get-categories', [$this, 'getCategories']);
        add_filter('oog-uitzending-filter-uitzendingen', [$this, 'filterUitzendingen']);
        add_filter('oog-uitzending-get-latest-videos', [$this, 'getLatestVideos']);

    }

    public function filterUitzendingen()
    {
        $archive = new Archive();
        $queryVars = $archive->filterArchive();
        return $queryVars;
    }

    public function getLatestVideos($args)
    {
        $query = new \WP_Query([
            'post_type' => Uitzending::POST_TYPE_TV,
            'posts_per_page' => $args['num_posts'],
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => [[
                'taxonomy' => Uitzending::TAXONOMY_PROGRAMME,
                'field' => 'slug',
                'terms' => $args['cat_slug']
            ]]

        ]);

        return $query;
    }

    /**
     * checks if a post is connected to a youtube video
     * @param $postID
     * @return bool|integer
     */
    public function hasVideo($postID)
    {
        return $this->hasMedia($postID, Uitzending::POST_TYPE_TV);
    }

    /**
     * checks if a post is connected to a radio broadcast
     * @param $postID
     * @return bool|integer
     */
    public function hasRadio($postID)
    {
        return $this->hasMedia($postID, Uitzending::POST_TYPE_RADIO);
    }

    public function getCategories()
    {
        return get_terms([
            'taxonomy' => Uitzending::TAXONOMY_PROGRAMME,
            'hide_empty' => true,
        ]);
    }

    /**
     * Gets the latest published video
     */
    public function getLatest()
    {

        $recent_posts = wp_get_recent_posts([
            'post_type' => Uitzending::POST_TYPE_TV,
            'numberposts' => 1,
            'orderby' => 'post_date',
            'order' => 'DESC'
        ], ARRAY_A);

        if (count($recent_posts) > 0) {
            $sc = new Shortcodes();
            $sc->addYoutubePlayer([
                    'uitzending' => $this->getYoutubePlayer($recent_posts[0]['ID'])
                ]);

        }

    }

    /**
     * Outputs a youtube player for the given postId
     * @param $postId
     * @param int $width
     */
    public function getYoutubePlayer($postId, $width = 532)
    {
        $youtube_url = get_field('youtube_video', $postId);
        if (!$youtube_url) {
            $youtube_url = get_field('external_youtube_video', $postId);
        }

        if ($youtube_url) {
            $sc = new Shortcodes();
            $sc->addYoutubePlayer(
                [
                    'youtube' => $youtube_url,
                    'width' => $width
                ]);

        }
    }

    /**
     * Checks if the given post has any media attached to it
     * @param $postId
     * @param $type
     * @return bool|int
     */
    private function hasMedia($postId, $type)
    {
        $postId = (int)$postId;
        $type = $type === Uitzending::POST_TYPE_TV ? Uitzending::POST_TYPE_TV : Uitzending::POST_TYPE_RADIO;
        $posts = get_posts([
            'numberposts' => 1,
            'post_type' => $type,
            'meta_query' => [
                [
                    'key' => 'related_post',
                    'value' => '"' . $postId . '"',
                    'compare' => 'LIKE'

                ]
            ]
        ]);
        if (count($posts) > 0) {
            return $posts[0]->ID;
        }

        return false;

    }
}