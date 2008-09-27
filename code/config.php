<?php

function am_debugging() {
	return false;
}

function dbg_log($msg) {
	if(am_debugging()) {
		echo "$msg<br />\n";
	}
}
