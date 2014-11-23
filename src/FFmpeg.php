<?php namespace henrist\FilmDB;

class FFmpeg {
    protected $ffprobe;

    public function __construct($ffmpeg_bin_path)
    {
        /*
         * Plassering til ffprobe (i ffmpeg pakken)
         * For windows kan binærfil lastet ned fra http://ffmpeg.arrozcru.org/autobuilds/
         * Se også http://ffmpeg.arrozcru.org/wiki/index.php?title=Links
         */
        $ffprobe = $ffmpeg_bin_path."/ffprobe".(HS_FILMDB_WIN ? ".exe" : "");
        if (!file_exists($ffprobe)) {
            throw new \Exception("Finner ikke ffprobe i ffmpeg.");
        }

        $this->ffprobe = $ffprobe;
    }

    /**
     * Les ut informasjon fra filmfil
     */
    public function get_moviefile_details($movie_path)
    {
        if (!$this->ffprobe) return false;

        $data = shell_exec(escapeshellarg($this->ffprobe) . " -show_streams " . escapeshellarg($movie_path) . " 2>nul");
        if (!$data) return false;

        $ret = array(
            "audio" => array(),
            "video" => array()
        );
        $c = array();

        // analyser dataen
        $lines = explode("\n", $data);
        foreach ($lines as $line)
        {
            $line = trim($line);

            // starte?
            if ($line == "[STREAM]")
            {
                $c = array();
            }

            // avslutte?
            elseif ($line == "[/STREAM]")
            {
                // lagre
                $ret[(isset($c['codec_type']) ? $c['codec_type'] : "unknown")][] = $c;
            }

            // data
            else
            {
                $p = explode("=", $line, 2);
                if (!isset($p[1])) continue;
                $c[$p[0]] = $p[1];
            }
        }

        return $ret;
    }
}