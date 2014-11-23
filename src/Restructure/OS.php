<?php namespace henrist\FilmDB\Restructure;

interface OS {
    public function dir_mk($dir);
    public function dir_rm($dir);
    public function dir_mv($dir, $newdir);
    public function dir_link($dir_as_link, $target);

    public function file_touch($file);
}