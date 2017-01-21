<?php

namespace oog\uitzendingen\db\sqlite;


class DB extends \SQLite3
{

    public function __construct($filename, $flags = null, $encryption_key = null)
    {
        parent::__construct($filename, SQLITE3_OPEN_CREATE|SQLITE3_OPEN_READWRITE, $encryption_key);

        $this->query('CREATE TABLE IF NOT EXISTS progress (filename TEXT UNIQUE, resumeuri TEXT, videoId TEXT)');
    }

    public function getResumeUri($file)
    {
        $stmt = $this->prepare('SELECT resumeuri, videoId FROM progress WHERE filename=:filename');
        $stmt->bindValue(':filename', $file, SQLITE3_TEXT);
        $result = $stmt->execute();
        return $result->fetchArray();
    }

    public function storeResumeUri($file, $uri, $videoId) {
        $stmt = $this->prepare('INSERT OR IGNORE INTO progress (filename, resumeuri, videoId) VALUES (:f, :r, :i)');
        $stmt->bindValue(':f', $file, SQLITE3_TEXT);
        $stmt->bindValue(':r', $uri, SQLITE3_TEXT);
        $stmt->bindValue(':i', $videoId, SQLITE3_TEXT);
        $stmt->execute();

        $stmt = $this->prepare('UPDATE progress SET resumeuri=:r WHERE filename=:f');
        $stmt->bindValue(':f', $file, SQLITE3_TEXT);
        $stmt->bindValue(':r', $uri, SQLITE3_TEXT);
        $stmt->execute();
    }
}