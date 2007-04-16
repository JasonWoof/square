<?php

require_once('code/wfpl/format.php');
require_once('code/db.php');

function db_square_exists($square) {
	return db4_exists($square);
}

# if you change the db format, change the next ****** 2 ****** functions
function db_encode_square($parent, $position, $tog0, $tog1, $tog2, $tog3, $id0, $id1, $id2, $id3) {
	return
		bin4($parent) . 
		bin4($id0) . 
		bin4($id1) . 
		bin4($id2) . 
		bin4($id3) . 
		chr($position) . 
		chr(
			$tog0 + 
			($tog1 * 2) + 
			($tog2 * 4) + 
			($tog3 * 8)
		);
}

# see db_encode_square above for field names
function db_get_square($square_id) {
	$bin = db4_get($square_id);
	$togs = ord(substr($bin, 21, 1));
	return array(
		unbin4(substr($bin, 0, 4)),
		ord(substr($bin, 20, 1)),
		$togs & 1,
		($tog1 >> 1) & 1,
		($tog2 >> 2) & 1,
		($tog3 >> 3) & 1,
		unbin4(substr($bin, 4, 4)),
		unbin4(substr($bin, 8, 4)),
		unbin4(substr($bin, 12, 4)),
		unbin4(substr($bin, 16, 4))
	);
}

function db_new_id() {
	$id = 0;
	if(isset($_SERVER["UNIQUE_ID"])) {
		for($i = 0; $i < strlen($_SERVER["UNIQUE_ID"]); $i++) {
			$id <<= 1;
			$id ^= ord(substr($_SERVER["UNIQUE_ID"], $i, 1));
		}
	} else {
		$id = rand();
	}
	while(db4_exists($id)) {
		$id ^= rand();
	}

	print("new id: '$id'<br>");
	return $id;
}

function square_new($parent, $position, $tog0, $tog1, $tog2, $tog3, $id0, $id1, $id2, $id3) {
	$id = new_db_id();
	db4_insert($id, db_encode_square($parent, $position, $tog0, $tog1, $tog2, $tog3, $id0, $id1, $id2, $id3));
}

function square_replace($id, $parent, $position, $tog0, $tog1, $tog2, $tog3, $id0, $id1, $id2, $id3) {
	db4_replace($id, db_encode_square($parent, $position, $tog0, $tog1, $tog2, $tog3, $id0, $id1, $id2, $id3));
}
function get_square_id() {
	if(isset($_REQUEST['square'])) {
		$square = format_int($_REQUEST['square']);
		if(db4_exists($square)) {
			if(isset($_REQUEST['zoom']) && ($_REQUEST['zoom'] == '0' || $_REQUEST['zoom'] == '1' || $_REQUEST['zoom'] == '2' || $_REQUEST['zoom'] == '3')) {
				list($parent, $parent_position, $tog0, $tog1, $tog2, $tog3, $id0, $id1, $id2, $id3) = db_get_square($square);
				switch($_REQUEST['zoom']) {
					case '0':
						$new = $id0;
					break;
					case '1':
						$new = $id1;
					break;
					case '2':
						$new = $id2;
					break;
					case '3':
						$new = $id3;
					break;
					default:
						$new = false;
				}
				if($new) {
					# zoom zoom
					$square = $new;
				}
			}
		} else {
			print 'NOT FOUND';
			$square = 1;
		}
	} else {
		$square = 1;
	}

	return $square;
}

?>
