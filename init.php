<?php

require_once('code/common.php');
require_once('code/db.php');


# this reserves the key, it's just an optomization.
function square_new_blank() {
	$id = ++$GLOBALS['last_square_id'];
	db4_insert($id, "1234567890123456789012");
	return $id;
}
	


function init_square($parent, $position, $depth) {
	$me = square_new_blank(); # reserve a slot/key in the database
	$id0 = $id1 = $id2 = $id3 = 0;
	if(rand(0, $depth + 2) < 3) $id0 = init_square($me, 0, $depth + 1);
	if(rand(0, $depth + 2) < 3) $id1 = init_square($me, 1, $depth + 1);
	if(rand(0, $depth + 2) < 3) $id2 = init_square($me, 2, $depth + 1);
	if(rand(0, $depth + 2) < 3) $id3 = init_square($me, 3, $depth + 1);
	print("inserting #$me with children: $id0, $id1, $id2, $id<br />\n");
	square_replace($me, $parent, $position, rand(0,1), rand(0,1), rand(0,1), rand(0,1), $id0, $id1, $id2, $id3);

	return $me;
}


function init_main() {
	db4_destroy_create_new();

	db4_insert(0, "saving this for later.");

	$GLOBALS['last_square_id'] = 0;

	init_square(0, 0, 0);

	db4_close();

	print("DONE (inserted " . $GLOBALS['last_square_id'] . " nodes)");
}



?>
