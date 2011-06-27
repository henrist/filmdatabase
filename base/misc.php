<?php

class httpreq
{
	public $host = "localhost";
	public $actualhost = false;
	public $port = 80;
	public $timeout = 5;
	public $link = false;

	// koble til serveren
	function connect()
	{
		$errno = $errstr = false;
		$this->link = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

		if (!$this->link)
		{
			#trigger_error("Kunne ikke koble til {$this->host}. Feilmelding: $errstr", E_USER_WARNING);
			return false;
		}

		return true;
	}

	// utføre GET spørring
	function get($path, $cookies = array(), $receive_data = true)
	{
		// koble til
		if (!$this->connect()) return false;

		// sett opp headers
		$headers = array();
		$headers[] = "GET $path HTTP/1.0";
		$headers[] = "Host: ".($this->actualhost ? $this->actualhost : $this->host);
		#$headers[] = "User-Agent: HenriSt webfetcher (PHP ".phpversion().")";
		$headers[] = "Accept: application/x-shockwave-flash,text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";

		// -> sett opp cookies
		foreach ($cookies as $name => $cookie)
		{
			$headers[] = "Cookie: $name=$cookie";
		}

		$headers[] = "Connection: close";

		// send spørring
		@fputs($this->link, implode("\r\n", $headers)."\r\n\r\n");

		// hente data?
		if ($receive_data)
		{
			return $this->receive_data();
		}

		return $this->link;
	}

	// utføre POST spørring
	function post($path, $params = array(), $cookies = array(), $receive_data = true)
	{
		// koble til
		if (!$this->connect()) return false;

		// sett opp parametere
		$post = array();

		foreach ($params as $name => $item)
		{
			$name = urlencode($name);

			// array?
			if (is_array($item))
			{
				foreach ($item as $i)
				{
					$post[] = $name."[]=".urlencode($i);
				}
			}

			// string
			else
			{
				$post[] = $name."=".urlencode($item);
			}
		}

		// sett sammen
		$post = implode("&", $post);


		// headers
		$headers = array();
		$headers[] = "POST $path HTTP/1.0";
		$headers[] = "Host: ".($this->actualhost ? $this->actualhost : $this->host);

		// -> sett opp cookies
		foreach ($cookies as $name => $cookie)
		{
			$headers[] = "Cookie: $name=$cookie";
		}

		$headers[] = "Content-type: application/x-www-form-urlencoded";
		$headers[] = "Content-length: " . strlen($post);
		$headers[] = "Connection: close";

		// send spørring
		fputs($this->link, implode("\r\n", $headers)."\r\n\r\n".$post);

		// hente data?
		if ($receive_data)
		{
			return $this->receive_data();
		}

		return $this->link;
	}

	// hente data
	function receive_data()
	{
		// hent data
		$data = "";
		while (!@feof($this->link))
		{
			$data .= @fgets($this->link, 8192);
		}

		// del opp headers og innhold
		$pos = strpos($data, "\r\n\r\n");

		// hent headers og innhold
		$headers = substr($data, 0, $pos);
		$content = substr($data, $pos+4);

		// send svar
		return array("headers" => $headers, "content" => $content);
	}
}

class httpreq_imdbdata extends httpreq
{
	function get($path, $cookies = array(), $receive_data = true)
	{
		$t = microtime(true);
		$ret = parent::get($path, $cookies, $receive_data);
		if ($ret !== false)
		{
			hs_filmdb_imdb::log_imdb_http("(".str_pad(round((microtime(true)-$t)*1000, 0), 4, " ", STR_PAD_LEFT) . "ms) Fetched http://$this->host$path");
		}
		return $ret;
	}
}

/**
 * Formatter data så det kan brukes i JavaScript variabler osv
 * Ikke UTF-8 (slik som json_encode)
 *
 * @param string $value
 */
function js_encode($value)
{
	if (is_null($value)) return 'null';
	if ($value === false) return 'false';
	if ($value === true) return 'true';
	if (is_scalar($value))
	{
		if (is_string($value))
		{
			static $json_replace_from = array(
				"\\",
				'"',
				"/",
				"\x8",
				"\xC",
				"\n",
				"\r",
				"\t"
			);
			static $json_replace_to = array(
				"\\\\",
				'\\"',
				"\\/",
				"\\b",
				"\\f",
				"\\n",
				"\\r",
				"\\t"
			);

			return '"'.str_replace($json_replace_from, $json_replace_to, $value).'"';
		}

		return $value;
	}

	if (!is_array($value) && !is_object($value)) return false;

	$object = false;
	for ($i = 0, reset($value), $len = count($value); $i < $len; $i++, next($value))
	{
		if (key($value) !== $i)
		{
			$object = true;
			break;
		}
	}

	$result = array();
	if ($object)
	{
		foreach ($value as $k => $v) $result[] = js_encode($k).':'.js_encode($v);
		return '{'.implode(",", $result).'}';
	}

	foreach ($value as $v) $result[] = js_encode($v);
	return '['.implode(",", $result).']';
}