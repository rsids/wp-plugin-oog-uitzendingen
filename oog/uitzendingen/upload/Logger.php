<?php
/**
 * Created by PhpStorm.
 * User: ids
 * Date: 20-1-17
 * Time: 10:41
 */

namespace oog\uitzendingen\upload;


class Logger
{

    public static function Log($message, $toFile = true)
    {
        if ($toFile) {
            $handle = @fopen(SCRIPT_DIR . '/log.txt', 'a+');
            // Write to log file
            if ($handle) {
                fwrite($handle, date('c') . ': ' . $message . "\n");

                fclose($handle);
            }
        }

        // Write to stdout
        echo $message;
    }
}