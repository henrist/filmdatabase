<?php

class hs_filmdb_film
{
	public $path;
	public $path_id;
	public $path_name;
	protected $imdb_id;
	protected $imdb_id_custom; // flagg: settes til true for å overkjøre cache = false (når id er satt)
	protected $imdb_data;
	public $cache;
	public $cache_movie;
	
	const FILE_IMDB_ID = ".filmdata-imdb-id";
	const FILE_IMDB_CACHE = ".filmdata-imdb-cache";
	const FILE_IMDB_CACHE_S = ".filmdata-imdb-cache-serialized";
	const FILE_IMDB_POSTER = ".filmdata-imdb-poster.jpg";
	const FILE_IMDB_POSTER_SRC = ".filmdata-imdb-poster-src";
	const FILE_FETCH_FAILED = ".filmdata-fetch-failed";
	const FILE_MOVIE_METADATA = ".filmdata-metadata";
	
	/**
	 * @var hs_filmdb
	 */
	protected $filmdb;
	
	public function __construct($path, hs_filmdb $filmdb)
	{
		$this->path = rtrim(str_replace("\\", "/", $path), "/");
		$this->filmdb = $filmdb;
		
		// sett opp mappenavnet
		if (($pos = strrpos($this->path, "/")) !== false) {
			$this->path_name = substr($this->path, $pos+1);
		} else {
			$this->path_name = $this->path;
		}
		
		$this->path_id = substr(md5($this->path), 0, 15);
	}
	
	/**
	 * Hent et bestemt felt med informasjon om filmen
	 */
	public function get($field)
	{
		if ($this->cache === null) $this->load_cache();
		if (isset($this->cache[$field])) return $this->cache[$field];
		
		return null;
	}
	
	/**
	 * Sjekk om vi har cache
	 */
	public function has_cache()
	{
		return file_exists($this->path."/".self::FILE_IMDB_ID) && file_exists($this->path."/".self::FILE_IMDB_CACHE);
	}
	
	/**
	 * Har vi poster?
	 */
	public function has_poster()
	{
		return file_exists($this->path."/".self::FILE_IMDB_POSTER);
	}

	/**
	 * Hent inn cache over metadata
	 */
	public function load_cache($cache = true, $save = true)
	{
		// forsøk og hent global cache
		if ($save || $cache)
		{
			if ($this->cache_global_load($cache)) return;
		}
		
		$path = $this->path."/".self::FILE_IMDB_CACHE_S;
		if ($cache && file_exists($path))
		{
			$this->cache = unserialize(file_get_contents($path));
			return;
		}
		
		// parse
		$this->parse_cache();
		
		// har vi cache å lagre?
		if ($this->cache && $save)
		{
			file_put_contents($path, serialize($this->cache));
		}
	}
	
	/**
	 * Gå over cache og sett opp filminformasjon
	 */
	protected function parse_cache()
	{
		// hent inn data om nødvendig
		if ($this->imdb_data === null) $this->get_imdb_page();
		if ($this->imdb_data === null) return false;
		
		$this->cache = array();
		$r = null;
		
		// tittel og år
		//if (preg_match("~<title>(.+?) \\(.*(\\d{4})\\)( - IMDb)?</title>~i", $this->imdb_data, $r))
		if (preg_match("~og:title\" content=\"(.+?) \\(.*(\\d{4})\\)\"/>~i", $this->imdb_data, $r))
		{
			$this->cache['title'] = str_replace("&#x27;", "'", strip_tags(html_entity_decode($r[1])));
			$this->cache['year'] = (int) $r[2];
		}
		
		// coverinfo (poster)
		$m = preg_match("~href=\"(/media/rm[^\"]+)\".+?src=\"([^\"]+)~", $this->imdb_data, $r); // ny versjon
		if (!$m) $m = preg_match("~<a name=\"poster\" href=\"([^\"]+)\".+?src=\"([^\"]+)~", $this->imdb_data, $r); // gammel versjon
		if ($m)
		{
			$this->cache['poster_href'] = $r[1];
			$this->cache['poster_src'] = $r[2];
		}
		
		// rating
		$m = preg_match('~ratingValue">([0-9\\.]+)<.*\\n.+?ratingCount">(.+?)<~', $this->imdb_data, $r);
		//if (!$m) $m = preg_match('~<b>([0-9\\.]+)/10</b>.+?____>([0-9,]+) votes~s', $this->imdb_data, $r);
		if ($m)
		{
			$this->cache['rating'] = (float) $r[1];
			$this->cache['rating_votes'] = (int) str_replace(",", "", $r[2]);
		}
		
		// genre
		if (preg_match_all('~<a href="/(?:genre|Sections/Genres)/.+?">([a-z\\-]+)</a>~i', $this->imdb_data, $r))
		{
			$this->cache['genres'] = $r[1];
		}
		
		// director
		if (preg_match('~(?:<h4[^\\n]+\\n +Director:|<div id="director-info).+?</div>~s', $this->imdb_data, $r))
		#if (preg_match('~<div id="director-info.+?</div>~s', $this->imdb_data, $r))
		{
			if (preg_match_all('~<a +href="/name/([^/]+)/[^>]+>([^<]+)</a~', $r[0], $r2))
			{
				$this->cache['directors_name_id'] = $r2[1];
				$this->cache['directors'] = $r2[2];
			}
		}
		
		// credits
		if (preg_match('~(?:<h4[^\\n]+\\n +Writers?:|<h5>Writers).+?</div>~s', $this->imdb_data, $r))
		{
			if (preg_match_all('~<a +href="/name/([^/]+)/[^>]+>([^<]+)</a>(?: \\(([^\\)]+)\\))?~', $r[0], $r2))
			{
				$this->cache['credits_name_id'] = $r2[1];
				$this->cache['credits'] = $r2[2];
				$this->cache['credits_details'] = $r2[3];
			}
		}
		
		// actors og characters (cast)
		if (preg_match('~<table class="cast(_list)?">.+?</table>~s', $this->imdb_data, $r))
		{
			if (preg_match_all('~"nm"><a href="/name/([^/]+)/[^>]+>([^<]+)</a>.+?"char">(.+?)</td>~', $r[0], $r2))
			{
				$this->cache['actors_name_id'] = $r2[1];
				$this->cache['actors'] = $r2[2];
				$this->cache['characters'] = $r2[3];
			}
			
			elseif (preg_match_all('~<tr.+?</tr>~s', $r[0], $r3, PREG_SET_ORDER))
			{
				foreach ($r3 as $r)
				{
					if (preg_match('~"/name/([^/]+)/[^>]+>([^<]+)</a>.+?/character/[^/]+/">([^<]+)~s', $r[0], $r2))
					{
						$this->cache['actors_name_id'][] = $r2[1];
						$this->cache['actors'][] = $r2[2];
						$this->cache['characters'][] = $r2[3];
					}
				}
			}
		}
		
		// plot
		$m = preg_match('~<h5>Plot:.+?<div[^>]+>(.+?)(<a class|</div)~s', $this->imdb_data, $r);
		if (!$m) $m = preg_match('~overview-top.+?<p>(.+?)</p>~s', $this->imdb_data, $r);
		if ($m)
		{
			$this->cache['plot'] = trim(strip_tags(html_entity_decode($r[1])));
		}
		
		// plot keywords
		if (preg_match('~(?:<h4 class="inline">Plot Keywords|<h5>Plot Keywords).+?</div>~s', $this->imdb_data, $r))
		{
			if (preg_match_all('~<a href="/keyword/([^/]+)/?">([^<]+)</a>~', $r[0], $r2))
			{
				$this->cache['keywords'] = $r2[2];
			}
		}
		
		// tagline
		// TODO: ny imdb-versjon?
		if (preg_match('~<h5>Tagline:.+?<div[^>]+>(.+?)<~s', $this->imdb_data, $r))
		{
			$this->cache['tagline'] = trim(strip_tags(html_entity_decode($r[1])));
		}
		
		// lanseringsdato
		// TODO: ny imdb-versjon?
		if (preg_match('~<h5>Release Date:.+?<div[^>]+>(.+?)<~s', $this->imdb_data, $r))
		{
			$this->cache['releasedate'] = trim(strip_tags(html_entity_decode($r[1])));
		}
		
		// spilletid
		if (preg_match('~(?:<h4 class="inline">|<h5>)Runtime:.+?(?:<div[^>]+>[^\d]+(\d+)|([0-9]+) min)~s', $this->imdb_data, $r))
		{
			$this->cache['runtime'] = $r[1] ? $r[1] : $r[2];
		}
		
		// aka
		if (preg_match('~<h4 class="inline">Also Known As:</h4> (.*)~', $this->imdb_data, $r))
		{
			$this->cache['aka'][] = trim($r[1]);
			$this->cache['aka_lang'][] = null;
		}
		elseif (preg_match('~<h5>Also Known As.+?<div[^>]+(.+?)</a>~s', $this->imdb_data, $r))
		{
			if (preg_match_all('~>(.+?) <em>(.+?)</em><br~s', $r[1], $r2))
			{
				$this->cache['aka'] = $r2[1];
				$this->cache['aka_lang'] = $r2[2];
			}
		}
		if (preg_match('~<span class="title-extra">.*\n(.*)\n(?:<i>\\((.*)\\))?~', $this->imdb_data, $r))
		{
			$this->cache['aka'][] = trim($r[1]);
			$this->cache['aka_lang'][] = isset($r[2]) ? trim($r[2]) : null;
		}
		
		// cache tid: hent ut tredje linja
		$lines = explode("\n", $this->imdb_data, 4);
		$time = strtotime(trim($lines[2]));
		$this->cache['cache_time'] = $time;
		
		// når mappen med filmen ble opprettet
		$this->cache['folder_created_time'] = $this->get_indexed_time(); // henter fra cache hvis det finnes
	}
	
	/**
	 * Sett IMDB-ID
	 */
	public function set_imdb_id($id)
	{
		$this->imdb_id = $id;
		$this->imdb_id_custom = true;
		
		// lagre imdb-id
		if (!file_put_contents($this->path."/".self::FILE_IMDB_ID, $id))
		{
			throw new Exception("Kunne ikke lagre film-ID.");
		}
	}
	
	/**
	 * Finn IMDB-ID for filmen
	 */
	public function get_imdb_id($cache = true, $save = true, $fetch = false)
	{
		if ($this->imdb_id_custom) return $this->imdb_id;
		if ($cache && $this->imdb_id) return $this->imdb_id;
		$id = false;
		
		// har vi id-cache?
		if ($cache && file_exists($this->path."/".self::FILE_IMDB_ID))
		{
			$id = trim(file_get_contents($this->path."/".self::FILE_IMDB_ID));
			$this->imdb_id = $id;
			return $id;
		}
		
		// sjekk for .nfo filer
		$nfo_files = hs_filmdb::search_folder($this->path, function($folder, $file)
		{
			if (!is_file($folder."/".$file)) return false;
			if (substr($file, -4) != ".nfo") return false;
			return true;
		});
		
		if (count($nfo_files) > 0)
		{
			foreach ($nfo_files as $file)
			{
				$file = $this->path ."/" . $file;
				
				// sjekk for imdb-lenke i nfo-filene
				$matches = array();
				if (preg_match_all("#http://[a-z\\.]*imdb.com/title/(tt[0-9]+)(/|\\s)#", @file_get_contents($file), $matches))
				{
					$prev_id = false;
					foreach ($matches[1] as $v)
					{
						if ($prev_id !== false && $prev_id != $v)
						{
							continue 2;
						}
						$prev_id = $v;
					}
					
					// kun 1 unik id ble funnet
					$id = $prev_id;
					break;
				}
			}
		}
		
		// må vi søke etter filmen?
		if (!$id && $fetch)
		{
			$search = $this->get_clean_name();
			
			// velg første film vi finner
			$res = hs_filmdb_imdb::search($search);
			if ($res === false)
			{
				$this->set_failed_status("HTTP-request failed for IMDB-search");
				return false;
			}
			
			if (is_array($res))
			{
				// fant ingen treff?
				if (count($res) == 0)
				{
					$this->set_failed_status("No search match.");
					return false;
				}
				
				$id = $res[0]['id'];
			}
			
			else
			{
				$id = $res;
			}
		}
		
		// lagre id?
		if ($id && $save)
		{
			if (!file_put_contents($this->path."/".self::FILE_IMDB_ID, $id))
			{
				throw new Exception("Kunne ikke lagre film-ID.");
			}
		}
		
		$this->imdb_id = $id;
		return $id;
	}
	
	/**
	 * Hent ut HTML-siden til filmen på IMDB
	 */
	protected function get_imdb_page($cache = true, $save = true, $fetch = false)
	{
		// benytte cache?
		if ($cache && file_exists($this->path."/".self::FILE_IMDB_CACHE))
		{
			$this->imdb_data = file_get_contents($this->path."/".self::FILE_IMDB_CACHE);
			return true;
		}
		
		// ikke hente data?
		if (!$fetch)
		{
			return false;
		}
		
		$this->cache = null;
		
		// hent ut ID for filmen
		$id = $this->get_imdb_id($cache, $save, true);
		if (!$id) return false;
		
		// hent data
		$data = hs_filmdb_imdb::get_imdb_data($id);
		switch ($data) {
			case "request-failed":
				$this->set_failed_status("HTTP request failed");
				return false;
			
			case "not-found":
				$this->set_failed_status("Movie ID $id incorrect");
				return false;
		}
		
		// lagre data?
		if ($save)
		{
			// flytt gammel fil
			if (file_exists($this->path."/".self::FILE_IMDB_CACHE))
			{
				rename($this->path."/".self::FILE_IMDB_CACHE, $this->path."/".self::FILE_IMDB_CACHE."-previous");
			}
			
			file_put_contents($this->path."/".self::FILE_IMDB_CACHE, "HenriSt filminfo\n$id\n".date("r")."\n\n".$data);
		}
		
		$this->imdb_data = $data;
		return true;
	}
	
	/**
	 * Sørg for at vi har hentet ned informasjon om filmen og lagret cache
	 */
	public function build_cache($rebuild = false)
	{
		$file_cache = $this->path."/".self::FILE_IMDB_CACHE;
		$file_id = $this->path."/".self::FILE_IMDB_ID;
		$file_poster_src = $this->path."/".self::FILE_IMDB_POSTER_SRC;
		$file_poster = $this->path."/".self::FILE_IMDB_POSTER;
		
		// sjekk om vi har cache i mappen
		if (!$rebuild && file_exists($file_cache) && file_exists($file_id) && file_exists($file_poster)) return null;
		
		// hent data
		if (!$this->get_imdb_page(!$rebuild, true, true))
		{
			return false;
			#throw new Exception("Kunne ikke hente data for filmen.");
		}
		
		// behandle html
		$this->load_cache(false);
		
		$this->build_cache_poster($rebuild);
		
		// sjekk for metadata
		$this->get_movie_details(false);
		
		return true;
	}
	
	/**
	 * Sørg for at poster er lastet ned
	 */
	public function build_cache_poster($rebuild = false)
	{
		// sjekk for poster
		if ($poster_src = $this->get("poster_src"))
		{
			$file_poster_src = $this->path."/".self::FILE_IMDB_POSTER_SRC;
			$file_poster = $this->path."/".self::FILE_IMDB_POSTER;
			
			// har vi denne allerede? (sjekk også for korrekt bilde)
			if ($rebuild || (!file_exists($file_poster_src) || !file_exists($file_poster) || @file_get_contents($file_poster_src) != $poster_src))
			{
				// hent ny poster
				$t = microtime(true);
				$data = file_get_contents($poster_src);
				if ($data === false)
				{
					throw new Exception("Kunne ikke hente poster for filmen: $poster_src");
				}
				
				hs_filmdb_imdb::log_imdb_http("(".str_pad(round((microtime(true)-$t)*1000, 0), 4, " ", STR_PAD_LEFT) . "ms) Fetched $poster_src");
				
				file_put_contents($file_poster, $data);
				file_put_contents($file_poster_src, $poster_src);
			}
		}
	}
	
	/**
	 * Hvor gammel cache har vi?
	 */
	public function get_cache_time()
	{
		$time = $this->get("cache_time");
		if ($time) return $time;
		
		// parse cache på nytt
		$this->load_cache(false);
		
		return $this->get("cache_time");
	}
	
	/**
	 * Rens et mappenavn
	 */
	public static function clean_name($name)
	{
		$tags_break = array("x264", "repack", "unrated", "internal", "proper", "dvdr", "bluray", "xvid", "divx", "pal", "ntsc", "dvdrip", "r5", "720p", "1080p", "ws", "limited", "dvdxvid", "director's", "directors", "25fps", "telesync", "uncorked", "dircut");
		$tags_ignore = array("ac3", "aac", "blu-ray", "complete", "custom", "dc", "divx", "dl", "docu", "dsr", "dubbed", "dvb", "dvbrip", "dvd5", "dvd9", "festival", "fs", "hddvd", "hdtv", "kvcd", "limited", "multisubs", "pdtv", "ppv", "recode", "remastered", "remux", "repack", "rerip", "se", "stv", "subbed", "svcd", "tvrip", "unrated", "vcd", "vhsrip", "ws", "xvid", "retail", "eng", "dvdscr", "subfix", "nordic", "ind", "int");
		
		$name = preg_replace("/  +/", " ", preg_replace("/[()\\[\\]{}\\-_]/", " ", $name));
		
		$name = preg_split("/[\\. ]/", $name);
		
		// finn ut om vi har årstall som gjør at vi ikke skal skippe tekst
		$min_x = 0;
		foreach ($name as $k => $r)
		{
			// årstall?
			if (preg_match("/^(19|20)[0-9]{2}$/", $r))
			{
				$min_x = $k;
			}
		}
		
		$nq = array();
		foreach ($name as $k => $r)
		{
			if ($k > $min_x && in_array(strtolower($r), $tags_break)) break;
			if ($k > $min_x && in_array(strtolower($r), $tags_ignore)) continue;
			
			// årstall?
			if (preg_match("/^(19|20)[0-9]{2}$/", $r))
			{
				$nq[] = "($r)";
				break;
			}
			else $nq[] = $r;
		}
		
		return implode(" ", $nq);
	}
	
	public function get_clean_name()
	{
		return self::clean_name($this->path_name);
	}
	
	/**
	 * Hent data for poster
	 */
	public function get_poster_data()
	{
		return @file_get_contents($this->path."/".self::FILE_IMDB_POSTER);
	}
	
	/**
	 * Feilet filmen indeksering?
	 */
	public function is_fetch_failed()
	{
		if ($this->has_cache()) return false;
		
		return file_exists($this->path."/".self::FILE_FETCH_FAILED);
	}
	
	/**
	 * Hent feilmelding ved indekseringsforsøk
	 */
	public function get_failed_data()
	{
		if (!$this->is_fetch_failed()) return false;
		
		return file_get_contents($this->path."/".self::FILE_FETCH_FAILED);
	}
	
	/**
	 * Sett feilmelding ved indekseringsforsøk
	 */
	protected function set_failed_status($msg)
	{
		file_put_contents($this->path."/".self::FILE_FETCH_FAILED, date("r") . ": " . $msg);
	}
	
	/**
	 * Forsøk å finn detaljer om filmfilen i filmmappen
	 */
	public function get_movie_details($cache = true, $save = true)
	{
		if ($this->cache_movie !== null)
		{
			return $this->cache_movie;
		}
		
		// forsøk og hent global cache
		if ($save || $cache)
		{
			if ($this->cache_global_load($cache)) return $this->cache_movie;
		}
		
		// sjekk for cache
		$path = $this->path."/".self::FILE_MOVIE_METADATA;
		if ($cache && file_exists($path))
		{
			return unserialize(file_get_contents($path));
		}
		
		// list opp alle filene i mappen
		$files = hs_filmdb::search_folder($this->path, function($folder, $file)
		{
			return is_file($folder."/".$file);
		});
		
		// generer cache
		$ret = NULL;
		foreach ($files as $file)
		{
			if (preg_match("~(^\\.|\\.(rar|r[0-9]{2}|nfo|sfv|jpg|png|gif|zip)\$)~i", $file)) continue;
			
			// forsøk å lese med ffmpeg
			$ffmpeg = $this->filmdb->get_ffmpeg();
			if ($ffmpeg && ($data = $ffmpeg->get_moviefile_details($this->path."/".$file)))
			{
				$ret = $data;
				break;
			}
		}
		
		// lagre?
		if ($save)
		{
			file_put_contents($path, serialize($ret));
		}
		
		return $ret;
	}
	
	/**
	 * Har vi sett denne filmen?
	 */
	public function is_seen()
	{
		return hs_filmdb::is_seen($this->get_imdb_id());
	}
	
	/**
	 * Sett om en film er sett eller ikke
	 */
	public function set_seen($seen = true)
	{
		return hs_filmdb::set_seen($this->get_imdb_id(), $seen);
	}
	
	/**
	 * Hent ut tidspunkt for når mappa ble opprettet
	 */
	public function get_indexed_time($cache = true)
	{
		// cachet?
		if ($cache && ($val = $this->get("folder_created_time")))
		{
			return $val;
		}
		
		return filectime($this->path."/.filmdata-imdb-cache");
	}
	
	/**
	 * Hent info fra global cache
	 */
	public function cache_global_load($cache = true)
	{
		static $is_loading = false;
		if ($is_loading) return false; // avoid recursive calls
		$is_loading = true;
		
		// forsøk og last inn cache
		$res = $cache ? $this->filmdb->cache_get($this->path_id) : null;
		if (!$res)
		{
			// generer cache
			$this->filmdb->cache_set($this, $cache);
		}
		
		else
		{
			$this->cache_movie = $res['movie_details'];
			$this->cache = $res['imdb'];
		}
		
		$is_loading = false;
		return true;
	}
}