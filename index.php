<?php

require_once('code/common.php');

function index() {
	$square_id = get_square_id();
	tem_set('square_id', $square_id);
}
