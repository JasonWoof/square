<?php

require_once('code/common.php');
require_once('code/db.php');


function index_main() {
	if($_REQUEST['z']) {
		$url = ereg_replace('[^a-zA-Z0-9_-]', '', $_REQUEST['z']);
		$dots = ereg_replace('[^.]', '', $_REQUEST['z']);
		if(strlen($dots) < 3) {
			$url .= $dots;
		}
	} else {
		$url = '';
	}
	tem_set('starting_url', $url);
}
