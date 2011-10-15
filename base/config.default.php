<?php

/**
 * Kopier denne filen til config.php og endre den til å passe eget behov
 */


class hs_filmdb_set
{
	/** Liste over filmer som er sett */
	public $file_seen = ".filmdata-seen";
	
	/** Fil som inneholder cache over path_id */
	public $file_path_id_cache = ".filmdata-path-id-cache";
	
	/** Fil med cache for data */
	public $file_path_data_cache = ".filmdata-data-cache";
	
	/** Mapper med filmre som blir gjennomsøkt */
	public $paths = array(
		"C:/Adresse/til/film-mappe",
		"C:/Adresse/til/enda/en/mappe"
	);
	
	/**
	 * Binærmappe til ffmpeg (ffprobe blir brukt)
	 */
	public $ffmpeg_bin_path;
	
	/**
	 * Mappe for indeks
	 */
	public $index_dir = "C:\\FILMINDEKS";
	
	/**
	 * Midlertidig mappe for indeks (blir opprettet og slettet automatisk)
	 */
	public $index_dir_temp = "C:\FILMINDEKS-TEMP";
	
	/**
	 * Mappe for indeks over siste filmer
	 */
	public $index_new_dir = "C:\\FILMINDEKS-NYE";
	
	/**
	 * Midlertidig mappe for indeks over siste filmer (blir opprettet og slettet automatisk)
	 */
	public $index_new_dir_temp = "C:\\FILMINDEKS-NYE-TEMP";
}