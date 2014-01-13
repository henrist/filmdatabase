<?php

class hs_filmdb_imdb {
	const IMDB_LOG = "imdb_http.log";
	
	public static function log_imdb_http($msg)
	{
		file_put_contents(self::IMDB_LOG, date("r") . " $msg\n", FILE_APPEND);
	}
	
	/**
	 * Søk etter en film
	 */
	public static function search($string)
	{
		$req = new httpreq_imdbdata();
		$req->host = "www.imdb.com";
		
		$data = $req->get("/find?s=tt&mx=5&q=".urlencode($string));
		if (!$data) return false;
		
		// har vi funnet nøyaktig treff?
		if (strpos($data['headers'], "Location:") !== false)
		{
			$match = array();
			preg_match("~Location: .*/title/(tt[0-9]+)~i", $data['headers'], $match);
			if (isset($match[1])) return $match[1];
		}
		
		// sett opp liste over de 5 første treffene
		$ret = array();
		
		$matches = array();
		preg_match_all('~<a href="/title/(tt[0-9]+)/.+?" >(.+?)</a>~i', $data['content'], $matches, PREG_SET_ORDER);
		
		$i = 0;
		$id_list = array();
		foreach ($matches as $match)
		{
			if (substr($match[2], 0, 4) == "<img") continue;
			if (in_array($match[1], $id_list)) continue;
			
			$ret[] = array(
				"id" => $match[1],
				"title" => strip_tags(html_entity_decode($match[2]))
			);
			
			$id_list[] = $match[1];
			if ($i++ == 5) break;
		}
		
		return $ret;
	}
	
	/**
	 * Last inn imdb-data for en film
	 */
	public static function get_imdb_data($imdb_id)
	{
		// hent data
		$req = new httpreq_imdbdata();
		$req->host = "www.imdb.com";
		
		$data = $req->get("/title/$imdb_id/");
		if (!$data)
		{
			return "request-failed";
		}
		
		// fant ikke?
		if (strpos($data['headers'], "200 OK") === false)
		{
			return "not-found";
		}
		
		return $data['content'];
	}
}