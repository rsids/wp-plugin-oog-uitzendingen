<?php

namespace oog\uitzendingen\providers;


abstract class AbstractGoogleClientProvider
{

    abstract protected function getConfig();

    abstract protected function getOrigins();

    abstract protected function getClientId();

    abstract protected function getSecret();

    abstract protected function getProjectId();

    abstract protected function getRefreshToken();

    abstract protected function getAccessToken();

    abstract protected function getAccessType();

    abstract protected function getScopes();

    abstract protected function setAccessToken($token);

    abstract protected function onFetchAccesstokenError($client);

    /**
     * @param bool $setToken
     * @return \Google_Client
     */
    public final function getGoogleClient($setToken = true)
    {

        $client = new \Google_Client();
        $client->setAuthConfig($this->getConfig());
        $client->setAccessType($this->getAccessType());
        $scopes = $this->getScopes();
        foreach ($scopes as $scope) {
            $client->addScope($scope);
        }

        if ($setToken && $this->getAccessToken()) {
            if ($client->isAccessTokenExpired()) {

                $token = $client->fetchAccessTokenWithRefreshToken($this->getRefreshToken());
                if (array_key_exists('access_token', $token)) {
                    $this->setAccessToken($token);
                } else if (array_key_exists('error', $token)) {
                    return $this->onFetchAccesstokenError($client);
                } else {
                    error_log('Missing key "access_token" while updating authentication, response was: ' . var_export($token, true));
                }
            }

            try {
                $client->setAccessToken($this->getAccessToken());

            } catch (\InvalidArgumentException $e) {
                error_log('Cannot set token to ' . var_export($this->getAccessToken(), true));
            }
        }
        return $client;
    }

//    public static function GetGoogleClient()
//    {
//        $client = new \Google_Client();
//        $client->setAuthConfigFile(SCRIPT_DIR . '/client_secret.json');
//        $client->setAccessType('offline');
//        $client->addScope(\Google_Service_YouTube::YOUTUBE);
//        $client->addScope(\Google_Service_YouTube::YOUTUBE_UPLOAD);
//
//        if (file_exists(SCRIPT_DIR . '/credentials.json')) {
//            $auth = json_decode(file_get_contents(SCRIPT_DIR . '/credentials.json'));
//            $client->setAccessToken((array)$auth);
//
//            if ($client->isAccessTokenExpired()) {
//                $token = $client->fetchAccessTokenWithRefreshToken($auth->refresh_token);
//
//                if (array_key_exists('error', $token)) {
//                    Logger::Log("Fout bij authenticeren\n");
//                    unlink(SCRIPT_DIR . '/credentials.json');
//                    $client = Auth::RequestAuthCode($client);
//                }
//            }
//        } else {
//            $client = Auth::RequestAuthCode($client);
//
//        }
//
//
//        return $client;
//    }
}