<?php

require_once('code/common.php');
require_once('code/tiles.php');
require_once('init.php');

function save_main() {
	header('Content-type: text/plain');
	if(!isset($_REQUEST['changes'])) {
		header('X-ZoomingArtError: ' + "parameter missing");
		return;
	}

	$changes = ereg_replace('[^a-zA-Z0-9._ -]', '', $_REQUEST['changes']);
	if(strlen($changes) == 0) {
		header('X-ZoomingArtError: ' + "no changes");
		return;
	}

	$rows = explode(' ', $changes);
	if(!$rows) {
		header('X-ZoomingArtError: ' + "splode error");
		return;
	}

	if(count($rows) > 5000) {
		header('X-ZoomingArtError: ' + count($rows) . ' is too many changes!');
		return;
	}

	$loaded = array();
	$loaded_ids = array();
	foreach($rows as $change) {
		$letters = ereg_replace('[^a-zA-Z0-9_-]', '', $change);
		$dots = ereg_replace('[^.]', '', $change);
		$levels = (strlen($letters) * 3) - strlen($dots);
		if($levels > 7) {
			$tile_url_len = floor(($levels - 5) / 3);
		} else {
			$tile_url_len = 0;
		}
		$tile_url = substr($letters, 0, $tile_url_len);
		$letters = substr($letters, $tile_url_len);
		$levels -= $tile_url_len * 3;
		$x = 0;
		$y = 0;
		$size = 128;
		for($i = 0; $i < strlen($letters); ++$i) {
			$pos = strpos(URL_CHARS, $letters[$i]);

			$size /= 2;
			$y += $size * floor($pos / 32);
			$x += $size * (floor($pos / 4) % 2);
			if(!--$levels) break;

			$size /= 2;
			$y += $size * (floor($pos / 16) % 2);
			$x += $size * (floor($pos / 2) % 2);
			if(!--$levels) break;

			$size /= 2;
			$y += $size * (floor($pos / 8) % 2);
			$x += $size * ($pos % 2);
			--$levels;
		}

		# echo "change: $change, tile_url: $tile_url, x: $x, y: $y, size: $size<br>";
		if(!isset($loaded[$tile_url])) {
			list($t128, $id) = tile_get_128($tile_url, true);
			# echo "id: $id<br>";
			$loaded[$tile_url] = $t128;
			$loaded_ids[$tile_url] = $id;
		}
		color_tile($x, $y, $size, $loaded[$tile_url]);
	}

	foreach($loaded as $tile_url => $t128) {
		$id = $loaded_ids[$tile_url];
		list($t64, $t32) = create_64_and_32_from_128($t128);
		if($id) {
			db_update('tiles', 't128,t64,t32', $t128, $t64, $t32, 'where id=%i', $id);
		} else {
			db_insert('tiles', 't128,t64,t32,url', $t128, $t64, $t32, $tile_url);
		}
		# echo "saved $tile_url (id $id) to database<br>";
	}

	if(isset($_REQUEST['z'])) {
		return 'binary';
	}
}

function create_64_and_32_from_128(&$t128) {
	$t32_i = 0; # 32x32 tile pixel index
	$t64_i = 0; # 64x64 tile pixel index
	$t128_i = 0; # 128x128 tile pixel index

	$t32 = str_repeat("\000", 32 * 32 / 8);
	$t64 = str_repeat("\000", 64 * 64 / 8);

	for($y = 0; $y < 32; ++$y) {
		for($x = 0; $x < 4; ++$x) {
			# pull values from t128
			$p00 = ord($t128[$t128_i]);
			$p01 = ord($t128[$t128_i + T128_RB]);
			$p02 = ord($t128[$t128_i + T128_RB2]);
			$p03 = ord($t128[$t128_i + T128_RB3]);
			++$t128_i;
			$p10 = ord($t128[$t128_i]);
			$p11 = ord($t128[$t128_i + T128_RB]);
			$p12 = ord($t128[$t128_i + T128_RB2]);
			$p13 = ord($t128[$t128_i + T128_RB3]);
			++$t128_i;
			$p20 = ord($t128[$t128_i]);
			$p21 = ord($t128[$t128_i + T128_RB]);
			$p22 = ord($t128[$t128_i + T128_RB2]);
			$p23 = ord($t128[$t128_i + T128_RB3]);
			++$t128_i;
			$p30 = ord($t128[$t128_i]);
			$p31 = ord($t128[$t128_i + T128_RB]);
			$p32 = ord($t128[$t128_i + T128_RB2]);
			$p33 = ord($t128[$t128_i + T128_RB3]);
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
		# finished a row. move pixel pointers to next row
		$t128_i += T128_RB3; # skip three row (we wrote 4 rows at once, advancing one)
		$t64_i += T64_RB; # skip a row (we wrote two rows at once, advancing one)
	}

	return array($t64, $t32);
}

?>
