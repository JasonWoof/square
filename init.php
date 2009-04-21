<?php

require_once('code/common.php');
require_once('code/tiles.php');

$GLOBALS['crs_count'] = -1; # create random square needs to know how many times it's been called

# pass an array (ints or a string)
# width is in pixels
function print_full_table($a, $width, $rowbytes = 0) {
	if($rowbytes == 0) {
		$rowbytes = ceil($width / 8);
	}
	$width;
	$byte_width = $width / 8;
	$i = 0;
	for($y = 0; $y < $width/2; ++$y) {
		for($x = 0; $x < $byte_width; ++$x) { # one byte at a time, not one bit
			$c = $a[$i];
			if(is_string($c)) {
				$c = ord($c);
			}
			printf("%08b", $c);
			++$i;
		}
		print("\n");
		$i += $rowbytes - $byte_width;
		$i += $rowbytes; # just print every other row
	}
	print("\n");
}

# pass an array (ints or a string)
# width is in pixels
function print_table($a, $width, $rowbytes) {
	$width;
	$byte_width = $width / 8;
	$i = 0;
	for($y = 0; $y < $byte_width; ++$y) {
		for($x = 0; $x < $byte_width; ++$x) { # one byte at a time, not one bit
			$c = $a[$i];
			if(is_string($c)) {
				$c = ord($c);
			}
			printf("%02x", $c);
			++$i;
		}
		print("\n");
		$i += $rowbytes - $byte_width;
	}
}

function create_tile_from_quadrant($quad) {
	# set the pixel index we're copying from
	$pi = ($quad % 2) * 16;
	$pi += floor($quad / 2) * 4096;

	$t32_i = 0; # 32x32 tile pixel index
	$t64_i = 0; # 64x64 tile pixel index
	$t128_i = 0; # 128x128 tile pixel index


	$t32 = str_repeat("\000", 32 * 32 / 8);
	$t64 = str_repeat("\000", 64 * 64 / 8);
	$t128 = str_repeat("\000", 128 * 128 / 8);

	for($y = 0; $y < 32; ++$y) {
		for($x = 0; $x < 4; ++$x) {
			# xy
			$p00 = $GLOBALS['pixels'][$pi                 ];
			$p01 = $GLOBALS['pixels'][$pi + PIXELS_RB ];
			$p02 = $GLOBALS['pixels'][$pi + PIXELS_RB2];
			$p03 = $GLOBALS['pixels'][$pi + PIXELS_RB3];
			++$pi;
			$p10 = $GLOBALS['pixels'][$pi             ];
			$p11 = $GLOBALS['pixels'][$pi + PIXELS_RB ];
			$p12 = $GLOBALS['pixels'][$pi + PIXELS_RB2];
			$p13 = $GLOBALS['pixels'][$pi + PIXELS_RB3];
			++$pi;
			$p20 = $GLOBALS['pixels'][$pi             ];
			$p21 = $GLOBALS['pixels'][$pi + PIXELS_RB ];
			$p23 = $GLOBALS['pixels'][$pi + PIXELS_RB3];
			$p22 = $GLOBALS['pixels'][$pi + PIXELS_RB2];
			++$pi;
			$p30 = $GLOBALS['pixels'][$pi             ];
			$p31 = $GLOBALS['pixels'][$pi + PIXELS_RB ];
			$p32 = $GLOBALS['pixels'][$pi + PIXELS_RB2];
			$p33 = $GLOBALS['pixels'][$pi + PIXELS_RB3];
			++$pi;

			# save directly into t128
			$t128[$t128_i] = chr($p00);
			$t128[$t128_i + T128_RB] = chr($p01);
			$t128[$t128_i + T128_RB2] = chr($p02);
			$t128[$t128_i + T128_RB3] = chr($p03);
			++$t128_i;
			$t128[$t128_i] = chr($p10);
			$t128[$t128_i + T128_RB] = chr($p11);
			$t128[$t128_i + T128_RB2] = chr($p12);
			$t128[$t128_i + T128_RB3] = chr($p13);
			++$t128_i;
			$t128[$t128_i] = chr($p20);
			$t128[$t128_i + T128_RB] = chr($p21);
			$t128[$t128_i + T128_RB2] = chr($p22);
			$t128[$t128_i + T128_RB3] = chr($p23);
			++$t128_i;
			$t128[$t128_i] = chr($p30);
			$t128[$t128_i + T128_RB] = chr($p31);
			$t128[$t128_i + T128_RB2] = chr($p32);
			$t128[$t128_i + T128_RB3] = chr($p33);
			++$t128_i;

			# downsample to 64x64
			$d64_00 = downsample($p00, $p10, $p01, $p11);
			$t64[$t64_i] = chr($d64_00);
			$d64_01 = downsample($p02, $p12, $p03, $p13);
			$t64[$t64_i + T64_RB] = chr($d64_01);
			++$t64_i;
			$d64_10 = downsample($p20, $p30, $p21, $p31);
			$t64[$t64_i] = chr($d64_10);
			$d64_11 = downsample($p22, $p32, $p23, $p33);
			$t64[$t64_i + T64_RB] = chr($d64_11);
			++$t64_i;

			# downsample to 32x32
			$t32[$t32_i] = chr(downsample($d64_00, $d64_10, $d64_01, $d64_11));
			++$t32_i;
		}
		# finished a row. move pixels pointer to next row
		$pi += T128_RB + PIXELS_RB3; # go forwards the other half of this row, and the 3 rows we finished
		$t128_i += T128_RB3; # skip three row (we wrote 4 rows at once, advancing one)
		$t64_i += T64_RB; # skip a row (we wrote two rows at once, advancing one)
	}

/*
	print("quad: $quad\n");
	print("first pixel from pixels: " . $GLOBALS['pixels'][0] . "\n");


	print("Pixels:\n");
	print_table($GLOBALS['pixels'], 64, PIXELS_RB);
	print("t128:\n");
	print_table($t128, 64, T128_RB);
	print("t64:\n");
	print_table($t64, 32, T64_RB);
	print("t32:\n");
	print_table($t32, 16, T32_RB);
	exit();
*/
	#print_full_table($t32, 32, T32_RB);
	#exit();

	return array($t128, $t64, $t32);
}

# this reserves the key, it's just an optomization.
function create_random_square() {
	$GLOBALS['crs_count'] = ($GLOBALS['crs_count'] + 1) % 4;
	if($GLOBALS['crs_count'] == 0) {
		$GLOBALS['pixels'] = array_fill(0, 256 * 256 / 8, 0x00);
		$GLOBALS['pixels'][0] = 0x00;
		$GLOBALS['pixels'][PIXELS_RB] = 0x00;

		# draw some random boxes
		$num_boxes = rand(100, 2055);
		for($i = 0; $i < $num_boxes; ++$i) {
			$size = 1 << rand(0, 2);
			$x = rand(0, 255);
			$y = rand(0, 255);
			$x -= $x % $size;
			$y -= $y % $size;
			color_square($x, $y, $size);
		}
		if($GLOBALS['debug_tiles']) {
			print_full_table($GLOBALS['pixels'], 256, 256 / 8);
		}
	}

		
	$zooms = create_tile_from_quadrant($GLOBALS['crs_count']);

	return $zooms;
}
	
function init_square($url) {
	$count = 0;
	$depth = strlen($url) + 1;
	$zooms = create_random_square();
	list($t128, $t64, $t32) = $zooms;
	db_insert('tiles', 'url,t128,t64,t32', $url, $t128, $t64, $t32);
	foreach($GLOBALS['url_chars_a'] as $c) {
		if(rand(0, $depth * $depth * $depth * $depth + 13) < 3) $count += init_square($url . $c);
	}
	print("inserting '$url'\n");

	return $count + 1;
}


function init_main() {
	db_delete('tiles');

	header('Content-Type: text/plain');

	$count = init_square("");

	print("DONE (inserted $count tiles)");
	exit();
}
