<?php namespace henrist\FilmDB\HttpReq;

use henrist\FilmDB\HttpReq;
use henrist\FilmDB\IMDB;

class IMDBData extends HttpReq
{
    function get($path, $cookies = array(), $receive_data = true)
    {
        $t = microtime(true);
        $ret = parent::get($path, $cookies, $receive_data);
        if ($ret !== false)
        {
            IMDB::log_imdb_http("(".str_pad(round((microtime(true)-$t)*1000, 0), 4, " ", STR_PAD_LEFT) . "ms) Fetched http://$this->host$path");
        }
        return $ret;
    }
}