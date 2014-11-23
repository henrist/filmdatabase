<?php namespace henrist\FilmDB\Restructure\OS;

use henrist\FilmDB\Restructure\OS

class Other implements OS {
    public $type = "other";
    public function dir_mk($dir)
    {
        mkdir($dir);
    }

    public function dir_rm($dir)
    {
        shell_exec("rm -Rf ".escapeshellarg($dir));
    }

    public function dir_mv($dir, $newdir)
    {
        rename($dir, $newdir);
    }

    public function dir_link($dir_as_link, $target)
    {
        symlink($target, $dir_as_link);
    }

    public function file_touch($file)
    {
        file_put_contents($file, "dummy", FILE_APPEND);
    }
}