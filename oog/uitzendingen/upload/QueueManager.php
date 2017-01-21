<?php

namespace oog\uitzendingen\upload;


class QueueManager
{
    private $baseDir;

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
    }

    public function moveFile($path, $target)
    {
        $parts = explode('/', $path);
        $filename = array_pop($parts);
        rename(
            $path,
            $this->baseDir . DIRECTORY_SEPARATOR . "queue/$target/$filename"
        );
        return $this->baseDir . DIRECTORY_SEPARATOR . "queue/$target/$filename";
    }
}