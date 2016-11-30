<?php
namespace oog\uitzendingen\upload;

class Upload
{

    private $baseDir;
    private $client;

    public function __construct($baseDir)
    {
        $this->baseDir = $baseDir;

        if (!is_dir($baseDir . '/queue')) {
            mkdir($baseDir . '/queue', 0777, true);
        }
        if (!is_dir($baseDir . '/queue/inprogress')) {
            mkdir($baseDir . '/queue/inprogress', 0777, true);
        }
        if (!is_dir($baseDir . '/queue/done')) {
            mkdir($baseDir . '/queue/done', 0777, true);
        }
        if (!is_dir($baseDir . '/queue/failed')) {
            mkdir($baseDir . '/queue/failed', 0777, true);
        }
        echo "=============== \033[31mYou\033[0mTube uploader ===============\n";

        $this->client = $this->getGoogleClient();
    }

    /**
     * Checks the directory for new files to upload
     */
    public function checkForFiles()
    {
        $videos = glob($this->baseDir . '/*.mp4');

        foreach ($videos as $video) {
            $parts = explode('/', $video);
            $filename = array_pop($parts);
            rename($video,
                $this->baseDir . DIRECTORY_SEPARATOR . 'queue' . DIRECTORY_SEPARATOR . $filename);
        }

        $this->processQueue();
    }

    private function processQueue()
    {

        $videos = glob($this->baseDir . '/queue/*.mp4');
        if($videos) {
            foreach ($videos as $video) {
                $target = $this->moveFile($video, 'inprogress');
                $this->upload($target);
            }
            echo "\033[34mGeen video's meer in de queue\033[0m\n";
        }
    }

    private function upload($path)
    {
        $client = $this->client;
        $youtube = new \Google_Service_YouTube($client);

        try {
            // Get filename
            $parts = explode('/', $path);
            $filename = array_pop($parts);
            $mtime = filemtime($path);
            echo "\nVideo \033[1m$filename\033[0m uploaden";

            // Set it as title
            $snippet = new \Google_Service_YouTube_VideoSnippet();
            $snippet->setTitle(
                sprintf('%s, (%s)',
                    $filename,
                    date('d-m-Y H:i:s', $mtime))
            );
            $snippet->setDescription("");
            $snippet->setCategoryId("22");

            // Set video to private
            $status = new \Google_Service_YouTube_VideoStatus();
            $status->privacyStatus = "private";

            // Attach metadata to video
            $video = new \Google_Service_YouTube_Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);

            $chunkSizeBytes = 1 * 1024 * 1024;

            $client->setDefer(true);

            // Create a request for the API's videos.insert method to create and upload the video.
            $insertRequest = $youtube->videos->insert("status,snippet", $video);

            // Create a MediaFileUpload object for resumable uploads.
            $media = new \Google_Http_MediaFileUpload(
                $client,
                $insertRequest,
                'video/*',
                null,
                true,
                $chunkSizeBytes
            );
            $media->setFileSize(filesize($path));

            $status = ['/','-','\\','|'];
            $i = 0;

//            echo "\033[1D\033[33m{$status[$i++%4]}";

            // Read the media file and upload it chunk by chunk.
            $status = false;
            $handle = fopen($path, "rb");
            while (!$status && !feof($handle)) {
                $chunk = fread($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);
                echo ".";
//                echo "\033[1D\033[33m{$status[$i++%4]}";
            }
//            echo "\033[1D";

            fclose($handle);

            // If you want to make other calls after the file upload, set setDefer back to false
            $client->setDefer(false);
            $this->moveFile($path, 'done');
            echo " \033[32mgelukt!\033[0m\n";
        } catch (\Google_Service_Exception $e) {
            echo sprintf("\n\033[31mA service error occurred: \033[0m%s\n", $e->getMessage());
            $this->moveFile($path, 'failed');
        } catch (\Google_Exception $e) {
            echo sprintf("\n\033[31mAn client error occurred: \033[0m%s\n", $e->getMessage());
            $this->moveFile($path, 'failed');
        } catch (\Exception $e) {
            echo sprintf("\n\033[31mAn client error occurred: \033[0m%s\n", $e->getMessage());
            $this->moveFile($path, 'failed');

        }
    }

    private function moveFile($path, $target)
    {
        $parts = explode('/', $path);
        $filename = array_pop($parts);
        rename($path, $this->baseDir . DIRECTORY_SEPARATOR . "queue/$target/$filename");
        return $this->baseDir . DIRECTORY_SEPARATOR . "queue/$target/$filename";
    }

    private
    function getGoogleClient()
    {
        $client = new \Google_Client();
        $client->setAuthConfigFile(ROOT_DIR . '/client_secret.json');
        $client->setAccessType('offline');
        $client->addScope(\Google_Service_YouTube::YOUTUBE);
        $client->addScope(\Google_Service_YouTube::YOUTUBE_UPLOAD);

        if (file_exists(ROOT_DIR . '/credentials.json')) {
            $auth = json_decode(file_get_contents(ROOT_DIR . '/credentials.json'));
            $client->setAccessToken((array)$auth);

            if ($client->isAccessTokenExpired()) {
                $token = $client->fetchAccessTokenWithRefreshToken((array)$auth);

                print_r($token);
            }
        } else {
            $client = $this->requestAuthCode($client);

        }


        return $client;
    }

    private
    function requestAuthCode(\Google_Client $client)
    {
        $auth_url = $client->createAuthUrl();
        echo "\nGeen youtube koppeling gevonden, ga naar de volgende url en plak de code, gevolgd door [Enter]\n";
        echo $auth_url;
        echo "\nCode:\n";

        $line = fgets(STDIN);
        if (trim($line) != '') {
            echo "Code opgeslagen: $line\n";
            $result = $client->authenticate($line);
            if (array_key_exists('access_token', $result)) {
                file_put_contents(ROOT_DIR . '/credentials.json', json_encode($result));
            } else {
                echo "Fout bij authenticatie, foutmelding: " . $result['error_description'] . "\n";
                exit;
            }
        }
        fclose(STDIN);

        return $client;
    }

    /* if ($setToken && get_option('oog-uitzending-access_token')) {
            if ($client->isAccessTokenExpired()) {

                $token = $client->fetchAccessTokenWithRefreshToken(get_option('oog-uitzending-refresh_token'));
                if (array_key_exists('access_token', $token)) {
                    update_option('oog-uitzending-access_token', $token['access_token']);
                    update_option('oog-uitzending-id_token', $token['id_token']);
                }
            }

            try {
                $client->setAccessToken(get_option('oog-uitzending-access_token'));

            } catch (\InvalidArgumentException $e) {
                error_log('Cannot set token to ' . var_export(get_option('oog-uitzending-token'), true));
            }
        }*/
}