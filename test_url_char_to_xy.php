<?php

require_once('code/tiles.php');

function test_url_char_to_xy_main() {
	header('Content-type: text/plain');
	for($scale = 8; $scale > 1; $scale /= 2) {
		for($quantize = 8; $quantize > 1; $quantize /= 2) {
			if($quantize <= $scale) {
				print("Passing quantize: $quantize, scale: $scale\n");
				$ci = 0;
				for($y = 0; $y < 8; ++$y) {
					for($x = 0; $x < 8; ++$x) {
						$c = substr(URL_CHARS, $ci, 1);
						list($px, $py) = url_char_to_xy($c, $quantize, $scale);
						print("($px,$py) ");
						++$ci;
					}
					print("\n");
				}
				print("\n\n");
			}
		}
	}
}
