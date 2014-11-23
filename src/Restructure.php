<?php namespace henrist\FilmDB;

class Restructure
{
    /**
     * @var Restructure\OS
     */
    protected $os;

    /**
     * @var FilmDB
     */
    protected $filmdb;

    /**
     * Konstruktør..
     */
    public function __construct(FilmDB $filmdb)
    {
        if (HS_FILMDB_WIN) $this->os = new Restructure\OS\Win();
        else $this->os = new Restructure\OS\Other();

        $this->filmdb = $filmdb;
    }

    /**
     * Desc TODO
     * @param array filmer fra FilmDB::get_all_movies_grouped()
     */
    public function restructure($movies) {
        if (!isset($movies['indexed']))
        {
            throw new \Exception("Ugyldig inndata.");
        }

        $dir = $this->filmdb->set->index_dir;
        $dirtemp = $this->filmdb->set->index_dir_temp;
        $hddir = $this->filmdb->set->index_hd_dir;
        $hddirtemp = $this->filmdb->set->index_hd_dir_temp;
        $newdir = $this->filmdb->set->index_new_dir;
        $newdirtemp = $this->filmdb->set->index_new_dir_temp;

        // opprett filmindeks-mappa
        $this->os->dir_mk($dirtemp);
        $this->os->dir_mk($dirtemp."/-- MERK1 -- FORBEHOLD OM FEIL INDEKSERING -- TITTEL KAN FRAVIKE --");
        $this->os->dir_mk($dirtemp."/-- MERK2 -- ALLE TITLER OG AARSTALL ER HENTET FRA IMDB --");

        // opprett HD-filmindeks-mappa
        $this->os->dir_mk($hddirtemp);
        $this->os->file_touch($hddirtemp."/-- MERK1 -- HENT FILMEN FRA HOVEDMAPPA, DETTE ER KUN INFO --");

        // opprett mappe for nye filmer
        $this->os->dir_mk($newdirtemp);
        $this->os->file_touch($newdirtemp."/-- MERK1 -- DETTE ER EN OVERSIKT OVER NYESTE FILMER LAGT TIL --");
        $this->os->file_touch($newdirtemp."/-- MERK2 -- FILMER LAGT INN FOR MER ENN 20 DAGER SIDEN FJERNES VED INDEKSERING --");
        $this->os->file_touch($newdirtemp."/-- MERK3 -- HENT FILMEN FRA HOVEDMAPPA, DETTE ER KUN INFO --");


        echo '<pre>';

        $new_expire = time()-86400*20; // hvor langt tilbake skal vi lage indeks for nye filmer

        $names = array();

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
            $hd = false;
            if (preg_match("/\\/(1080-U?KOMP|Filmer-1080|1080)\\//", $film->path))
            {
                $name .= " [1080]";
                $hd = true;
            }
            elseif (preg_match("/\\/(720-U?KOMP|Filmer-720|720)\\//", $film->path))
            {
                $name .= " [720]";
                $hd = true;
            }

            #$name .= " (".$data['video'][0]['width']."x".$data['video'][0]['height'].")";

            // rating
            #$name .= " [rating=".$film->get("rating")."]";

            // fiks ugyldige navn
            $name = preg_replace("/[\\\\\\/:*?\"<>|]/", "", $name);

            // sørg for korrekt koding
            if ($this->os->type == "other") $name = utf8_encode($name);

            // sett opp alternativt navn
            $name_org = $name;
            $x = 2;
            while (file_exists($dirtemp."/".$name) || in_array($name, $names))
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

            $this->os->dir_link($dirtemp."/".$name, $film->path);
            $names[] = $name;

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

            // HD?
            if ($hd)
            {
                $r = number_format((float) $film->get("rating"), 1, ",", "");
                $this->os->file_touch($hddirtemp."/".$name_org." (rating=$r)");
            }

            $i++;
        }

        echo '</pre>';

        // slett filmindeks-mappa
        $this->os->dir_rm($dir);
        $this->os->dir_rm($hddir);
        $this->os->dir_rm($newdir);

        // flytt temp-mappa
        $this->os->dir_mv($dirtemp, $dir);
        $this->os->dir_mv($hddirtemp, $hddir);
        $this->os->dir_mv($newdirtemp, $newdir);

        echo '
<h1>Filmindeks</h1>
<p>Filmindeks skal nå være oppdatert med '.$i.' filmer.</p>';
    }
}