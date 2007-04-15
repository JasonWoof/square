<?php

require_once('db_connect.php');

require_once('code/common.php');

function color_pixel($x, $y) {
	$bit = $x % 8;
	$GLOBALS['pixels'][($x / 8) + ($y * $GLOBALS['pixels_rowbytes'])] ^= 0x80 >> $bit;
}

function color_square($x, $y, $width) {
	# it's always going to be alligned to $width, and $width will never be 1
	$bit = $x % 8;
	switch($width) {
		case 2:
			$GLOBALS['pixels'][($x / 8) + ($y * $GLOBALS['pixels_rowbytes'])] ^= 0xc0 >> $bit;
			$GLOBALS['pixels'][($x / 8) + (($y + 1) * $GLOBALS['pixels_rowbytes'])] ^= 0xc0 >> $bit;
		return;
		case 4:
			$GLOBALS['pixels'][($x / 8) + ($y * $GLOBALS['pixels_rowbytes'])] ^= 0xf0 >> $bit;
			$GLOBALS['pixels'][($x / 8) + (($y + 1) * $GLOBALS['pixels_rowbytes'])] ^= 0xf0 >> $bit;
			$GLOBALS['pixels'][($x / 8) + (($y + 2) * $GLOBALS['pixels_rowbytes'])] ^= 0xf0 >> $bit;
			$GLOBALS['pixels'][($x / 8) + (($y + 3) * $GLOBALS['pixels_rowbytes'])] ^= 0xf0 >> $bit;
		return;
		default:
			$x /= 8;
			$xmax = $x + ($width / 8);
			$ymax = ($y + $width) * $GLOBALS['pixels_rowbytes'];
			for($yy = $y * $GLOBALS['pixels_rowbytes']; $yy < $ymax; $yy += $GLOBALS['pixels_rowbytes']) {
				for($xx = $x; $xx < $xmax; $xx++) {
					$GLOBALS['pixels'][$xx + $yy] = 0xff;
				}
			}
		return;
	}
}

function binary_square($id, $x, $y, $width, $toggled) {
	if($width == 1) {
		if($toggled) {
			color_pixel($x, $y);
		}
		return;
	}

	if($id == 0) {
		if($toggled) {
			color_square($x, $y, $width);
		}
		return;
	}
		
	list($tog0, $tog1, $tog2, $tog3, $id0, $id1, $id2, $id3) = db_get_row('square', 'tog0,tog1,tog2,tog3,id0,id1,id2,id3', 'where id=%i', $id);

	$width /= 2;
	binary_square($id0, $x, $y, $width, $toggled ^ $tog0);
	binary_square($id1, $x + $width, $y, $width, $toggled ^ $tog1);
	binary_square($id2, $x, $y + $width, $width, $toggled ^ $tog2);
	binary_square($id3, $x + $width, $y + $width, $width, $toggled ^ $tog3);
}


function get_initial_toggle($square) {
	$toggle = 0;

	list($parent, $position) = db_get_row('square', 'parent,position', 'where id=%i', $square);
	while($parent && ($position == '0' || $position == '1' || $position == '2' || $position == '3')) {
		$field = 'tog' . $position;
		list($parent, $position, $tog) =  db_get_row('square', "parent,position,$field", 'where id=%i', $parent);
		$toggle ^= $tog;
	}

	return $toggle;
}


function binary() {
	header('Content-Type: application/octet-stream; charset=us-ascii');
	header('Content-Length: 8196');
	$SQUARE_WIDTH = 256;

	$square = get_square_id();
	
	$GLOBALS['pixels_rowbytes'] = $SQUARE_WIDTH / 8;
	$GLOBALS['pixels'] = array();
	for($i = 0; $i < $SQUARE_WIDTH * $SQUARE_WIDTH/ 8; $i++) {
		$GLOBALS['pixels'][] = 0;
	}

	binary_square($square, 0, 0, $SQUARE_WIDTH, get_initial_toggle($square));


	# let the client know that it's looking at
	print(chr($square >> 24));
	print(chr(($square >> 16) & 0xff));
	print(chr(($square >> 8) & 0xff));
	print(chr($square & 0xff));

	for($i = 0; $i < $SQUARE_WIDTH * $SQUARE_WIDTH/ 8; $i++) {
		print(chr($GLOBALS['pixels'][$i]));
	}
}


?>
