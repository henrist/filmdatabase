<?php

global $filmdb, $template;


// hent inn filmer og sett opp data
$filmer = $filmdb->get_all_movies_grouped();

// sorter etter navn
$t_n = array();
foreach ($filmer['indexed'] as $film)
{
	$t_n[] = $film->get("title");
}
array_multisort($t_n, $filmer['indexed']);

// sett opp filmdata
$js_data = array();
$js_genres = array();
$js_genres_count = array();
$js_actors = array();
$plots[] = array();
$i = 0;
foreach ($filmer['indexed'] as $film)
{
	$genres = $film->get("genres");
	if (!$genres) $genres = array();

	foreach ($genres as $genre)
	{
		if (!in_array($genre, $js_genres)) $js_genres[] = $genre;

		// antall filmer i hver sjanger
		if (!isset($js_genres_count[$genre])) $js_genres_count[$genre] = 0;
		$js_genres_count[$genre]++;
	}

	// hent filminfo
	$data = $film->get_movie_details();
	$codec = isset($data['video'][0]['codec_name']) ? $data['video'][0]['codec_name'] : false;
	$res = isset($data['video'][0]['width']) ? $data['video'][0]['width']."x".$data['video'][0]['height'] : false;

	$norsub = false;
	$sub = isset($data['subtitle']);
	if (isset($data['subtitle']))
	{
		foreach ($data['subtitle'] as $sub)
		{
			if (isset($sub['TAG:language']) && $sub['TAG:language'] == "nor")
			{
				$norsub = true;
				break;
			}
		}
	}

	$keywords = $film->get("keywords");
	if (!$keywords) $keywords = array();

	// hent skuespillere
	$actors_id = $film->get("actors_name_id");
	if ($actors_id)
	{
		$actors_list = $film->get("actors");
		foreach ($actors_id as $id => $val)
		{
			$js_actors[$val] = $actors_list[$id];
		}
	}
	else $actors_id = array();

	// DVDR, 720 eller 1080?
	#$type = "";
	#if (preg_match("/(DVDR|1080|720)-U?KOMP/", $film->path, $matches))
	#{
	#	$type = $matches[1];
	#}
	#elseif (preg_match("/Filmer-(1080|720)/", $film->path, $matches))
	#{
	#	$type = $matches[1];
	#}

	// legg til 720 eller 1080 i tittelen
	$type = "";
	if (preg_match("/\\/(1080-U?KOMP|Filmer-1080|1080)\\//", $film->path))
	{
		$type .= "1080";
	}
	elseif (preg_match("/\\/(720-U?KOMP|Filmer-720|720)\\//", $film->path))
	{
		$type .= "720";
	}
	elseif (preg_match("/\\/(DVDR-U?KOMP|Filmer-DVDR|dvdr)\\//i", $film->path))
	{
		$type .= "DVDR";
	}

	$js_data[$film->path_id] = array(
		"plot" => "test",
		"genres" => $genres,
		"codec" => $codec,
		"res" => $res,
		"width" => $res ? $data['video'][0]['width'] : false,
		"height" => $res ? $data['video'][0]['height'] : false,
		"norsub" => $norsub,
		"sub" => $sub,
		"imdb_id" => $film->get_imdb_id(),
		"title" => $film->get("title"),
		"aka" => $film->get("aka"),
		"year" => $film->get("year"),
		"rating" => $film->get("rating"),
		"has_poster" => $film->has_poster(),
		"runtime" => $film->get("runtime"),
		"keywords" => $keywords,
		"actors" => $actors_id,
		"type" => $type
	);

	$plots[$film->path_id] = $film->get("plot");

	#if ($i++ == 10) break;
}
sort($js_genres);
asort($js_actors);

$template->js .= '
$(function() {
	filmdata.data = ' . js_encode($js_data) . ';
	filmdata.genres = ' . js_encode($js_genres) . ';
	filmdata.genres_count = ' . js_encode($js_genres_count) . ';
	filmdata.actors = ' . js_encode($js_actors) . ';
	filmdata.init();

	// FIXME new OverText(document.id("dur_from"));
	// FIXME new OverText(document.id("dur_to"));
});';

$template->css .= '
';

$ret = '
<h1>Filmdatabase <i style="font-size: 70%; font-weight: normal">av <a href="http://henrist.net/">Henrik</a></i></h1>
<p>Denne siden er en indeksert oversikt over alle filmene jeg har, og hvor det er mulig å søke og filtrere ut listen for å finne en passende film å se. Det er et delvis uferdig produkt, og det hender jeg tar noen timer når jeg er ledig og oppdaterer med litt ny funksjonalitet. Oversikten er generert med data fra IMDB.</p>
<p><a href="?manage">Behandle liste og indekser filmer &raquo;</a></p>

<fieldset class="hide" id="filterarea">
	<legend>Filtrering</legend>
	<p>
		<b>Filmtittel:</b> <input type="text" id="soketter" class="styled" />
		<b>Nøkkelord:</b> <input type="text" id="sokkeywords" class="styled" />
	</p>
	<p class="genre_wrap"><span class="genre_pre"><b>Må inneholde sjangerene:</b></span> <span id="genres_positive" class="genres_boxes"></span></p>
	<p class="genre_wrap"><span class="genre_pre"><b>Kan ikke inneholde sjangerene:</b></span> <span id="genres_negative" class="genres_boxes"></span></p>
	<p><span class="filmdb_filter_name"><b>Årstall:</b></span>
		<input type="text" id="year" class="yearinput styled" style="width: 50px" />
		<span id="year2c" class="hide"> - <input type="text" id="year2" class="yearinput styled" style="width: 50px" /></span>
		<span class="year_options">
			<input type="radio" name="yeartype" value="exact" id="yearexact" checked="checked" /><label for="yearexact"> Spesifisert</label>
			<input type="radio" name="yeartype" value="before" id="yearbefore" /><label for="yearbefore"> Eldre</label>
			<input type="radio" name="yeartype" value="after" id="yearafter" /><label for="yearafter"> Nyere</label>
			<input type="radio" name="yeartype" value="between" id="yearbetween" /><label for="yearbetween"> Mellom</label>
		</span>
	</p>
	<p><span class="filmdb_filter_name"><b>Varighet:</b></span>
		<input type="text" id="dur_from" class="duration styled" title="minst" /> -
		<input type="text" id="dur_to" class="duration styled" title="lengst" />
	</p>
	<p><span class="filmdb_filter_name"><b>Skuespiller:</b></span>
		<input type="text" id="actor" class="styled" />
	</p>
	<p><span class="filmdb_filter_name"><b>Kvalitet:</b></span>
		<span class="type_options">
			<input type="checkbox" name="type_1080" id="type_1080" value="1080" /><label for="type_1080"> 1080</label>
			<input type="checkbox" name="type_720" id="type_720" value="720" /><label for="type_720"> 720</label>
			<input type="checkbox" name="type_dvdr" id="type_dvdr" value="DVDR" /><label for="type_dvdr"> DVDR</label>
			<input type="checkbox" name="type_lowres" id="type_lowres" value="" /><label for="type_lowres"> Lowres</label>
		</span>
	</p>
</fieldset>
<fieldset class="hide" id="setuparea">
	<legend>Oppsett</legend>
	<p id="enabled_cols">Synlige kolonner:';

$felter = array(
	1 => array("Bilder", false, "accesskey" => "b"),
	6 => array("Sjangre"),
	7 => array("Oppløsning"),
	8 => array("Codec"),
	9 => array("Integrert undertekst?"),
	10 => array("Mappe", false, "accesskey" => "m"),
	11 => array("Tid lastet inn", false, "accesskey" => "t"),
	12 => array("Plot")
);
foreach ($felter as $id => $p)
{
	$k = isset($p['accesskey']) ? $p['accesskey'] : null;

	$ret .= '
		<input type="checkbox" id="showcol_'.$id.'"'.(!isset($p[1]) || $p[1] ? ' checked="checked"' : '').($k ? ' accesskey="'.$k.'"' : '').' /><label for="showcol_'.$id.'"> '.$p[0].'</label>';
}

$ret .= '
	</p>
	<!--<p><input type="checkbox" id="showposters" checked="checked" /><label for="showposters"> Vis bilde for filmene</label></p>
	<p><input type="checkbox" id="showpaths" accesskey="m" /><label for="showpaths"> Vis mappeplassering for filmene</label></p>
	<p><input type="checkbox" id="showcreated" accesskey="t" /><label for="showcreated"> Vis når filmer ble lastet inn</label></p>-->
</fieldset>
<p><b>Antall filmer:</b> <span id="countsearch"></span>'.count($js_data).' <span class="tips"><b>Tips:</b> Tabellen kan sorteres ved å trykke på kolonnene!</span></p>
<table class="table" id="filmer">
	<thead>
		<tr>
			<th data-sorter="false" class="table-th-nosort">&nbsp;</th>
			<th>Navn</th>
			<th>År</th>
			<th class="sorter-movie_rating">Rating</th>
			<th class="sorter-movie_time">Spilletid</th>
			<th data-sorter="false" class="table-th-nosort">Sjangre</th>
			<th class="sorter-movie_resolution">Oppløsning</th>
			<th>Codec</th>
			<th>Integrert<br />undertekst?</th>
			<th>Mappeplassering</th>
			<th>Tid lastet inn</th>
			<th data-sorter="false" class="table-th-nosort">Plot</th>
		</tr>
	</thead>
	<tbody>';

foreach ($js_data as $id => $data)
{
	$film = $filmer['indexed'][$id];
	$codec = $data['codec'] ?: "&nbsp;";

	// DVDR, 720 eller 1080?
	$res = "";
	if (preg_match("/\\/(DVDR|1080|720)(-U?KOMP)?\\//i", $film->path, $matches) || preg_match("/Filmer-(DVDR|720|1080)/", $film->path, $matches))
	{
		$res = '<b>['.$matches[1].']</b>';
	}
	$res = $data['res'] ? $data['res'] . ($res ? "<br />" . $res : "") : ($res ?: "&nbsp;");

	$genres = count($data['genres']) > 0 ? implode("<br />", $data['genres']) : "&nbsp;";

	$aka = $data['aka'] ? '<br /><span class="film-aka">'.implode("<br />", $data['aka']).'</span>' : '';

	$ret .= '
		<tr rel="'.$id.'">
			<td'.($data['has_poster'] ? '>&nbsp;' : ' class="noposter">Mangler').'</td>
			<td>'.htmlentities($data['title']).$aka.'</td>
			<td>'.$data['year'].'</td>
			<td>'.$data['rating'].'</td>
			<td>'.($data['runtime'] ? $data['runtime'] : "&nbsp;").'</td>
			<td>'.$genres.'</td>
			<td>'.$res.'</td>
			<td>'.$codec.'</td>
			<td>'.($data['norsub'] ? 'Norsk' : ($data['sub'] ? 'Ja' : '&nbsp;')).'</td>
			<td>'.htmlspecialchars($film->path).'</td>
			<td class="nowrap">'.date("Y-m-d H:i", $film->get_indexed_time()).'</td>
			<td style="font-size: 11px">'.htmlspecialchars(html_entity_decode(str_replace("&#x27;", "'", $plots[$id]))).'<br /><a href="http://www.imdb.com/title/'.$data['imdb_id'].'/" target="_blank">'.$data['imdb_id'].'</a></td>
		</tr>';
}

echo $ret . '
	</tbody>
</table>';


