<?php

require_once('db_connect.php');

# return the ID of the biggest square
function get_mama() {
	return db_get_value('square_mama', 'mama', 'where id=1');
}

function color_pixel($x, $y) {
	$bit = $x % 8;
	$GLOBALS['pixels'][($x / 8) + ($y * $GLOBALS['pixels_rowbytes'])] ^= 0x80 >> $bit;
//	print("px($x,$y) &nbsp; ");
}

function color_square($x, $y, $width) {
	# FIXME optomize
	$xmax = $x + $width;
	$ymax = $y + $width;
	for(; $y < $ymax; $y++) {
		for($xx = $x; $xx < $xmax; $xx++) {
			color_pixel($xx, $y);
		}
	}
}

function binary_square($id, $x, $y, $width, $toggled) {
	// print("binary_square($id, $x, $y, $width, $toggled)<br>");
	if($width == 1) {
//		print("width==1;<br>");
		if($toggled) {
			toggle_pixel($x, $y);
		}
		return;
	}

	if($id == 0) {
//		print("id==0;<br>");
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

	binary_square($square, 0, 0, $SQUARE_WIDTH, 0);

	header('Content-Type: application/octet-stream; charset=us-ascii');
	header('Content-Length: 8192');
	for($i = 0; $i < $SQUARE_WIDTH * $SQUARE_WIDTH; $i++) {
		print(chr($GLOBALS['pixels'][$i]));
	}
	//print(join($GLOBALS['pixels']));
}



?>
