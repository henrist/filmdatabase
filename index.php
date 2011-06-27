<?php

require "base/base.php";

// last inn template
require "base/template/template.php";
$template = new hs_filmdb_template();

/*if (isset($_GET['reindex']) || (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == "reindex"))
{
	self::reindex();
	die;
}*/

// indekser noen filmer
/*if (isset($_GET['index']) || (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == "index"))
{
	header("Content-Type: text/plain");
	ob_implicit_flush(true);
	ob_end_flush();
	$filmer = hs_imdbdata::get_all_movies_grouped();
	
	echo "Starting indexing...\n";
	
	$i = 0;
	foreach ($filmer['unknown'] as $film)
	{
		if ($i++ == 20) break;
		
		echo ".. ";
		$film->build_cache();
		
		echo "Indexed " . $film->get("title") . " (".$film->get("year").")\n";
	}
	
	echo "Finished";
	die;
}*/

// indeksere en bestemt film?
if (isset($_GET['indexs']))
{
	$film = $filmdb->get_movie_by_pathid($_GET['indexs']);
	if (!$film) die("Fant ikke filmen.");
	
	// har vi gitt imdb-id?
	if (isset($_POST['imdb_id']) && !empty($_POST['imdb_id']))
	{
		if (!preg_match("/^tt\\d{7}$/", $_POST['imdb_id'])) die("Ugyldig IMDB-ID.");
		$film->set_imdb_id($_POST['imdb_id']);
	}
	
	$film->build_cache(true);
	echo "Indekserte " . $film->get("title") . " (".$film->get("year").")";
	
	die;
}

//if (isset($_SERVER['argv'])) die("Running as CLI.");

// ønsker vi å hente ut bilde for en film?
if (isset($_GET['poster']))
{
	$film = $filmdb->get_movie_by_pathid($_GET['poster']);
	
	if ($film && ($poster = $film->get_poster_data()))
	{
		header("Content-Type: image/jpeg");
		header("Content-Length: ".strlen($poster));
		
		echo $poster;
		die;
	}
	
	header("HTTP/1.1 404 Not Found");
	die;
}

/*// bygge opp mappestruktur på nytt?
elseif (isset($_GET['restructure']))
{
	self::restructure();
}*/

// manage?
elseif (isset($_GET['manage']))
{
	require "base/template/manage.php";
}

// søkevisning
else
{
	require "base/template/list.php";
}


$template->render();



	/**
	 * Indeksere på nytt
	 */
	/*public static function reindex()
	{
		// hvor lenge skal vi beholde index?
		$max_age = 2592000; // 30 dager
		$limit = time() - $max_age;
		
		header("Content-Type: text/plain");
		ob_implicit_flush(true);
		ob_end_flush();
		$filmer = hs_imdbdata::get_all_movies_grouped();
		
		echo "Starting reindexing...\n";
		
		$i = 0;
		foreach ($filmer['indexed'] as $film)
		{
			// skal denne behandles?
			if ($film->get_cache_time() >= $limit) continue;
			
			#if ($i++ == 50) break;
			$i++;
			
			if ($i > 1)
			{
				echo 'sleep ';
				usleep(10000000);
			} 
			
			echo "fetching.. ";
			$film->build_cache(true);
			
			echo "Indexed " . $film->get("title") . " (".$film->get("year").")\n";
		}
		
		echo "Finished";
		die;
	}
*/