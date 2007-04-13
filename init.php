<?php

require_once('db_connect.php');

function encode_square($x, $y, $pixels) {
	$BOX_WIDTH = 64;
	$LINE_WIDTH = 512;
	$out = "";
	$ymax = $y + 4;

	for( ; $y < $ymax; $y++) {
		$scan = substr($pixels, $y * $LINE_WIDTH + $x * $BOX_WIDTH, $BOX_WIDTH);
		for($i = 0; $i < $BOX_WIDTH; ) {
			$bits = 0;
			for($bit = 0; $bit < 8; $bit++) {
				$bits = $bits << 1;
				if(substr($scan, $i++, 1) === '1') {
					$bits |= 1;
				}
			}
			$out .= chr($bits);
		}
	}
	return $out;
}



function init() {
	$pixels = read_whole_file('start.txt');
	$hex = "0123456789abcdef";

	db_delete('square');

	for($x = 0; $x < 4; $x++) {
		for($y = 0; $y < 4; $y++) {
			$coord = substr($hex, $x + 4 * $y, 1);
			db_insert('square', 'address,pixels', $coord, encode_square($x, $y, &$pixels));
		}
	}
}



?>
