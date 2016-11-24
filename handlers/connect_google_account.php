<?php
define('DOING_AJAX', true);
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../../wp-load.php');
include_once __DIR__ . '/../autoloader.php'; ?><!DOCTYPE html>
<html itemscope itemtype='http://schema.org/Article'>
<head>
    <script src='//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js'>
    </script>
    <script src='https://apis.google.com/js/client:platform.js?onload=start' async defer></script>
    <script>
        function start() {
            gapi.load('auth2', function () {
                auth2 = gapi.auth2.init({
                    client_id: '<?= get_option('oog-uitzendingen-clientid') ?>',
                    // Scopes to request in addition to 'profile' and 'email'
                    scope: 'https://www.googleapis.com/auth/youtube'
                });
            });
        }
    </script>
</head>
<body>
<h1>Youtube account koppelen</h1>
<button id='signinButton'>Sign in with Google</button>
<script>
    $('#signinButton').click(function () {
        // signInCallback defined in step 6.
        auth2.grantOfflineAccess({
            'redirect_uri': 'postmessage',
            'approval_prompt': 'force'
        }).then(function signInCallback(authResult) {
            if (authResult['code']) {

                // Hide the sign-in button now that the user is authorized, for example:
                $('#signinButton').attr('style', 'display: none');

                var data = new FormData();
                data.append('action', '<?= \oog\uitzendingen\Uitzending::ACTION_STORE_CODE ?>');
                data.append('code', authResult['code']);

                // Send the code to the server
                $.ajax({
                    type: 'POST',
                    url: '<?= esc_url(admin_url('admin-post.php')) ?>',
                    dataType: 'json',
                    contentType: false,
                    success: function (result) {
                        // Handle or verify the server response.
                        window.location.href = '<?= esc_url(admin_url('options-general.php') . '?page=' . \oog\uitzendingen\admin\Admin::OPTIONS_PAGE)?>'
                    },
                    processData: false,
                    data: data
                });
            } else {
                // There was an error.
            }
        });
    });
</script>
</body>
</html>
