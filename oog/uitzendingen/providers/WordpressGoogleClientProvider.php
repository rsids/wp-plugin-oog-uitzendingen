<?php

namespace oog\uitzendingen\providers;


class WordpressGoogleClientProvider extends AbstractGoogleClientProvider
{

    protected function getOrigins()
    {
        $origins = get_option('oog-uitzendingen-origins', '');
        $origins = explode("\n", $origins);
        array_walk($origins, function (&$item) {
            $item = filter_var($item, FILTER_VALIDATE_URL);
        });

        return $origins;
    }

    protected function getClientId()
    {
        return get_option('oog-uitzendingen-clientid');
    }

    protected function getSecret()
    {
        return get_option('oog-uitzendingen-secret');
    }

    protected function getProjectId()
    {
        return get_option('oog-uitzendingen-projectid');
    }

    protected function getRefreshToken()
    {
        return get_option('oog-uitzendingen-refresh_token');
    }

    protected function getAccessToken()
    {
        return get_option('oog-uitzendingen-access_token');
    }

    protected function setAccessToken($token)
    {
        update_option('oog-uitzending-access_token', json_encode($token));
        update_option('oog-uitzending-id_token', $token['id_token']);
    }

    protected function getConfig()
    {
        return [
            'web' => [
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getSecret(),
                'project_id' => $this->getProjectId(),
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => 'https://accounts.google.com/o/oauth2/token',
                'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                'javascript_origins' => $this->getOrigins()
            ]
        ];
    }

    protected function getScopes()
    {
        return [
            \Google_Service_YouTube::YOUTUBE
        ];
    }

    protected function getAccessType()
    {
        return 'online';
    }

    protected function onFetchAccesstokenError($client)
    {
        error_log("Could not get accesstoken");
        return null;
    }
}