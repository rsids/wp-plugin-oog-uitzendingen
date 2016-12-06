<?php


namespace oog\uitzendingen;


class Shortcodes
{
    public function init()
    {
        add_shortcode('add-youtube-player', [$this, 'addYoutubePlayer']);
    }

    public function addYoutubePlayer($atts)
    {
        $a = shortcode_atts([
            'width' => 532,
            'uitzending' => null,
            'post' => null,
            'youtube' => null
        ], $atts);

        // Check if post id is set
        if ($a['post']) {
            $custom_fields = get_post_custom($a['post']);

            // Old way of adding youtube
            if (isset($custom_fields['youtube'])) {
                $a['youtube'] = is_array($custom_fields['youtube']) ? $custom_fields['youtube'][0] : $custom_fields['youtube'];
            } else {
                $f = new Filters();
                $a['uitzending'] = $f->hasVideo($a['post']);

            }
        }

        // Check if uitzending id is set
        if ($a['uitzending']) {
            $a['youtube'] = get_field('youtube_video', $a['uitzending']);
            if (!$a['youtube']) {
                $a['youtube'] = get_field('external_youtube_video', $a['uitzending']);
            }
        }

        if ($a['youtube']) {
            $a['youtube'] = filter_var($a['youtube'], FILTER_SANITIZE_STRING);
            $width = filter_var($a['width'], FILTER_VALIDATE_INT);
            if (!$width) {
                $width = 532;
            }
            $height = round($a['width'] / 1.602);
            echo <<<OOG
                    <div class='video-container'>
                        <iframe class="youtube-player" type="text/html" width="{$width}"
                                height="{$height}"
                                src="https://www.youtube.com/embed/{$a['youtube']}?rel=0" frameborder="0">
                        </iframe>
                    </div>
OOG;
        }


    }
}