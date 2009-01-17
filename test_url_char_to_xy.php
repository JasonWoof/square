<?php

require_once('code/tiles.php');

function test_url_char_to_xy_main() {
	header('Content-type: text/plain');
	for($size = 16; $size > 1; $size /= 2) {
		$ci = 0;
		for($y = 0; $y < 8; ++$y) {
			for($x = 0; $x < 8; ++$x) {
				$c = substr(URL_CHARS, $ci, 1);
				list($px, $py) = url_char_to_xy($c, $size);
				print("($px,$py) ");
				++$ci;
			}
			print("\n");
		}
		print("\n");
	}
}
