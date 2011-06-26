<?php

class hs_filmdb_restructure
{
	/**
	 * @var hs_filmdb_restructure_os
	 */
	protected $os;
	
	/**
	 * Konstruktør..
	 */
	public function __construct()
	{
		if (HS_FILMDB_WIN) $this->os = new hs_filmdb_restructure_os_win();
		else $this->os = new hs_filmdb_restructure_os_other();
	}
	
	/**
	 * Desc TODO
	 * @param array filmer fra hs_filmdb::get_all_movies_grouped()
	 */
	public function restructure($movies) {
		if (!isset($movies['indexed']))
		{
			throw new Exception("Ugyldig inndata.");
		}
		
		// opprett filmindeks-mappa
		$this->os->dir_mk($dirtemp);
		$this->os->dir_mk($dirtemp."/-- MERK1 -- FORBEHOLD OM FEIL INDEKSERING -- TITTEL KAN FRAVIKE --");
		$this->os->dir_mk($dirtemp."/-- MERK2 -- ALLE TITLER OG AARSTALL ER HENTET FRA IMDB --");
		
		// opprett mappe for nye filmer
		$this->os->dir_mk($newdirtemp);
		$this->os->file_touch($newdirtemp."/-- MERK1 -- DETTE ER EN OVERSIKT OVER NYESTE FILMER LAGT TIL --");
		$this->os->file_touch($newdirtemp."/-- MERK2 -- FILMER LAGT INN FOR MER ENN 10 DAGER SIDEN FJERNES VED INDEKSERING --");
		$this->os->file_touch($newdirtemp."/-- MERK3 -- HENT FILMEN FRA HOVEDMAPPA, DETTE ER KUN INFO --");
		
		
		echo '<pre>';
		
		$new_expire = time()-86400*10; // hvor langt tilbake skal vi lage indeks for nye filmer
		
		// sett opp lenkene
		$i = 0;
		foreach ($movies['indexed'] as $film)
		{
			// sett opp mappenavn
			$name = html_entity_decode($film->get("title"));
			$name .= " (".$film->get("year").")";
			
			// filminfo
			/*$data = $film->get_movie_details();
			if (isset($data['video'][0]['width']))
			{
				$res = false;
				if ($data['video'][0]['width'] == 1920 || $data['video'][0]['height'] == 1080)
				{
					$res = "1080";
				}
				
				elseif ($data['video'][0]['width'] == 1280 || $data['video'][0]['height'] == 720)
				{
					$res = "720";
				}
				
				if ($res) $name .= " [$res]";
			}*/
			
			// FIXME
			$name = str_replace("&#x27;", "'", $name);
			
			// legg til 720 eller 1080 i tittelen
			if (preg_match("/(1080|720)-U?KOMP/", $film->path, $matches))
			{
				$name .= " [{$matches[1]}]";
			}
			
			#$name .= " (".$data['video'][0]['width']."x".$data['video'][0]['height'].")";
			
			// rating
			#$name .= " [rating=".$film->get("rating")."]";
			
			// fiks ugyldige navn
			$name = preg_replace("/[\\\\\\/:*?\"<>|]/", "", $name);
			
			// sett opp alternativt navn
			$name_org = $name;
			$x = 2;
			while (file_exists($dirtemp."/".$name))
			{
				$name = $name_org . " ($x)";
				$x++;
			}
			
			/*// lagre lenke
			if (!symlink($film->path, $dir."\\".$name))
			{
				echo '
<p>Symlink feilet: '.$film->path.' til '.$dir.'\\'.$name.'</p>';
			}*/
			
			$this->os->dir_link($dirtemp."/".$namem, $film->path);
			
			echo $dir."/".$name."\n";
			
			// er denne filmen nyere enn 1 uke?
			$changed = $film->get_indexed_time();
			if ($changed > $new_expire)
			{
				// opprett indeks
				$t = date("Y-m-d Hi", $changed);
				$r = number_format((float) $film->get("rating"), 1, ",", "");
				$this->os->file_touch($newdirtemp."/".$t."   $r   $name");
			}
			
			$i++;
		}
		
		echo '</pre>';
		
		// slett filmindeks-mappa
		$this->os->dir_rm($dir);
		$this->os->dir_rm($newdir);
		
		// flytt temp-mappa
		$this->os->dir_mv($dirtemp, $dir);
		$this->os->dir_mv($newdirtemp, $newdir);
		
		echo '
<h1>Filmindeks</h1>
<p>Filmindeks skal nå være oppdatert med '.$i.' filmer.</p>';
	}
}


interface hs_filmdb_restructure_os {
	public function dir_mk($dir);
	public function dir_rm($dir);
	public function dir_mv($dir, $newdir);
	public function dir_link($dir_as_link, $target);
	
	public function file_touch($file);
}


class hs_filmdb_restructure_os_win implements hs_filmdb_restructure_os {
	public function dir_mk($dir)
	{
		shell_exec("mkdir \"".escapeshellarg($dir)."\"");
	}
	
	public function dir_rm($dir)
	{
		shell_exec("rmdir /S /Q \"".escapeshellarg($dir)."\"");
	}
	
	public function dir_mv($dir, $newdir)
	{
		shell_exec("move /Y \"".escapeshellarg($dir)."\" \"".escapeshellarg($newdir)."\"");
	}
	
	public function dir_link($dir_as_link, $target)
	{
		shell_exec("mklink /D \"".escapeshellarg($dir_as_link)."\" \"".escapeshellarg($target)."\"");
	}
	
	public function file_touch($file)
	{
		file_put_contents($file, "", FILE_APPEND);
	}
}

class hs_filmdb_restructure_os_other implements hs_filmdb_restructure_os {
	public function dir_mk($dir)
	{
		mkdir($dir);
	}
	
	public function dir_rm($dir)
	{
		shell_exec("rm -Rf \"".escapeshellarg($dir)."\"");
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
		file_put_contents($file, "", FILE_APPEND);
	}
}