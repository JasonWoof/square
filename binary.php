<?php

require_once('code/common.php');
require_once('code/tiles.php');
require_once('init.php');

function high_4_to_8($hi4) {
	$out = ($hi4 & 0x80) | (($hi4 & 0x40) >> 1) | (($hi4 & 0x20) >> 2) | (($hi4 & 0x10) >> 3);
	return $out | ($out >> 1);
}

function low_4_to_8($low4) {
	$out = (($low4 & 0x8) << 3) | (($low4 & 0x4) << 2) | (($low4 & 0x2) << 1) | ($low4 & 0x1);
	return $out | ($out << 1);
}

# for a hard square, we are passed a full 128x128 grid
# we pull data from the 32x32 data set in the tile
function hard_square($url, $shadow) {
	$si = 0; # shadow index
	$ti = 0; # tile index
	$oi = 0; # output index
	for($tile_number = 0; $tile_number < 64; ++$tile_number) { # TODO make sure this loops l->r t->b
		$tile = tile_get_32($url . $GLOBALS['url_chars_a'][$tile_number]);
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


function get_initial_toggle($square) {
	$toggle = 0;
	
	# FIXME

	return $toggle;
}


function binary_main() {
	if(!am_debugging()) {
		header('Content-Type: application/octet-stream; charset=us-ascii');
		header('Content-Length: 8196');
	}
	$SQUARE_WIDTH = 256;

	$GLOBALS['pixels_rowbytes'] = $SQUARE_WIDTH / 8;
	$GLOBALS['pixels'] = array_fill(0, 256 * 256 / 8, 255);

	# NOTE: $GLOBALS['pixels'] is an array of ints, because php won't let me use ^= on chars.

	# FIXME $square = get_square_id();
	$url = ""; # root


	dbg_log("square id: $square");

	#$snarglepop = get_initial_toggle($square);
	
	$shadow = tile_get_128('');
	#$shadow = str_repeat("\000", 128 * 128 / 8); #FIXME

	hard_square('', $shadow);


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
	for($i = 0; $i < $SQUARE_WIDTH * $SQUARE_WIDTH/ 8; $i++) {
		print(chr($GLOBALS['pixels'][$i]));
	}
}


?>
