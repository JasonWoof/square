<?php

require_once('code/wfpl/messages.php');
require_once('code/common.php');
require_once('code/tiles.php');
require_once('binary.php');

# By default this page is disabled, since it is resource intensive. To enable
# it uncommont this line:
#
# define('ENABLE_TEST_GET_INITIAL_TOGGLE', true);
#
# This tests get_initial_toggle(). Here's what it does:
#
#   1) display a t128 tile from the database (in black)
# 
#   2) call get_initial_toggle() 128*128 times with parameters that should make
#   it pull each pixel from the t128 above
# 
#   3) display the results of the calls in step 2 overlayed (shadowing in red)
#   the display of the t128 from step 1
# 
# If you pass no arguments, you should get a pass message, and see that the red
# shadow matches the black.
#
# If you add ?url=X (where X is some url of a record in the db) it should
# report that they do NOT match, because the t128 is just that tile, where as
# get_initial_toggle() should take higher zooms into account.
#
# To find a good url install firebug enable network debugging, and zoom exactly
# 3 times. In firebug's networking section you can see the URLs for the ajax
# requests. Get the url= argument from that last one, and pass it to this page.
# The black/red output of this page is the top/left quarter of the square you
# just zoomed to.

function test_get_initial_toggle_main() {
	if(ENABLE_TEST_GET_INITIAL_TOGGLE !== true) {
		message('This page is disabled to prevent DOS attacks or accidental resource hogging. If you are the administratior you can follow the instructions at the top of the source file to enable this page.');
		return;
	}
	if(isset($_REQUEST['url'])) {
		$url = ereg_replace('[^a-zA-Z0-9._-]', '', $_REQUEST['url']);
		$pos = strpos($url, '.');
		if($pos !== false) {
			$dots = substr($url, $pos);
			$url = substr($url, 0, $pos);
		} else {
			$dots = '';
		}
	} else {
		$url = '';
		$dots = '';
	}

	message("url: $url, dots: $dots");

	$t128 = tile_get_128($url);
	if($t128 === false) {
		message("No tile at $url");
		return;
	}

	$shadows = make_t128_from_initial_toggles($url, $dots);

	if($t128 == $shadows) {
		message('They Match!');
	} else {
		message("FAILED. They don't match.");
	}

	$shadows = t128_to_dotspace($shadows);
	tem_set('shadows', $shadows);
	$t128 = t128_to_dotspace($t128);
	tem_set('t128', $t128);
}

function t128_to_dotspace($t) {
	$a = str_split($t);
	$ret = '';
	foreach($a as $c) {
		$ret .= sprintf("%08b", ord($c));
	}
	$ret = ereg_replace('0', '.', $ret);
	$ret = ereg_replace('1', '#', $ret);
	$ret = str_split($ret, 128);
	$ret = join("\n", $ret);
	return $ret;
}

function make_t128_from_initial_toggles($url, $dots) {
	$ret = '';
	$byte = 0;

	for($y = 0; $y < 128; ++$y) {
		for($x = 0; $x < 128; ++$x) {
			# every 16 pixels the first char changes
			$suf = substr(URL_CHARS, (floor($y / 16) * 8) + floor($x / 16), 1);
			# every 2 pxels the 2nd char changes
			$suf .= substr(URL_CHARS, ((floor($y / 2) % 8) * 8) + (floor($x / 2) % 8), 1);
			# every pixel it goes 0, 4, 0, 4, 0, 4, etc
			$suf .= substr(URL_CHARS, ((($y * 4) % 8) * 8) + (($x * 4) % 8), 1);

			$tog = get_initial_toggle($url . $suf, $dots);
			$byte = ($byte << 1) | $tog;
			if($x % 8 == 7) {
				$ret .= chr($byte);
				$byte = 0;
			}
		}
	}

	return $ret;
}
