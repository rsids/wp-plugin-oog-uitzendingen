<?php


namespace oog\uitzendingen\widgets;


use oog\uitzendingen\Filters;

class LatestEpisodeWidget extends \WP_Widget
{
    function __construct()
    {
        parent::__construct('oog_uitzendingen', $name = 'OOG Uitzendingen widget');
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance)
    {
        error_log(print_r($args, true));
        $filters = new Filters();
        $query = $filters->getLatestVideos($instance);

        $this->render($query);

    }


    /**
     * Outputs the options form on admin
     *
     * @param array $instance The widget options
     * @return string|void
     */
    public function form($instance)
    {
        // outputs the options form on admin
        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('New title', 'oog_uitzendingen_text_domain');
        }
        if (isset($instance['num_posts'])) {
            $numPosts = $instance['num_posts'];
        } else {
            $numPosts = 4;
        }
        if (isset($instance['cat_slug'])) {
            $catSlug = $instance['cat_slug'];
        } else {
            $catSlug = 'nieuws';
        }
        ?>
        <p>
            <label for="<?= $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?= $this->get_field_id('title'); ?>"
                   name="<?= $this->get_field_name('title'); ?>" type="text" value="<?= esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?= $this->get_field_id('num_posts'); ?>"><?php _e('Aantal posts:'); ?></label>
            <input class="widefat" id="<?= $this->get_field_id('num_posts'); ?>"
                   name="<?= $this->get_field_name('num_posts'); ?>" type="number" min="1"
                   value="<?= esc_attr($numPosts); ?>">
        </p>
        <p>
            <label for="<?= $this->get_field_id('cat_slug'); ?>"><?php _e('Programma slug:'); ?></label>
            <input class="widefat" id="<?= $this->get_field_id('cat_slug'); ?>"
                   name="<?= $this->get_field_name('cat_slug'); ?>" type="text" value="<?= esc_attr($catSlug); ?>">
        </p>
        <?php
    }

    /**
     * Processing widget options on save
     *
     * @param array $new_instance The new options
     * @param array $old_instance The previous options
     * @return array
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['num_posts'] = (!empty($new_instance['num_posts'])) ? (int)$new_instance['num_posts'] : 1;
        $instance['cat_slug'] = (!empty($new_instance['cat_slug'])) ? strip_tags($new_instance['cat_slug']) : 'nieuws';

        return $instance;
    }

    private function render(\WP_Query $query)
    {
        error_log('Rendering widget');
        $items = '';

        $posts = $query->get_posts();

        foreach ($posts as $video) {
            $id = get_field('youtube_video', $video->ID) ?: get_field('external_youtube_video', $video->ID);

            $thumbnail = "https://i.ytimg.com/vi/{$id}/mqdefault.jpg";
            $related = get_field('related_post', $video->ID);
            $title = get_the_title($video->ID);

            if ($related) {
                $link = get_permalink($related);
            } else {
                $link = get_permalink($video->ID);
            }

            $alt = str_replace('"', '\'', $title);
            $maxChars = 50;
            if (strlen($title) > $maxChars) {
                $title = substr($title, 0, $maxChars) . "...";
            }

            $items .= <<<OOG
            <li class="clearfix">
                <a href="$link">
                    <img class="img-responsive article-image" src="$thumbnail" width="320" height="180" alt="$alt">
                    <div class='missed-title'>$title</div>
                </a>
            </li>
OOG;
        }
        echo <<<OOG
            <aside class="missed-episode-widget widget">

                <h3><a href="/uitzending-gemist/">Uitzending gemist</a></h3>

                    <ul>$items</ul>

            </aside> <!-- missed-episode-wdget -->
OOG;
    }
}