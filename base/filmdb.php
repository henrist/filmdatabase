<?php

set_time_limit(0);
define("HS_FILMDB_WIN", stripos(PHP_OS, 'win') !== false);

class hs_filmdb
{
	/**
	 * @var hs_filmdb_set
	 */
	public $set;
	
	/**
	 * Mellomlagring av filmene vi har lastet inn
	 */
	public $movies;
	
	/**
	 * Konstruktør...
	 */
	public function __construct()
	{
		// last inn konfigurasjon
		if (!file_exists(dirname(__FILE__)."/config.php")) {
			throw new Exception("Konfigurasjonsfil mangler.");
		}
		require "config.php";
		$this->set = new hs_filmdb_set();
		
		require "film.php";
		require "imdb.php";
		require "ffmpeg.php";
		require "misc.php";
	}
	
	public function init()
	{
		
	}
	
	/**
	 * Hent ut en bestemt film basert på path-id
	 * @return hs_filmdb_film
	 */
	public function get_movie_by_pathid($path_id)
	{
		// har vi ikke filminfo?
		if (!$this->movies)
		{
			// har vi cache?
			if (file_exists($this->set->file_path_id_cache))
			{
				$arr = array();
				$data = explode("\n", file_get_contents($this->set->file_path_id_cache));
				foreach ($data as $line)
				{
					$line = explode("=", trim($line), 2);
					if (!isset($line[1])) continue;
					$arr[$line[0]] = $line[1];
				}
				
				$this->movies = $arr;
			}
			
			else
			{
				// hent inn informasjon om alle filmene
				$this->get_all_movies();
			}
		}
		
		// har vi filminfo?
		if (isset($this->movies[$path_id]))
		{
			// må opprette filmobjekt?
			if (!is_object($this->movies[$path_id]))
			{
				$this->movies[$path_id] = new hs_filmdb_film($this->movies[$path_id], $this);
			}
			
			return $this->movies[$path_id];
		}
		
		// fant ikke filmen
		return null;
	}
	
	protected $ffmpeg;
	
	/**
	 * @return hs_filmdb_ffmpeg
	 */
	public function get_ffmpeg() {
		if ($this->ffmpeg) return $this->ffmpeg;
		
		require_once "ffmpeg.php";
		try {
			$ffmpeg = new hs_filmdb_ffmpeg($this->set->ffmpeg_bin_path);
		} catch (Exception $e) {
			
		}
		
		$this->ffmpeg = $ffmpeg;
		return $ffmpeg;
	}
	
	
	/** Liste over filmer som er sett */
	protected $seen;
	
	/**
	 * Sjekk om vi har sett en film
	 */
	public function is_seen($imdb_id, $cache = true)
	{
		// har vi liste over filmer som er sett?
		if (!$this->seen || !$cache)
		{
			$this->seen = explode("\n", @file_get_contents($this->set->file_seen));
		}
	}
	
	/**
	 * Marker en film som sett
	 */
	public function set_seen($imdb_id, $seen = true)
	{
		if ($this->is_seen($imdb_id))
		{
			if ($seen) return;
			else
			{
				unset($this->seen[array_search($imdb_id, $this->seen)]);
			}
		}
		
		elseif ($seen)
		{
			$this->seen[] = $imdb_id;
		}
		
		// lagre
		@file_put_contents($this->set->file_seen, implode("\n", $this->seen));
	}
	
	/**
	 * Last inn alle filmene
	 */
	public function get_all_movies($cache = true)
	{
		// har vi cache?
		static $loaded = false;
		if ($cache && $loaded) return $this->movies;
		$loaded = true;
		
		// sett opp filmliste og cache for path_id
		$this->movies = array();
		$cache = "";
		foreach ($this->set->paths as $path)
		{
			$liste = $this->search_folder($path, function($folder, $file)
			{
				return is_dir($folder."/".$file);
			});
			
			foreach ($liste as $name)
			{
				$film = new hs_filmdb_film($path . "/" . $name, $this);
				$this->movies[$film->path_id] = $film;
				
				// cache path_id
				$cache .= ($cache ? "\n" : "") . $film->path_id . "=" . $film->path;
			}
		}
		
		// lagre cache
		@file_put_contents($this->set->file_path_id_cache, $cache);
		
		return $this->movies;
	}
	
	/**
	 * Last inn alle filmene fordelt på gruppe ihht. indeksering
	 */
	public function get_all_movies_grouped()
	{
		$filmer = array(
			"indexed" => array(),
			"unknown" => array(),
			"failed" => array()
		);
		
		foreach ($this->get_all_movies() as $film)
		{
			if ($film->has_cache())
			{
				$filmer['indexed'][$film->path_id] = $film;
			}
			elseif ($film->is_fetch_failed())
			{
				$filmer['failed'][$film->path_id] = $film;
			}
			else
			{
				$filmer['unknown'][$film->path_id] = $film;
			}
		}
		
		return $filmer;
	}
	
	/**
	 * Kjør en funksjon for hver fil/mappe i en mappe
	 * @param string $folder mappen som skal gjennomsøkes
	 * @param function $fn_criteria funksjonen som kalles: fn(mappe, fil/mappe)
	 * @return array over alle filene/mappene som funksjonen returnerte true for
	 */
	public static function search_folder($folder, $fn_criteria)
	{
		$dh = @opendir($folder); // TODO: gi feilmelding hvis mappen ikke kan åpnes
		if (!$dh) return array();
		
		$ret = array();
		while (($file = readdir($dh)) !== false)
		{
			if ($file == "." || $file == ".." || !$fn_criteria($folder, $file)) continue;
			$ret[] = $file;
		}
		
		return $ret;
	}
}