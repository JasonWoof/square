<?php

define('DATABASE_NAME', 'db/square.db4');
define('DATABASE_HANDLER', 'db4');

# this file provides convenience functions.
#
# it assumes the keys are 32-bit integers

function db4_open_write() {
	return $GLOBALS['db'] = dba_open(DATABASE_NAME, 'w');
}

function db4_open_read() {
	return $GLOBALS['db'] = dba_open(DATABASE_NAME, 'r');
}

function db4_destroy_create_new() {
	return $GLOBALS['db'] = dba_open(DATABASE_NAME, 'n', DATABASE_HANDLER);
}

function db4_close() {
	if(isset($GLOBALS['db'])) {
		$res = $GLOBALS['db'];
		unset($GLOBALS['db']);
		return dba_close($res);
	}
}

function db4_insert($key, $val) {
	return dba_insert(bin4($key), $val, $GLOBALS['db']);
}

function db4_replace($key, $val) {
	return dba_replace(bin4($key), $val, $GLOBALS['db']);
}

function db4_delete($key) {
	return dba_delete(bin4($key), $GLOBALS['db']);
}

function db4_exists($key) {
	return dba_exists(bin4($key), $GLOBALS['db']);
}

function db4_get($key) {
	return dba_fetch(bin4($key), $GLOBALS['db']);
}

function bin4($int) {
	return (chr(($int >> 24) & 0xff) . chr(($int >> 16) & 0xff) . chr(($int >> 8) & 0xff) . chr($int & 0xff));
}

function unbin4($bin) {
	return (
		(ord(substr($bin, 0, 1)) << 24) |
		(ord(substr($bin, 1, 1)) << 16) |
		(ord(substr($bin, 2, 1)) << 8) |
		ord(substr($bin, 3, 1))
	);
}

?>
