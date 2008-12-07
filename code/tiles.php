<?php

define('URL_CHARS', '-0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz');
$GLOBALS['url_chars_a'] = str_split(URL_CHARS);

# - 0 1 2  3 4 5 6
# 7 8 9 A  B C D E
# F G H I  J K L M
# N O P Q  R S T U
#
# V W X Y  Z _ a b
# c d e f  g h i j
# k l m n  o p q r
# s t u v  w x y z



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
define('T128_RB7', 112); 
define('PIXELS_RB', 32); 
define('PIXELS_RB2', 64); 
define('PIXELS_RB3', 96); 
define('PIXELS_RB7', 224); 

function get_medium_tiles($url) {
	$base = substr($url, 0, -1);
	$last = substr($url, -1);
	$pos = strpos(URL_CHARS, $last);
	if($pos === false) {
		die('invalid url');
	}
	$qx = floor(($pos % 8) / 4) * 4;
	$qy = floor($pos / 32) * 32;
	$ret = array();
	for($y = 0; $y < 32; $y += 8) {
		for($x = 0; $x < 4; ++$x) {
			#print "url: (from $url) " . $base . $GLOBALS['url_chars_a'][$x + $y + $qx + $qy] . "\n";
			$ret[] = tile_get_64($base . $GLOBALS['url_chars_a'][$x + $y + $qx + $qy]);
		}
	}

	return $ret;
}

function get_easy_tiles($url) {
	$base = substr($url, 0, -1);
	$last = substr($url, -1);
	$pos = strpos(URL_CHARS, $last);
	if($pos === false) {
		die('invalid url');
	}
	$ret = array();
	$ret[] = tile_get_128($base . $GLOBALS['url_chars_a'][$pos]);
	$ret[] = tile_get_128($base . $GLOBALS['url_chars_a'][$pos + 1]);
	$ret[] = tile_get_128($base . $GLOBALS['url_chars_a'][$pos + 8]);
	$ret[] = tile_get_128($base . $GLOBALS['url_chars_a'][$pos + 9]);

	return $ret;
}

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
