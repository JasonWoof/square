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


# Return the row and column of $c in URL_CHARS, adjusted as follows:
#
# First they are scaled down and floored so they are integers less than $quantize
#
# Then they are scaled up so they are range from 0 to $scale-1
#
# examples:
#    1) find out what quadrant "c" is in: (output range 0..1 inclusive)
#          list($x, $y) = url_char_to_xy('c', 2, 2);
#    2) get coordinates of quadrant containing "f" in the scale of a 128x128 grid
#          list($x, $y) = url_char_to_xy('f', 2, 128);
#    3) find out which 16x16 patch of a 128x128 block is addressed as "R"
#          list($x, $y) = url_char_to_xy('R', 8, 16);
function url_char_to_xy($c, $quantize = 8, $scale = 0) {
	if(!$scale) {
		$scale = $quantize;
	}

	if($quantize > $scale) {
		$quantize = $scale;
	}

	$pos = strpos(URL_CHARS, $c);
	$x = ($pos % 8);
	$y = floor($pos / 8 + .000001);

	$quantize = 8 / $quantize;

	$x = floor(($x / $quantize) + .000001);
	$y = floor(($y / $quantize) + .000001);

	$x = round($x * ($scale / 8) * $quantize);
	$y = round($y * ($scale / 8) * $quantize);

	return array($x, $y);
}

# args are expected to be fully alligned
function t128_subsection(&$t128, $x, $y, $size) {
	$x_bytes = floor($x / 8); # convert bits to bytes
	$size_bytes = ceil($size / 8);

	$ret = '';
	for($i = 0; $i < $size; ++$i) {
		$ret .= substr($t128, ($y * T128_RB) + $x_bytes, $size_bytes);
		$y += 1;
	}

	# if size < 8 AND we're not aligned to a byte boundary then shift everything
	$shift = $x % 8;
	if($shift) {
		for($i = 0; $i < $size; ++$i) {
			$ret[$i] = chr((ord($ret[$i]) << $shift) & 0xff);
		}
	}

	return $ret;
}

function get_hard_tiles($url) {
	$query = '';
	foreach($GLOBALS['url_chars_a'] as $c) {
		if($query) {
			$query .= ' || ';
		}
		$query .= ' url="' . $url . $c . '"';
	}
	$rows = db_get_rows('tiles', 'url,t32', $quere);
	$ut = array();
	foreach($rows as $row) {
		list($u, $t) = $row;
		$ut[$u] = $t;
	}
	$ret = array();
	foreach($GLOBALS['url_chars_a'] as $c) {
		if(isset($ut[$url . $c])) {
			$ret[] = $ut[$url . $c];
		} else {
			$ret[] = str_repeat("\000", 32 * 32 / 8);
		}
	}

	return $ret;
}

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
function tile_get_128($url, $array = false) {
	$row = db_get_row('tiles', 'id,t128', 'where url=%"', $url);
	if($row === false) {
		$id = 0;
		$t128 = str_repeat("\000", 128 * 128 / 8);
	} else {
		list($id, $t128) = $row;
	}

	if($GLOBALS['debug_tiles']) {
		print_full_table($t128, 128, 128 / 8);
	}

	if($array) {
		return array($t128, $id);
	}

	return $t128;
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
					$GLOBALS['pixels'][$xx + $yy] ^= 0xff;
				}
			}
		return;
	}
}

function color_tile($x, $y, $width, &$tile) {
	# it's always going to be alligned to $width, and $width will never be 1
	$bit = $x % 8;
	switch($width) {
		case 1:
			$i = floor($x / 8) + ($y * T128_RB);
			$tile[$i] = chr(ord($tile[$i]) ^ (0x80 >> $bit));
			return;
		case 2:
			$i = floor($x / 8) + ($y * T128_RB);
			$tile[$i] = chr(ord($tile[$i]) ^ (0xc0 >> $bit));
			$i = floor($x / 8) + (($y + 1) * T128_RB);
			$tile[$i] = chr(ord($tile[$i]) ^ (0xc0 >> $bit));
		return;
		case 4:
			$i = floor($x / 8) + ($y * T128_RB);
			$tile[$i] = chr(ord($tile[$i]) ^ (0xf0 >> $bit));
			$i = floor($x / 8) + (($y + 1) * T128_RB);
			$tile[$i] = chr(ord($tile[$i]) ^ (0xf0 >> $bit));
			$i = floor($x / 8) + (($y + 2) * T128_RB);
			$tile[$i] = chr(ord($tile[$i]) ^ (0xf0 >> $bit));
			$i = floor($x / 8) + (($y + 3) * T128_RB);
			$tile[$i] = chr(ord($tile[$i]) ^ (0xf0 >> $bit));
		return;
		default:
			$x = floor($x / 8);
			$xmax = $x + floor($width / 8);
			$ymax = ($y + $width) * T128_RB;
			for($yy = $y * T128_RB; $yy < $ymax; $yy += T128_RB) {
				for($xx = $x; $xx < $xmax; $xx++) {
					$tile[$xx + $yy] = chr(ord($tile[$xx + $yy]) ^ 0xff);
				}
			}
		return;
	}
}

function downsample($tl, $tr, $bl, $br) {
	# add every 2 pixels together.
	# result is 2-bit sums every 2 bits
	$tl = (($tl & 0xaa) >> 1) + ($tl & 0x55);
	$tr = (($tr & 0xaa) >> 1) + ($tr & 0x55);
	$bl = (($bl & 0xaa) >> 1) + ($bl & 0x55);
	$br = (($br & 0xaa) >> 1) + ($br & 0x55);

	$out = 0;

	# mask out the 2-bit sums from differnt rows, and add together. result r is 0-4 inclusive
	# subtract that result from 1 to get a small negative number if r is greater than 2
	# copy a high bit into the output char (we'll shift it down all at once later)
	$out |= (2 - ((($tl & 0xc0) >> 6) + (($bl & 0xc0) >> 6))) & 0x80000000;
	$out |= (2 - ((($tl & 0x30) >> 4) + (($bl & 0x30) >> 4))) & 0x40000000;
	$out |= (2 - ((($tl & 0x0c) >> 2) + (($bl & 0x0c) >> 2))) & 0x20000000;
	$out |= (2 - ((($tl & 0x03)     ) + (($bl & 0x03)     ))) & 0x10000000;

	$out |= (2 - ((($tr & 0xc0) >> 6) + (($br & 0xc0) >> 6))) & 0x08000000;
	$out |= (2 - ((($tr & 0x30) >> 4) + (($br & 0x30) >> 4))) & 0x04000000;
	$out |= (2 - ((($tr & 0x0c) >> 2) + (($br & 0x0c) >> 2))) & 0x02000000;
	$out |= (2 - ((($tr & 0x03)     ) + (($br & 0x03)     ))) & 0x01000000;

	$out >>= 24;
	$out &= 0xff; // >> is arithmetic

	return $out;
}
