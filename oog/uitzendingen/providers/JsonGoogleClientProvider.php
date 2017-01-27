<?php

namespace oog\uitzendingen\providers;


use oog\uitzendingen\upload\Logger;

class JsonGoogleClientProvider extends AbstractGoogleClientProvider
{

    private $clientSecret;
    private $credentials;

    protected function getOrigins()
    {
        return [];
    }

    protected function getClientId()
    {
        $config = $this->getConfig();

        return $config->installed->client_id;
    }

    protected function getSecret()
    {
        $config = $this->getConfig();

        return $config->installed->client_secret;
    }

    protected function getProjectId()
    {
        $config = $this->getConfig();

        return $config->installed->project_id;
    }

    protected function getRefreshToken()
    {
        $accessToken = $this->getAccessToken();
        if ($accessToken) {
            return $accessToken['refresh_token'];
        }

        return null;
    }

    protected function getAccessToken()
    {
        if (file_exists($this->credentials)) {
            $json = file_get_contents($this->credentials);
            return json_decode($json, true);
        }
        return false;
    }

    protected function setAccessToken($token)
    {
        file_put_contents($this->credentials, json_encode($token));
    }

    protected function getConfig()
    {
        $json = file_get_contents($this->clientSecret);
        return json_decode($json, true);
    }

    public function setCredentialsJson($file)
    {
        $this->credentials = $file;
    }

    public function setClientSecretJson($file)
    {

        if (file_exists($file)) {
            $this->clientSecret = $file;

        } else {
            throw new \Exception($file . " was not found");
        }

    }

    protected function requestAuthCode($client)
    {
        $auth_url = $client->createAuthUrl();
        echo "\nGeen youtube koppeling gevonden, ga naar de volgende url en plak de code, gevolgd door [Enter]\n";
        echo $auth_url;
        echo "\nCode:\n";

        $line = fgets(STDIN);
        if (trim($line) != '') {
            Logger::Log("Code opgeslagen: $line\n");
            $result = $client->authenticate($line);
            if (array_key_exists('access_token', $result)) {
                $this->setAccessToken($result);
            } else {
                Logger::Log("Fout bij authenticatie, foutmelding: " . $result['error_description'] . "\n");
                exit;
            }
        }
        fclose(STDIN);

        return $client;
    }

    protected function onFetchAccesstokenError($client)
    {
        @unlink($this->credentials);
        return $this->requestAuthCode($client);
    }


    protected function getScopes()
    {
        return [
            \Google_Service_YouTube::YOUTUBE,
            \Google_Service_YouTube::YOUTUBE_UPLOAD
        ];
    }

    protected function getAccessType()
    {
        return 'offline';
    }
}