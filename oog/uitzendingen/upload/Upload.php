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
        $this->log("=============== YouTube uploader ===============\n");

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
        if ($videos) {
            foreach ($videos as $video) {
                $target = $this->moveFile($video, 'inprogress');
                $this->upload($target);
            }
            $this->log("Geen video's meer in de queue\n");
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
            $this->log("\nVideo $filename uploaden");

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

            $chunkSizeBytes = 20 * 1024 * 1024;

            $client->setDefer(true);

            // Create a request for the API's videos.insert method to create and upload the video.
            $insertRequest = $youtube->videos->insert("status,snippet", $video);

            // Create a MediaFileUpload object for resumeable uploads.
            $media = new \Google_Http_MediaFileUpload(
                $client,
                $insertRequest,
                'video/*',
                null,
                true,
                $chunkSizeBytes
            );
            $media->setFileSize(filesize($path));

            $i = 0;
            $total = filesize($path);

            // Read the media file and upload it chunk by chunk.
            $status = false;
            $handle = fopen($path, "rb");
            $this->log('   0%');
            while (!$status && !feof($handle)) {
                $i += $chunkSizeBytes;
                $chunk = fread($handle, $chunkSizeBytes);
                try {
                    $status = $media->nextChunk($chunk);
                } catch(\Exception $e) {
                    $this->log($e->getMessage());
                }
                $progress = '   ' . min(100, ceil($i / $total * 100)) . '%';
                $progress = substr($progress, -4);
                $this->log(chr(8) . chr(8) . chr(8) . chr(8) . "$progress");
            }

            fclose($handle);

            // If you want to make other calls after the file upload, set setDefer back to false
            $client->setDefer(false);
            $this->moveFile($path, 'done');
            $this->log(" gelukt!\n");
        } catch (\Google_Service_Exception $e) {
            $this->log(sprintf("\nA service error occurred: %s\n", $e->getMessage()));
            $this->moveFile($path, 'failed');
        } catch (\Google_Exception $e) {
            $this->log(sprintf("\nAn client error occurred: %s\n", $e->getMessage()));
            $this->moveFile($path, 'failed');
        } catch (\Exception $e) {
            $this->log(sprintf("\nAn client error occurred: %s\n", $e->getMessage()));
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

    private function getGoogleClient()
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
                    $this->log("Fout bij authenticeren\n");
                    unlink(SCRIPT_DIR . '/credentials.json');
                    $client = $this->requestAuthCode($client);
                }
            }
        } else {
            $client = $this->requestAuthCode($client);

        }


        return $client;
    }

    private function requestAuthCode(\Google_Client $client)
    {
        $auth_url = $client->createAuthUrl();
        echo "\nGeen youtube koppeling gevonden, ga naar de volgende url en plak de code, gevolgd door [Enter]\n";
        echo $auth_url;
        echo "\nCode:\n";

        $line = fgets(STDIN);
        if (trim($line) != '') {
            $this->log("Code opgeslagen: $line\n");
            $result = $client->authenticate($line);
            if (array_key_exists('access_token', $result)) {
                file_put_contents(SCRIPT_DIR . '/credentials.json', json_encode($result));
            } else {
                $this->log("Fout bij authenticatie, foutmelding: " . $result['error_description'] . "\n");
                exit;
            }
        }
        fclose(STDIN);

        return $client;
    }

    private function log($message)
    {
        $handle = @fopen(SCRIPT_DIR . '/log.txt', 'a+');

        // Write to log file
        if ($handle) {
            fwrite($handle, date('c') . ': ' . $message . "\n");
            fclose($handle);
        }

        // Write to stdout
            echo $message;
    }
}