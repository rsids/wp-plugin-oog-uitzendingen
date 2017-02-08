<?php

namespace oog\uitzendingen\admin;

use oog\uitzendingen\Uitzending;
use oog\uitzendingen\Youtube;

class Admin
{

    const OPTIONS_PAGE = 'ooguitzendingen-options';

    private $edit;

    public function addActionsAndHooks()
    {
        if (!defined('OOG_UITZENDINGEN_CLI_MODE')) {
            add_action('admin_menu', [$this, 'addMenu']);
            add_action('admin_post_' . Uitzending::ACTION_STORE_CODE, [$this, 'storeGoogleCode'], 10);
            add_action('admin_post_' . Uitzending::ACTION_DISCONNECT, [$this, 'disconnectGoogle'], 10);
            add_action('admin_post_' . Uitzending::ACTION_GET_CATEGORIES, [$this, 'getCategories'], 10);
            add_action('admin_notices', [$this, 'adminNotices']);

            add_filter('posts_where', [$this, 'search'], 10, 2);
            add_filter('posts_join', [$this, 'searchJoin']);
            add_filter('posts_distinct', [$this, 'searchDistinct']);
        }

        $this->edit = new EditUitzending();
    }

    /**
     * hooks to admin_init
     */
    public function init()
    {
        $this->registerSettings();
    }

    public function addMenu()
    {
        add_options_page(
            __('OogTV Uitzendingen plugin'),
            __('OogTV Uitzendingen'),
            'manage_options',
            Admin::OPTIONS_PAGE,
            [$this, 'renderOptions']
        );
    }

    public function adminNotices()
    {

        if (array_key_exists('uitzendingNotice', $_GET)) {

            $msg = '';
            $type = 'info';
            switch ($_GET['uitzendingNotice']) {
                case Uitzending::NOTICE_CATEGORY_OK:
                    $msg = 'Categorieën geladen';
                    $type = 'success';
                    break;
                case Uitzending::NOTICE_CATEGORY_ERR:
                    $msg = 'Categorieën geladen mislukt';
                    $type = 'error';
                    break;
                case Uitzending::NOTICE_YOUTUBE_FAIL;
                    $msg = 'Youtube bijwerken geladen mislukt';
                    $type = 'error';
                    break;

            }

            if ($msg) {
                echo <<<OOG
<div class="notice notice-{$type} is-dismissible">
      <p>{$msg}</p>
</div>
OOG;
            }
        }
    }

    public function disconnectGoogle()
    {
        $client = Admin::GetGoogleClient();
        $client->revokeToken(get_option('oog-uitzending-refresh_token'));
        $client->revokeToken(get_option('oog-uitzending-access_token'));

        delete_option('oog-uitzending-refresh_token');
        delete_option('oog-uitzending-access_token');
        delete_option('oog-uitzending-id_token');

    }

    public function storeGoogleCode()
    {
        $client = Admin::GetGoogleClient(false);

        $client->setRedirectUri(get_home_url());
        $token = $client->fetchAccessTokenWithAuthCode($_POST['code']);

        if (array_key_exists('access_token', $token)) {
            update_option('oog-uitzending-access_token', json_encode($token));
            update_option('oog-uitzending-id_token', $token['id_token']);
            update_option('oog-uitzending-refresh_token', $token['refresh_token']);
        } else {
            error_log('Missing key "access_token" while authenticating google account, response was: ' . var_export($token, true));
        }

    }

    public function getCategories()
    {
        $yt = new Youtube();
        $categories = $yt->getCategories();

        if (count($categories) > 0) {
            $param = Uitzending::NOTICE_CATEGORY_OK;
            update_option(Uitzending::OPTION_CATEGORIES, json_encode($categories));
        } else {
            $param = Uitzending::NOTICE_CATEGORY_ERR;
        }
        exit(wp_redirect(admin_url('options-general.php?page=' . self::OPTIONS_PAGE . '&uitzendingNotice=' . $param)));
    }

    public function renderOptions()
    {
        include OOG_UITZENDINGEN_PLUGIN_DIR . '/templates/admin-settings.php';
    }

    public function registerSettings()
    {

        register_setting('oog-uitzendingen', 'oog-uitzendingen-clientid');
        register_setting('oog-uitzendingen', 'oog-uitzendingen-secret');
        register_setting('oog-uitzendingen', 'oog-uitzendingen-projectid');
        register_setting('oog-uitzendingen', 'oog-uitzendingen-origins');
    }

    public function search($where, $query)
    {
        global $pagenow, $wpdb;
        //

        if (!is_admin()) {
            return $where;
        }

        if (is_search()) {
            if($query->query_vars['post_type'] === Uitzending::POST_TYPE_TV) {
                $where = preg_replace(
                    "/\(\s*" . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
                    "(" . $wpdb->posts . ".post_title LIKE $1) OR (" . $wpdb->postmeta . ".meta_value LIKE $1)", $where);
            }
        }

        return $where;
    }

    public function searchDistinct($where)
    {
        global $wpdb;

        if (!is_admin()) {
            return $where;
        }

        if (is_search()) {
            return "DISTINCT";
        }

        return $where;
    }

    public function searchJoin($join)
    {
        global $wpdb;

        if (!is_admin()) {
            return $join;
        }

        if (is_search()) {
            $join .= ' LEFT JOIN ' . $wpdb->postmeta . ' ON ' . $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
        }

        return $join;
    }

    /**
     * @param bool $setToken
     * @return \Google_Client
     */
    public static function GetGoogleClient($setToken = true)
    {
        $origins = get_option('oog-uitzendingen-origins', '');
        $origins = explode("\n", $origins);
        array_walk($origins, function (&$item) {
            $item = filter_var($item, FILTER_VALIDATE_URL);
        });

        $client = new \Google_Client();
        $client->setAuthConfig([
            'web' => [
                'client_id' => get_option('oog-uitzendingen-clientid'),
                'client_secret' => get_option('oog-uitzendingen-secret'),
                'project_id' => get_option('oog-uitzendingen-projectid'),
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => 'https://accounts.google.com/o/oauth2/token',
                'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                'javascript_origins' => $origins
            ]
        ]);

        $client->addScope(\Google_Service_YouTube::YOUTUBE);

        if ($setToken && get_option('oog-uitzending-access_token')) {
            if ($client->isAccessTokenExpired()) {

                $token = $client->fetchAccessTokenWithRefreshToken(get_option('oog-uitzending-refresh_token'));
                if (array_key_exists('access_token', $token)) {
                    update_option('oog-uitzending-access_token', json_encode($token));
                    update_option('oog-uitzending-id_token', $token['id_token']);
                } else {
                    error_log('Missing key "access_token" while updating authentication, response was: ' . var_export($token, true));
                }
            }

            try {
                $client->setAccessToken(get_option('oog-uitzending-access_token'));

            } catch (\InvalidArgumentException $e) {
                error_log('Cannot set token to ' . var_export(get_option('oog-uitzending-token'), true));
            }
        }
        return $client;
    }
}