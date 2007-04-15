<?php

require_once('db_connect.php');

# return the ID of the biggest square
function get_mama() {
	return db_get_value('square_mama', 'mama', 'where id=1');
}

function get_square_id() {
	if(isset($_REQUEST['square'])) {
		$square = format_int($_REQUEST['square']);
		if(db_get_value('square', 'count(*)', 'where id=%i', $square) == 0) {
			$square = get_mama();
		} else {
			if(isset($_REQUEST['zoom']) && ($_REQUEST['zoom'] == '0' || $_REQUEST['zoom'] == '1' || $_REQUEST['zoom'] == '2' || $_REQUEST['zoom'] == '3')) {
				$field = 'id' . $_REQUEST['zoom'];
				$new = db_get_value('square', $field, 'where id=%i', $square);
				if($new) {
					# zoom zoom
					$square = $new;
				}
			}
		}
	} else {
		$square = get_mama();
	}

	return $square;
}

?>
