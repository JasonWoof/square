<?php

define('URL_CHARS', '-0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz');
$GLOBALS['url_chars_a'] = str_split(URL_CHARS);

# bytes per row
define('T32_RB', 4); 
define('T32_RB2', 8); 
define('T32_RB3', 12); 
define('T64_RB', 8); 
define('T64_RB2', 16); 
define('T64_RB3', 24); 
define('T128_RB', 16); 
define('T128_RB2', 32); 
define('T128_RB3', 48); 
define('PIXELS_RB', 32); 
define('PIXELS_RB2', 64); 
define('PIXELS_RB3', 96); 

# get the 128x128 version of the tile
function tile_get_128($url) {
	$tile = db_get_value('tiles', 't128', 'where url=%"', $url);
	if($tile === false) {
		#return false;
		return str_repeat("\000", 128 * 128 / 8);
	}

	if($GLOBALS['debug_tiles']) {
		print_full_table($tile, 128, 128 / 8);
	}

	return $tile;
}

# get the 64x64 version of the tile
function tile_get_64($url) {
	$tile = db_get_value('tiles', 't64', 'where url=%"', $url);
	if($tile === false) {
		#return false;
		return str_repeat("\000", 64 * 64 / 8);
	}

	if($GLOBALS['debug_tiles']) {
		print_full_table($tile, 64, 64 / 8);
	}

	return $tile;
}

# get the 32x32 version of the tile
function tile_get_32($url) {
	$tile = db_get_value('tiles', 't32', 'where url=%"', $url);
	if($tile === false) {
		#return false;
		return str_repeat("\000", 32 * 32 / 8);
	}

	#$debug_binary = true;
	if($debug_binary) {;
		$GLOBALS['debug_tiles'] = true;
		tile_get_128($url); # because it prints now
		tile_get_64($url); # because it prints now
		print_full_table($tile, 32, 32 / 8);
		exit();
	}

	if($GLOBALS['debug_tiles']) {;
		print_full_table($tile, 32, 32 / 8);
	}

	return $tile;
}

function color_pixel($x, $y) {
	$bit = $x % 8;
	$GLOBALS['pixels'][floor($x / 8) + ($y * PIXELS_RB)] ^= 0x80 >> $bit;
}

# on my machine, this function takes up about 10% of the total execution time
function color_square($x, $y, $width) {
	# it's always going to be alligned to $width, and $width will never be 1
	$bit = $x % 8;
	switch($width) {
		case 1:
			color_pixel($x, $y);
			return;
		case 2:
			$GLOBALS['pixels'][floor($x / 8) + ($y * PIXELS_RB)] ^= 0xc0 >> $bit;
			$GLOBALS['pixels'][floor($x / 8) + (($y + 1) * PIXELS_RB)] ^= 0xc0 >> $bit;
		return;
		case 4:
			$GLOBALS['pixels'][floor($x / 8) + ($y * PIXELS_RB)] ^= 0xf0 >> $bit;
			$GLOBALS['pixels'][floor($x / 8) + (($y + 1) * PIXELS_RB)] ^= 0xf0 >> $bit;
			$GLOBALS['pixels'][floor($x / 8) + (($y + 2) * PIXELS_RB)] ^= 0xf0 >> $bit;
			$GLOBALS['pixels'][floor($x / 8) + (($y + 3) * PIXELS_RB)] ^= 0xf0 >> $bit;
		return;
		default:
			$x = floor($x / 8);
			$xmax = $x + floor($width / 8);
			$ymax = ($y + $width) * PIXELS_RB;
			for($yy = $y * PIXELS_RB; $yy < $ymax; $yy += PIXELS_RB) {
				for($xx = $x; $xx < $xmax; $xx++) {
					$GLOBALS['pixels'][$xx + $yy] = 0xff;
				}
			}
		return;
	}
}
