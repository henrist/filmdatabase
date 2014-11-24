<?php

class hs_filmdb_template
{
	public $title = "Filmdatabase";
	public $js;
	public $css;

	public function __construct()
	{
		@ob_start();
	}

	public function render()
	{
		$data = @ob_get_contents();
		@ob_clean();

		echo '<html>
<head>
<title>'.$this->title.'</title>
<link href="/files/default.css" rel="stylesheet" type="text/css" />
<style type="text/css">
'.$this->css.'
</style>
<script src="/bower_components/jquery/dist/jquery.js" type="text/javascript"></script>
<script src="/bower_components/jquery.tablesorter/js/jquery.tablesorter.min.js" type="text/javascript"></script>
<script src="/bower_components/jquery-unveil/jquery.unveil.min.js" type="text/javascript"></script>
<script src="/files/default.js" type="text/javascript"></script>
<script type="text/javascript">
'.$this->js.'
</script>
</head>
<body>'.$data.'
<p class="hsws">Utviklet av <a href="http://hsw.no/">Henrik Steen Webutvikling</a></p>
</body>
</html>';
	}
}