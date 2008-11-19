<?php

require_once('code/tiles.php');
require_once('init.php');
require_once('binary.php');

function db_browse_main() {
	if(isset($_REQUEST['url'])) {
		$url = ereg_replace('[^a-zA-Z0-9_-]', '', $_REQUEST['url']);
	} else {
		$url = '';
	}

	tem_set('url', $url);

	$row = db_get_column('tiles', 'url', 'where url like "%s_"', $url);
	if($row) {
		foreach($row as $sub_url) {
			tem_set('child_url', $sub_url);
			tem_set('child_name', substr($sub_url, -1));
			tem_show('child_link');
		}
	}

	if($url != '') {
		tem_set('back_url', substr($url, 0, -1));
		tem_show('back_link');
	}

	$GLOBALS['debug_tiles'] = true;
	ob_start();
	create_random_square();
	create_random_square();
	create_random_square();
	create_random_square();
	ob_clean();
	ob_start();
	create_random_square();
	tem_set('pixels', ob_get_clean());
	ob_start();
	tile_get_128($url); # because it prints now
	tem_set('t128', ob_get_clean());
	ob_start();
	tile_get_64($url); # because it prints now
	tem_set('t64', ob_get_clean());
	ob_start();
	tile_get_32($url); # because it prints now
	tem_set('t32', ob_get_clean());
}
