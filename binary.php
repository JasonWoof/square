<?php

require_once('db_connect.php');

function binary() {
	$hex = '0123456789abcdef';
	$pixels = '';

	for($y = 0; $y < 4; $y++) {
		for($x = 0; $x < 4; $x++) {
			$coord = substr($hex, $x + 4 * $y, 1);
			$ret = db_get_value('square', 'pixels', 'where address=%"', $coord);
			if($ret === null) {
				$pixels .= "cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc";
			} else {
				$pixels .= $ret;
			}
		}
	}

	header('Content-Type: application/octet-stream');
	header('Content-Length: 8192');
	echo $pixels;
}



?>
