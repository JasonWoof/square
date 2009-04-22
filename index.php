<?php

require_once('code/common.php');
require_once('code/db.php');


function index_main() {
	if($_REQUEST['url']) {
		$url = ereg_replace('[^a-zA-Z0-9_-]', '', $_REQUEST['url']);
		$dots = ereg_replace('[^.]', '', $_REQUEST['url']);
		if(strlen($dots) < 3) {
			$url .= $dots;
		}
	} else {
		$url = '';
	}
	tem_set('starting_url', $url);
}
