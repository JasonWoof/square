<?php

require_once('db_connect.php');

function encode_square($x, $y, $pixels) {
	$BOX_WIDTH = 64;
	$LINE_WIDTH = 256;
	$out = "";

	# convert from box to pixel coordinates
	$x *= $BOX_WIDTH;
	$y *= $BOX_WIDTH;

	$ymax = $y + $BOX_WIDTH;

	for( ; $y < $ymax; $y++) {
		$scan = substr($pixels, $y * $LINE_WIDTH + $x, $BOX_WIDTH);
		for($i = 0; $i < $BOX_WIDTH; ) {
			$bits = 0;
			for($bit = 0; $bit < 8; $bit++) {
				$bits *= 2;
				if(substr($scan, $i++, 1) !== chr(0)) {
					$bits += 1;
				}
			}
			$out .= chr($bits);
		}
	}
	return $out;
}


function init_square($parent, $position, $depth) {
	db_insert('square', 'parent,position,tog0,tog1,tog2,tog3,id0,id1,id2,id3', $parent, $position, 1, 1, 1, 1, 0, 0, 0, 0);
	$me = db_auto_id();
	$id0 = $id1 = $id2 = $id3 = 0;
	if(rand(0, $depth + 2) < 3) $id0 = init_square($me, 0, $depth + 1);
	if(rand(0, $depth + 2) < 3) $id1 = init_square($me, 1, $depth + 1);
	if(rand(0, $depth + 2) < 3) $id2 = init_square($me, 2, $depth + 1);
	if(rand(0, $depth + 2) < 3) $id3 = init_square($me, 3, $depth + 1);
	if($id0 || $id1 || $id2 || $id3) {
		db_update('square', 'id0,id1,id2,id3', $id0, $id1, $id2, $id3, 'where id=%i', $me);
	}

	return $me;
}


function init() {
	$GLOBALS['pixels'] = read_whole_file('start.bin');

	db_delete('square');

	$mama = init_square(0, 0, 0);

	db_replace('square_mama', 'id, mama', '1', $mama);

	print("mama: '$mama'");
}



?>
