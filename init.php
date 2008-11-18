<?php

require_once('code/common.php');
require_once('code/tiles.php');


# this reserves the key, it's just an optomization.
function create_random_square() {
	# FIXME
	return str_repeat("\000anov.", 512);
}
	
function init_square($url) {
	$count = 0;
	$depth = strlen($url) + 1;
	$raw = create_random_square();
	db_insert('tiles', 'url,raw', $url, $raw);
	foreach($GLOBALS['url_chars_a'] as $c) {
		if(rand(0, $depth * $depth * $depth * $depth + 13) < 3) $count += init_square($url . $c);
	}
	print("inserting '$url'\n");

	return $count + 1;
}


function init_main() {
	db_delete('tiles');

	header('Content-Type: text/plain');

	$count = init_square("");

	print("DONE (inserted $count tiles)");
	exit();
}
