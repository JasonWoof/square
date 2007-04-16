<?php

require_once('code/common.php');
require_once('code/db.php');

function index() {
	if(isset($_REQUEST['square'])) {
		db4_open_read();
		$square_id = get_square_id();
		db4_close();
	} else {
		$square_id = 1;
	}
	tem_set('square_id', $square_id);
}
