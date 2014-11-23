<?php namespace henrist\FilmDB;

use henrist\FilmDB\Restructure\OS

class Win implements OS {
    public $type = "win";
    public function dir_mk($dir)
    {
        shell_exec("mkdir ".escapeshellarg($dir));
    }

    public function dir_rm($dir)
    {
        shell_exec("rmdir /S /Q ".escapeshellarg($dir));
    }

    public function dir_mv($dir, $newdir)
    {
        shell_exec("move /Y ".escapeshellarg($dir)." ".escapeshellarg($newdir));
    }

    public function dir_link($dir_as_link, $target)
    {
        shell_exec("mklink /D ".escapeshellarg($dir_as_link)." ".escapeshellarg($target));
    }

    public function file_touch($file)
    {
        file_put_contents($file, "dummy", FILE_APPEND);
    }
}