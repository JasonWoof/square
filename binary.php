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

# for a hard square, we are passed a 128x128 bit shadow
# we pull data from the 32x32 data set in the tile table
function hard_square($url, $shadow) {
	$si = 0; # shadow index
	$ti = 0; # tile index
	$oi = 0; # output index
	$tiles = get_hard_tiles($url);
	for($tile_number = 0; $tile_number < 64; ++$tile_number) { # TODO make sure this loops l->r t->b
		$tile = $tiles[$tile_number];
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

# pass a big tile, a tile 8x smaller, and the width in pixels of the small one (1,2,4,8 and 16 supported)
#
# tiles have one bit per pixel. 4x4 and 2x2 tiles are padded so each row starts on the next byte boundary.
#
# $big is changed, small is not (it's just passed by reference for speed)
#
# The result (overwriting $big) is the bitwise eXclusive OR of $big and (small zoomed 8x)
function blit_xor_8x(&$big, &$small, $width) {
	$big_cur = 0;
	for($row = 0; $row < $width; ++$row, $big_cur += $width * 7) {
		if($width == 16) {
			$small_row = (ord($small[$row * 2]) << 8) | ord($small[($row * 2) + 1]);
		} else {
			$small_row = ord($small[$row]) << 8;
		}
		for($bit = 0; $bit < $width; ++$bit, ++$big_cur) {
			$mask = $small_row & 0x8000; $small_row = $small_row << 1; # pop high bit
			$mask |= ($mask >> 1) | ($mask >> 2) | ($mask >> 3);
			$mask = ($mask >> 12) | ($mask >> 8);
			for($sub_row = 0; $sub_row < 8; ++$sub_row) {
				$big_i = $big_cur + ($sub_row * $width);
				$big[$big_i] = chr(ord($big[$big_i]) ^ $mask);
			}
		}
	}
}

# fetch the parent 128x128 tile
# FIXME get other ancestors
function hard_shadow($url) {
	$url_len = strlen($url);
	$shadow = tile_get_128($url);

	if($url_len == 0) {
		return $shadow;
	}

	$shadow_16_tile = tile_get_128(substr($url, 0, -1));
	list($x, $y) = url_char_to_xy(substr($url, -1), 128);
	$shadow_16 = t128_subsection($shadow_16_tile, $x, $y, 16);

	if($url_len > 1) {
		$shadow_2 = tile_get_128(substr($url, 0, -2));
		# FIXME take the correct 2x2 chunk of this

		if($url_len > 2) {
			$initial_toggle = get_initial_toggle($url, 0);
			if($initial_toggle) {
				# FIXME color $shadow_2
			}
		}

		# FIXME color $shadow_16 from $shadow_2
	}

	blit_xor_8x($shadow, $shadow_16, 16);

	return $shadow;
}

# fetch the parent 128x128 tile and return the correct quadrant (no scaling)
# FIXME get other ancestors
function medium_shadow($url) {
	$url_len = strlen($url);
	$parent = tile_get_128(substr($url, 0, -1));
	list($x, $y) = url_char_to_xy(substr($url, -1), 2, 128);
	$shadow = t128_subsection($parent, $x, $y, 64);

	if($url_len == 1) {
		return $shadow;
	}

	if($url_len > 1) {
		$shadow_8_tile = tile_get_128(substr($url, 0, -2));
		list($x, $y) = url_char_to_xy(substr($url, -2, 1), 8, 128);
		list($xq, $yq) = url_char_to_xy(substr($url, -1, 1), 2, 16);
		$shadow_8 = t128_subsection($shadow_8_tile, $x + $xq, $y + $yq, 8);

		if($url_len > 2) {
			$initial_toggle = get_initial_toggle($url, 2);
			if($initial_toggle) {
				# FIXME color $shadow_8
			}
		}
		blit_xor_8x($shadow, $shadow_8, 8);
	}

	return $shadow;
}

# fetch the parent 128x128 tile and return the correct 32x32 piece (no scaling)
# FIXME get other ancestors
function easy_shadow($url) {
	$url_len = strlen($url);
	$parent = tile_get_128(substr($url, 0, -1));
	$shadow = '';
	$pos = strpos(URL_CHARS, substr($url, -1));
	$qx = floor(($pos % 8) / 2) * T32_RB;
	$qy = floor($pos / 16) * T128_RB * 32;

	for($y = 0; $y < 32; ++$y) {
		$shadow .= substr($parent, $qx + $qy + ($y * T128_RB), T32_RB);
	}

	if($url_len == 1) {
		return $shadow;
	}

	if($url_len > 1) {
		$shadow_4 = tile_get_128(substr($url, 0, -2));
		# FIXME get the right 4x4 sub-section

		if($url_len > 2) {
			$initial_toggle = get_initial_toggle($url, 2);
			if($initial_toggle) {
				# FIXME color $shadow_4
			}
		}
	}

	# FIXME color $shadow with $shadow_4

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
				$s = ord($shadow[$si]); # used for this whole context

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



# for an easy square, we are passed a 32x32 grid
# we pull data from the 128x128 data set in the tile table
function easy_square($url, $shadow) {
	# FIXME
	$si = 0; # shadow index
	$ti = 0; # tile index
	$oi = 0; # output index
	$tiles = get_easy_tiles($url);
	for($tile_number = 0; $tile_number < 4; ++$tile_number) {
		$tile = $tiles[$tile_number];
		$ti = 0;
		$oi = (($tile_number % 2) * 16) + (floor($tile_number / 2) * 4096);
		$si = (($tile_number % 2) * 2) + (floor($tile_number / 2) * 64);
		# there is a 16x16 part of the shadow covering our tile. 2 is the byte width:
		for($rows = 0; $rows < 16; ++$rows) { # we do eight rows at once to match shadow
			for($cols = 0; $cols < 2; ++$cols) { # we do eight cols at once to match shadow
				$s = ord($shadow[$si]); # used for this whole context

				for($i = 0; $i < 8; ++$i) { # horizontal
					$mask = $s & 0x80; $s = $s << 1; # pop high bit
					# fan out bit
					$mask |= ($mask >> 1) | ($mask >> 2) | ($mask >> 3);
					$mask |=  ($mask >> 4);

					$tmp_ti = $ti;
					$tmp_oi = $oi;
					for($j = 0; $j < 8; ++$j) {
						$p8 = ord($tile[$tmp_ti]);
						$GLOBALS['pixels'][$tmp_oi] = $p8 ^ $mask;
						$tmp_ti += T128_RB;
						$tmp_oi += PIXELS_RB;
					}

					++$ti;
					++$oi;
				}

				# we finished the shadow byte
				++$si;

			}
			# end of the row, reset pointers for next row
			$ti += T128_RB7; # we advanced 1 row already
			$oi += 16; # we advanced 16, and the row is 32
			$oi += PIXELS_RB7; # we did these next rows already
			$si += 2; # we advanced 2 and the row is 4
		}
	}
}


# find the initial "background color" for this square. that is:
# start at zoom 0, and zoom in while the square to be rendered is still contained within a single pixel. Return the cumulative toggle of that pixel.
# return 1 (toggled) or 0 (not toggled)
function get_initial_toggle($url, $dots) {
	$zooms = (strlen($url) * 3) - strlen($dots);
	# after zooming 7 times, a single pixel from tile '' covers the whole picture.
	if($zooms < 7) {
		header("X-Initial-Toggle-Note: Only $zooms zooms.");
		return 0;
	}

	$url_chars = 0;
	$toggle = 0;
	while($zooms > 6) { # 6 because the lower zooms come to a single pixel below plane 0 where we have no data
		$zooms -= 3;

		$t128 = tile_get_128(substr($url, 0, $url_chars));
		# the next two bytes of the url tell us which pixel to take
		$ch1 = strpos(URL_CHARS, substr($url, $url_chars, 1));
		$ch2 = strpos(URL_CHARS, substr($url, $url_chars + 1, 1));
		$ch3 = strpos(URL_CHARS, substr($url, $url_chars + 2, 1));

		# there are 8x8 child tiles under this tile. Each sits under 16x16 pixels?
		$x = ($ch1 % 8) * 16;
		$x += ($ch2 % 8) * 2;
		$x += floor(($ch3 % 8) / 4);

		$y = floor($ch1 / 8) * 16;
		$y += floor($ch2 / 8) * 2;
		$y += floor(($ch3 / 8) / 4);

		$toggle ^= (ord(substr($t128, ($y * T128_RB) + floor($x / 8), 1)) >> (7 - ($x % 8))) & 1;

		$url_chars += 1;
	}

	header("X-Initial-Toggle-Note: initial toggle: $toggle.");
	return $toggle;
}


function binary_main() {
	if(isset($_REQUEST['url'])) {
		$url = ereg_replace('[^a-zA-Z0-9._-]', '', $_REQUEST['url']);
		$pos = strpos($url, '.');
		if($pos !== false) {
			$dots = substr($url, $pos);
			$dots = ereg_replace('[^.]', '', $dots);
			$url = substr($url, 0, $pos);
			if(strlen($dots) > 2 || ($url == '' && $dots != '')) {
				print("Invalid URL");
				return;
			}
		} else {
			$dots = '';
		}
	} else {
		$url = '';
		$dots = '';
	}

	if(!am_debugging()) {
		header('Content-Type: application/octet-stream; charset=us-ascii');
		header('Content-Length: 8192');
	}
	$SQUARE_WIDTH = 256;

	# $GLOBALS['pixels'] is an array of ints, because php won't let me use ^= on chars.
	$GLOBALS['pixels'] = array_fill(0, 256 * 256 / 8, 255);

	$initial_toggle = get_initial_toggle($url, $dots);

	$shadow = str_repeat("\000", 128 * 128 / 8); #FIXME

	switch($dots) {
		case '':
			$shadow = hard_shadow($url);
			hard_square($url, $shadow);
		break;
		case '..':
			$shadow = medium_shadow($url);
			medium_square($url, $shadow);
		break;
		case '.':
			$shadow = easy_shadow($url);
			easy_square($url, $shadow);
		break;
	}
	


	for($i = 0; $i < PIXELS_RB * 256; $i++) {
		print(chr($GLOBALS['pixels'][$i]));
	}
}


?>
