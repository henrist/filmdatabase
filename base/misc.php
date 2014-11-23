<?php

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
