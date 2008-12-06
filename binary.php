<?php

require_once('code/common.php');
require_once('code/tiles.php');
require_once('init.php');

# take the high 4 bits of the byte passed. Spread them out and double them. eg:
# pass in:  1234xxxx
# get back: 11223344
function high_4_to_8($hi4) {
	$out = ($hi4 & 0x80) | (($hi4 & 0x40) >> 1) | (($hi4 & 0x20) >> 2) | (($hi4 & 0x10) >> 3);
	return $out | ($out >> 1);
}

# take the low 4 bits of the byte passed. Spread them out and double them. eg:
# pass in:  xxxx1234
# get back: 11223344
function low_4_to_8($low4) {
	$out = (($low4 & 0x8) << 3) | (($low4 & 0x4) << 2) | (($low4 & 0x2) << 1) | ($low4 & 0x1);
	return $out | ($out << 1);
}

# take the low 2 bits of the byte passed and fann them out
# pass in:  xxxxxx12
# get back: 11112222
function low_2_to_8($low2) {
	$out = ($low2 & 0x1) | (($low2 & 0x2) << 3);
	return $out | ($out << 1) | ($out << 2) | ($out << 3);
}

function high_2_to_8($hi2) {
	return low_2_to_8($hi2 >> 6);
}

function himid_2_to_8($hi2) {
	return low_2_to_8($hi2 >> 4);
}

function lomid_2_to_8($hi2) {
	return low_2_to_8($hi2 >> 2);
}

# for a hard square, we are passed a full 128x128 grid
# we pull data from the 32x32 data set in the tile table
function hard_square($url, $shadow) {
	$si = 0; # shadow index
	$ti = 0; # tile index
	$oi = 0; # output index
	for($tile_number = 0; $tile_number < 64; ++$tile_number) { # TODO make sure this loops l->r t->b
		$tile = tile_get_32($url . $GLOBALS['url_chars_a'][$tile_number]); # TODO try fetching all at once
		$ti = 0;
		$oi = (($tile_number % 8) * 4) + (floor($tile_number / 8) * 1024);
		$si = (($tile_number % 8) * 2) + (floor($tile_number / 8) * 256);
		for($rows = 0; $rows < 16; ++$rows) { # we do two rows at once to match shadow
			for($cols = 0; $cols < 2; ++$cols) { # we do two cols at once to match shadow
				# top left
				$p8 = ord($tile[$ti]);
				$s = ord($shadow[$si]);
				$s8 = high_4_to_8($s);
				$GLOBALS['pixels'][$oi] = $p8 ^ $s8;

				# 2nd down, left (uses s from above)
				$p8 = ord($tile[$ti + T32_RB]);
				# s8 already set above
				$GLOBALS['pixels'][$oi + PIXELS_RB] = $p8 ^ $s8;

				# we output one byte (and another in the next row down),
				# but only used up one nibble from shadow.
				++$ti;
				++$oi;

				# top, 1 to right
				$p8 = ord($tile[$ti]);
				# s already set
				$s8 = low_4_to_8($s);
				$GLOBALS['pixels'][$oi] = $p8 ^ $s8;

				# 2nd down, 1 to right (uses s from above)
				$p8 = ord($tile[$ti + T32_RB]);
				# s8 already set above
				$GLOBALS['pixels'][$oi + PIXELS_RB] = $p8 ^ $s8;

				# we output one byte (and another in the next row down),
				# and the rest of the shadow byte.
				++$ti;
				++$oi;
				++$si;

			}
			# end of the row, reset pointers for next row
			# $ti wraps automatically
			$ti += T32_RB; # we did the next row already
			$oi += 28; # we went 4, and the row is 32
			$oi += PIXELS_RB; # we did this next row already
			$si += 14; # we went 2 and the row is 16
		}
	}
}

# fetch the parent 128x128 tile and return the correct quadrant (no scaling)
function medium_shadow($url) {
	$parent = tile_get_128(substr($url, 0, -1));
	$shadow = '';
	$pos = strpos(URL_CHARS, substr($url, -1));
	if($pos === false) {
		die('invalid url');
	}
	$qx = floor(($pos % 8) / 4) * T64_RB;
	$qy = floor($pos / 32) * T128_RB * 64;

	for($y = 0; $y < 64; ++$y) {
		$shadow .= substr($parent, $qx + $qy + ($y * T128_RB), T64_RB);
	}

	return $shadow;
}

# for a medium square: shadow is a 64x64 grid
# we pull data from the 64x64 data set in the tile table
function medium_square($url, $shadow) {
	$si = 0; # shadow index
	$ti = 0; # tile index
	$oi = 0; # output index
	$tiles = get_medium_tiles($url);
	for($tile_number = 0; $tile_number < 16; ++$tile_number) {
		$tile = $tiles[$tile_number];
		$ti = 0;
		$oi = (($tile_number % 4) * 8) + (floor($tile_number / 4) * 2048);
		$si = (($tile_number % 4) * 2) + (floor($tile_number / 4) * 128);
		# there is a 16x16 part of the shadow covering our tile. 2 is the byte width:
		for($rows = 0; $rows < 16; ++$rows) { # we do four rows at once to match shadow
			for($cols = 0; $cols < 2; ++$cols) { # we do four cols at once to match shadow
				$s = ord($shadow[$si]); # used for this whole for block

				$s8 = high_2_to_8($s); # used for the next 4 pixels

				$p8 = ord($tile[$ti]);
				$GLOBALS['pixels'][$oi] = $p8 ^ $s8;

				$p8 = ord($tile[$ti + T64_RB]);
				$GLOBALS['pixels'][$oi + PIXELS_RB] = $p8 ^ $s8;

				$p8 = ord($tile[$ti + T64_RB2]);
				$GLOBALS['pixels'][$oi + PIXELS_RB2] = $p8 ^ $s8;

				$p8 = ord($tile[$ti + T64_RB3]);
				$GLOBALS['pixels'][$oi + PIXELS_RB3] = $p8 ^ $s8;

				++$ti;
				++$oi;


				$s8 = himid_2_to_8($s); # used for the next 4 pixels

				$p8 = ord($tile[$ti]);
				$GLOBALS['pixels'][$oi] = $p8 ^ $s8;

				$p8 = ord($tile[$ti + T64_RB]);
				$GLOBALS['pixels'][$oi + PIXELS_RB] = $p8 ^ $s8;

				$p8 = ord($tile[$ti + T64_RB2]);
				$GLOBALS['pixels'][$oi + PIXELS_RB2] = $p8 ^ $s8;

				$p8 = ord($tile[$ti + T64_RB3]);
				$GLOBALS['pixels'][$oi + PIXELS_RB3] = $p8 ^ $s8;

				++$ti;
				++$oi;


				$s8 = lomid_2_to_8($s); # used for the next 4 pixels

				$p8 = ord($tile[$ti]);
				$GLOBALS['pixels'][$oi] = $p8 ^ $s8;

				$p8 = ord($tile[$ti + T64_RB]);
				$GLOBALS['pixels'][$oi + PIXELS_RB] = $p8 ^ $s8;

				$p8 = ord($tile[$ti + T64_RB2]);
				$GLOBALS['pixels'][$oi + PIXELS_RB2] = $p8 ^ $s8;

				$p8 = ord($tile[$ti + T64_RB3]);
				$GLOBALS['pixels'][$oi + PIXELS_RB3] = $p8 ^ $s8;

				++$ti;
				++$oi;


				$s8 = low_2_to_8($s); # used for the next 4 pixels

				$p8 = ord($tile[$ti]);
				$GLOBALS['pixels'][$oi] = $p8 ^ $s8;

				$p8 = ord($tile[$ti + T64_RB]);
				$GLOBALS['pixels'][$oi + PIXELS_RB] = $p8 ^ $s8;

				$p8 = ord($tile[$ti + T64_RB2]);
				$GLOBALS['pixels'][$oi + PIXELS_RB2] = $p8 ^ $s8;

				$p8 = ord($tile[$ti + T64_RB3]);
				$GLOBALS['pixels'][$oi + PIXELS_RB3] = $p8 ^ $s8;

				++$ti;
				++$oi;

				# we finished the shadow byte
				++$si;

			}
			# end of the row, reset pointers for next row
			$ti += T64_RB3; # we advanced 1 row already
			$oi += 24; # we advanced 8, and the row is 32
			$oi += PIXELS_RB3; # we did these next rows already
			$si += 6; # we advanced 2 and the row is 8
		}
	}
}



# for an easy square,   ?we are passed a full 128x128 grid?
# we pull data from the 128x128 data set in the tile table
function easy_square($url, $shadow) {
	# FIXME
}


function get_initial_toggle($square) {
	$toggle = 0;
	
	# FIXME

	return $toggle;
}


function binary_main() {
	if(isset($_REQUEST['url'])) {
		$url = ereg_replace('[^a-zA-Z0-9._-]', '', $_REQUEST['url']);
		$pos = strpos($url, '.');
		if($pos !== false) {
			$dots = substr($url, $pos);
			$url = substr($url, 0, $pos);
		} else {
			$dots = '';
		}
	} else {
		$url = '';
		$dots = '';
	}

	if(!am_debugging()) {
		header('Content-Type: application/octet-stream; charset=us-ascii');
		header('Content-Length: 8196');
	}
	$SQUARE_WIDTH = 256;

	# $GLOBALS['pixels'] is an array of ints, because php won't let me use ^= on chars.
	$GLOBALS['pixels'] = array_fill(0, 256 * 256 / 8, 255);

	$shadow = str_repeat("\000", 128 * 128 / 8); #FIXME

	switch($dots) {
		case '':
			$shadow = tile_get_128($url);
			hard_square($url, $shadow);
		break;
		case '..':
			$shadow = medium_shadow($url);
			medium_square($url, $shadow);
		break;
		case '.':
			easy_square($url, $shadow);
		break;
		default:
			die('invalid url');
	}
	


	# let the client know that it's looking at
	print(chr($square >> 24));
	print(chr(($square >> 16) & 0xff));
	print(chr(($square >> 8) & 0xff));
	print(chr($square & 0xff));

	#$GLOBALS['pixels'][0] = 95;
	#$GLOBALS['pixels'][1] = "\000";
	#$GLOBALS['pixels'][2] = 98;
	#$GLOBALS['pixels'][3] = chr(0);
	#$GLOBALS['pixels'][4] = 97;
	#print("Foo: " . strlen($GLOBALS['pixels']) . "hah\n");
	for($i = 0; $i < PIXELS_RB * 256; $i++) {
		print(chr($GLOBALS['pixels'][$i]));
	}
}


?>
