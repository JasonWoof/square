<?php

define('URL_CHARS', '-0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz');
$GLOBALS['url_chars_a'] = str_split(URL_CHARS);

# get the 32x32 version of the tile
function tile_get_32($url) {
	$tile = db_get_value('tiles', 'raw', 'where url=%"', $url);
	if($tile === false) {
		#return false;
		return str_repeat("\000", 32 * 32 / 8);
	}
	return substr($tile, 2560, 128);
}
