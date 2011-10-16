<?php

global $filmdb, $template;

// hent inn alle filmene
$filmer = $filmdb->get_all_movies_grouped();

// sorter etter navn
$t_n = array();
foreach ($filmer['indexed'] as $film)
{
	$t_n[] = $film->get("title");
}
array_multisort($t_n, $filmer['indexed']);

echo '
<h1>Filmdatabase</h1>
<ul>
	<li><a href=".">Vis søkbar liste &raquo;</a></li>
	<li><a href="?restructure">Restrukturer mappe på disk &raquo;</a></li>
</ul>
<h2>Indekserte filmer</h2>
<p>Antall: '.count($filmer['indexed']).'</p>
<p id="showposters"><a href="#">Vis posters for filmene</a></p>
<table class="table">
	<thead>
		<tr>
			<th rowspan="2">Poster</th>
			<th rowspan="2">Plassering</th>
			<th colspan="3">Metadata</th>
			<th colspan="6">IMDB-data</th>
		</tr>
		<tr>
			<th>Codec</th>
			<th>Oppløsning</th>
			<th>Norsk undertekst?</th>
			<th>ID</th>
			<th>Tittel</th>
			<th>År</th>
			<th>Rating</th>
			<th>Sjangre</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>';

$i = 0;
$posters = array();
foreach ($filmer['indexed'] as $film)
{
	$genres = $film->get("genres");
	$genres = $genres ? implode("<br />", $genres) : "&nbsp;";
	
	// hent filminfo
	$data = $film->get_movie_details();
	$codec = isset($data['video'][0]['codec_name']) ? $data['video'][0]['codec_name'] : "&nbsp;";
	$res = isset($data['video'][0]['width']) ? $data['video'][0]['width']."x".$data['video'][0]['height'] : "&nbsp;";
	
	$norsub = false;
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
	
	echo '
		<tr'.(++$i % 2 == 0 ? ' class="color"' : '').'>
			<td id="poster_'.$i.'">&nbsp;</td>
			<td>'.$film->path.'</td>
			<td>'.$codec.'</td>
			<td>'.$res.'</td>
			<td>'.($norsub ? "JA" : "&nbsp;").'</td>
			<td><a href="http://www.imdb.com/title/'.($id = $film->get_imdb_id()).'/">'.$id.'</a></td>
			<td>'.htmlspecialchars($film->get("title")).'</td>
			<td>'.$film->get("year").'</td>
			<td>'.$film->get("rating").'</td>
			<td>'.$genres.'</td>
			<td class="nowrap"><form action="?indexs='.$film->path_id.'" method="post" target="_blank"><input type="input" type="text" name="imdb_id" value="'.($id ?: "").'" style="width: 90px" /><input type="submit" value="Reindekser" /></form></td>
		</tr>';
	
	$posters[$i] = '<img src="?poster='.$film->path_id.'" alt="" />';
		}

echo '
	</tbody>
</table>';

$template->js .= '
var posters = '.js_encode($posters).';
window.addEvent("domready", function()
{
	$("showposters").getElement("a").addEvent("click", function(event)
	{
		event.stop();
		for (i in posters)
		{
			$("poster_"+i).set("html", posters[i]);
		}
		this.getParent().destroy();
	});
});';

echo '
<h2>Filmer som ikke er indeksert enda</h2>
<table class="table">
	<thead>
		<tr>
			<th>Plassering</th>
			<th>Søketittel</th>
			<th>IMDB-ID</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>';

$i = 0;
foreach ($filmer['unknown'] as $film)
{
	echo '
		<tr'.(++$i % 2 == 0 ? ' class="color"' : '').'>
			<td>'.$film->path.'</td>
			<td>'.htmlspecialchars($film->get_clean_name()).'</td>
			<td>'.(($id = $film->get_imdb_id()) ? '<a href="http://www.imdb.com/title/'.$id.'/">'.$id.'</a>' : "&nbsp;").'</td>
			<td class="nowrap"><form action="?indexs='.$film->path_id.'" method="post" target="_blank"><input type="input" type="text" name="imdb_id" value="'.($id ?: "").'" style="width: 90px" /><input type="submit" value="Indekser" /></form></td>
		</tr>';
}

echo '
	</tbody>
</table>';

echo '
<h2>Filmer som har feilet indeksering</h2>
<table class="table">
	<thead>
		<tr>
			<th>Plassering</th>
			<th>Søketittel</th>
			<th>IMDB-ID</th>
			<th>Feilnotat</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>';

$i = 0;
foreach ($filmer['failed'] as $film)
{
	echo '
		<tr'.(++$i % 2 == 0 ? ' class="color"' : '').'>
			<td>'.$film->path.'</td>
			<td>'.htmlspecialchars($film->get_clean_name()).'</td>
			<td>'.(($id = $film->get_imdb_id()) ? '<a href="http://www.imdb.com/title/'.$id.'/">'.$id.'</a>' : "&nbsp;").'</td>
			<td>'.$film->get_failed_data().'</td>
			<td><form action="?indexs='.$film->path_id.'" method="post" target="_blank"><input type="input" type="text" name="imdb_id" value="'.($id ?: "").'" style="width: 90px" /><input type="submit" value="Indekser" /></form></td>
		</tr>';
}

echo '
	</tbody>
</table>';