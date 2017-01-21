<?php
namespace oog\uitzendingen\upload;

use GuzzleHttp\Psr7\Request;
use oog\uitzendingen\db\sqlite\DB;

class Upload
{
    const CHUNK_SIZE_BYTES = 10 * 1024 * 1024;

    private $baseDir;

    private $db;

    private $qm;


    public function __construct($baseDir)
    {
        $this->baseDir = $baseDir;

        $this->db = new DB(SCRIPT_DIR . '/progress.db');
        $this->qm = new QueueManager($baseDir);

        Logger::Log("=============== YouTube uploader ===============\n", false);

    }

    /**
     * Checks the directory for new files to upload
     */
    public function checkForFiles()
    {

        $interrupted = glob($this->baseDir . '/queue/inprogress/*.mp4');

        if (count($interrupted) > 0) {
            $this->processInterrupted();
        } else {

            $videos = glob($this->baseDir . '/*.mp4');
            foreach ($videos as $video) {
                $parts = explode('/', $video);
                $filename = array_pop($parts);
                rename($video,
                    $this->baseDir . DIRECTORY_SEPARATOR . 'queue' . DIRECTORY_SEPARATOR . $filename);
            }

            $this->processQueue();
        }

    }

    private function processQueue()
    {

        $videos = glob($this->baseDir . '/queue/*.mp4');
        if ($videos) {
            foreach ($videos as $video) {
                $target = $this->qm->moveFile($video, 'inprogress');
                $this->upload($target);
            }
            Logger::Log("Geen video's meer in de queue\n");
        }
    }

    public function processInterrupted()
    {
        $videos = glob($this->baseDir . '/queue/inprogress/*.mp4');
        foreach ($videos as $video) {
            $this->resumeUpload($video);
        }
    }

    private function resumeUpload($path)
    {
        $parts = explode('/', $path);
        $filename = array_pop($parts);
        $data = $this->db->getResumeUri($filename);
        if (!$data) {
            $this->qm->moveFile($path, 'failed');
            return;
        }

        $client = Auth::GetGoogleClient();

        try {
            // Get filename
            Logger::Log("\nVideo $path upload hervatten");
            $client->setDefer(true);

//            $request = new Request('PUT', $data['resumeuri']);
            $request = $this->createYoutubeVideo($client, $path);
//            $request->set

            // Create a MediaFileUpload object for resumeable uploads.
            $media = new \Google_Http_MediaFileUpload(
                $client,
                $request,
                'video/*',
                null,
                true,
                Upload::CHUNK_SIZE_BYTES
            );
            $media->setFileSize(filesize($path));
            $media->resume($data['resumeuri']);
            $this->uploadChunks($path, $media, $media->getProgress());

            // If you want to make other calls after the file upload, set setDefer back to false
            $client->setDefer(false);
            $this->qm->moveFile($path, 'done');
            Logger::Log(" gelukt!\n");
        } catch (\Google_Service_Exception $e) {
            Logger::Log(sprintf("\nA service error occurred: %s\n", $e->getMessage()));
            $this->qm->moveFile($path, 'failed');
        } catch (\Google_Exception $e) {
            Logger::Log(sprintf("\nAn client error occurred: %s\n", $e->getMessage()));
            $this->qm->moveFile($path, 'failed');
        } catch (\Exception $e) {
            Logger::Log(sprintf("\nAn client error occurred: %s\n", $e->getMessage()));
            $this->qm->moveFile($path, 'failed');
        }
    }

    private function upload($path)
    {
        $client = Auth::GetGoogleClient();
        $client->setDefer(true);

        try {
            // Get filename
            $parts = explode('/', $path);
            $filename = array_pop($parts);
            Logger::Log("\nVideo $filename uploaden");
            $insertRequest = $this->createYoutubeVideo($client, $path);

            // Create a MediaFileUpload object for resumeable uploads.
            $media = new \Google_Http_MediaFileUpload(
                $client,
                $insertRequest,
                'video/*',
                null,
                true,
                Upload::CHUNK_SIZE_BYTES
            );

            $this->uploadChunks($path, $media, 0);

            // If you want to make other calls after the file upload, set setDefer back to false
            $client->setDefer(false);
            $this->qm->moveFile($path, 'done');
            Logger::Log(" gelukt!\n");
        } catch (\Google_Service_Exception $e) {
            Logger::Log(sprintf("\nA service error occurred: %s\n", $e->getMessage()));
            $this->qm->moveFile($path, 'failed');
        } catch (\Google_Exception $e) {
            Logger::Log(sprintf("\nAn client error occurred: %s\n", $e->getMessage()));
            $this->qm->moveFile($path, 'failed');
        } catch (\Exception $e) {
            Logger::Log(sprintf("\nAn client error occurred: %s\n", $e->getMessage()));
            $this->qm->moveFile($path, 'failed');
        }
    }

    /**
     * @param string $path
     * @param \Google_Http_MediaFileUpload $media
     * @param int $start
     */
    private function uploadChunks($path, $media, $start = 0)
    {
        // Get filename
        $parts = explode('/', $path);
        $filename = array_pop($parts);
        $media->setFileSize(filesize($path));

        $i = $start;
        $total = filesize($path);

        // Read the media file and upload it chunk by chunk.
        $status = false;
        $handle = fopen($path, "rb");
        Logger::Log(sprintf(
            '   %d%%',
            min(100, ceil($i / $total * 100))
        ));
        while (!$status && !feof($handle)) {
            $i += Upload::CHUNK_SIZE_BYTES;
            $chunk = fread($handle, Upload::CHUNK_SIZE_BYTES);
            try {
                $this->db->storeResumeUri($filename, $media->getResumeUri(), 0);
                $status = $media->nextChunk($chunk);
            } catch (\Exception $e) {
                Logger::Log($e->getMessage());
            }
            $progress = sprintf(
                '   %d%%',
                min(100, ceil($i / $total * 100))
            );
            $progress = substr($progress, -4);
            Logger::Log(chr(8) . chr(8) . chr(8) . chr(8) . "$progress");
        }

        fclose($handle);
    }

    private function createYoutubeVideo($client, $path)
    {
        // Get filename
        $parts = explode('/', $path);
        $filename = array_pop($parts);

        $youtube = new \Google_Service_YouTube($client);
        $mtime = filemtime($path);
        $snippet = new \Google_Service_YouTube_VideoSnippet();

        // Set it as title
        $snippet->setTitle(
            sprintf('%s, (%s)',
                $filename,
                date('d-m-Y H:i:s', $mtime))
        );
        $snippet->setDescription('');
        $snippet->setCategoryId('22');

        // Set video to private
        $status = new \Google_Service_YouTube_VideoStatus();
        $status->privacyStatus = "private";

        // Attach metadata to video
        $video = new \Google_Service_YouTube_Video();
        $video->setSnippet($snippet);
        $video->setStatus($status);

        // Create a request for the API's videos.insert method to create and upload the video.
        $insertRequest = $youtube->videos->insert("status,snippet", $video);
        return $insertRequest;
    }

}