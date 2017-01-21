<?php
/**
 * Created by PhpStorm.
 * User: ids
 * Date: 20-1-17
 * Time: 10:55
 */

namespace oog\uitzendingen\upload;


class Auth
{
    public static function GetGoogleClient()
    {
        $client = new \Google_Client();
        $client->setAuthConfigFile(SCRIPT_DIR . '/client_secret.json');
        $client->setAccessType('offline');
        $client->addScope(\Google_Service_YouTube::YOUTUBE);
        $client->addScope(\Google_Service_YouTube::YOUTUBE_UPLOAD);

        if (file_exists(SCRIPT_DIR . '/credentials.json')) {
            $auth = json_decode(file_get_contents(SCRIPT_DIR . '/credentials.json'));
            $client->setAccessToken((array)$auth);

            if ($client->isAccessTokenExpired()) {
                $token = $client->fetchAccessTokenWithRefreshToken($auth->refresh_token);

                if (array_key_exists('error', $token)) {
                    Logger::Log("Fout bij authenticeren\n");
                    unlink(SCRIPT_DIR . '/credentials.json');
                    $client = Auth::RequestAuthCode($client);
                }
            }
        } else {
            $client = Auth::RequestAuthCode($client);

        }


        return $client;
    }

    public static function RequestAuthCode(\Google_Client $client)
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
                file_put_contents(SCRIPT_DIR . '/credentials.json', json_encode($result));
            } else {
                Logger::Log("Fout bij authenticatie, foutmelding: " . $result['error_description'] . "\n");
                exit;
            }
        }
        fclose(STDIN);

        return $client;
    }
}