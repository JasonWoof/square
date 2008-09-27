<?php

require_once('code/common.php');
require_once('code/db.php');

function color_pixel($x, $y) {
	$bit = $x % 8;
	$GLOBALS['pixels'][($x / 8) + ($y * $GLOBALS['pixels_rowbytes'])] ^= 0x80 >> $bit;
}

# on my machine, this function takes up about 10% of the total execution time
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
		
	list($parent, $position, $tog0, $tog1, $tog2, $tog3, $id0, $id1, $id2, $id3) = db_get_square($id);

	$width /= 2;
	binary_square($id0, $x, $y, $width, $toggled ^ $tog0);
	binary_square($id1, $x + $width, $y, $width, $toggled ^ $tog1);
	binary_square($id2, $x, $y + $width, $width, $toggled ^ $tog2);
	binary_square($id3, $x + $width, $y + $width, $width, $toggled ^ $tog3);
}


function get_initial_toggle($square) {
	$toggle = 0;
	
	list($parent, $position, $tog0, $tog1, $tog2, $tog3, $id0, $id1, $id2, $id3) = db_get_square($square);
	while($parent && ($position == 0 || $position == 1 || $position == 2 || $position == 3)) {
		list($parent, $parent_position, $tog0, $tog1, $tog2, $tog3, $id0, $id1, $id2, $id3) = db_get_square($parent);
		$foo = array($tog0, $tog1, $tog2, $tog3);
		$toggle ^= $foo[$position];
		$position = $parent_position;
	}

	return $toggle;
}


function binary_main() {
	if(!am_debugging()) {
		header('Content-Type: application/octet-stream; charset=us-ascii');
		header('Content-Length: 8196');
	}
	$SQUARE_WIDTH = 256;

	$GLOBALS['pixels_rowbytes'] = $SQUARE_WIDTH / 8;
	$GLOBALS['pixels'] = array();
	for($i = 0; $i < $SQUARE_WIDTH * $SQUARE_WIDTH/ 8; $i++) {
		$GLOBALS['pixels'][] = 0;
	}

	db4_open_read();

		$square = get_square_id();

		dbg_log("square id: $square");

		$snarglepop = get_initial_toggle($square);

		binary_square($square, 0, 0, $SQUARE_WIDTH, $snarglepop);

	db4_close();


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
