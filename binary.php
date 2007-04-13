<?php

require_once('db_connect.php');

# return the ID of the biggest square
function get_mama() {
	return db_get_value('square_mama', 'mama', 'where id=1');
}

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

	# make sure values are 1 or 0 so they will xor properly
	$tog0 &= 1;
	$tog1 &= 1;
	$tog2 &= 1;
	$tog3 &= 1;

	$width /= 2;
	binary_square($id0, $x, $y, $width, $toggled ^ $tog0);
	binary_square($id1, $x + $width, $y, $width, $toggled ^ $tog1);
	binary_square($id2, $x, $y + $width, $width, $toggled ^ $tog2);
	binary_square($id3, $x + $width, $y + $width, $width, $toggled ^ $tog3);
}


	

function binary() {
	header('Content-Type: application/octet-stream; charset=us-ascii');
	header('Content-Length: 8192');
	$SQUARE_WIDTH = 256;
	if(isset($_REQUEST['square'])) {
		$square = format_int($_REQUEST['square']);
		if(db_get_value('square', 'count(*)', 'where id=%i', $square) == 0) {
			$square = get_mama();
		}
	} else {
		$square = get_mama();
	}
	
	$GLOBALS['pixels_rowbytes'] = $SQUARE_WIDTH / 8;
	$GLOBALS['pixels'] = array();
	for($i = 0; $i < $SQUARE_WIDTH * $SQUARE_WIDTH; $i++) {
		$GLOBALS['pixels'][] = 0;
	}

	binary_square($square, 0, 0, $SQUARE_WIDTH, 1);

	for($i = 0; $i < $SQUARE_WIDTH * $SQUARE_WIDTH; $i++) {
		print(chr($GLOBALS['pixels'][$i]));
	}
}



?>
