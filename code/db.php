<?php

define('DATABASE_NAME', 'db/square.db4');
define('DATABASE_HANDLER', 'db4');
#define('DATABASE_HANDLER', 'flatfile');

# this file provides convenience functions.
#
# it assumes the keys are 32-bit integers

function db4_open_write() {
	return $GLOBALS['db'] = dba_open(DATABASE_NAME, 'w', DATABASE_HANDLER);
}

function db4_open_read() {
	$db = dba_open(DATABASE_NAME, 'r', DATABASE_HANDLER);
	if($db === false) {
		die('Failed to open database "' . DATABASE_NAME . '"');
	}
	return $GLOBALS['db'] = $db;
}

function db4_destroy_create_new() {
	$ret = $GLOBALS['db'] = dba_open(DATABASE_NAME, 'n', DATABASE_HANDLER);
	if($ret === false) {
		die('failed to create database');
	}
	return $ret;
}

function db4_close() {
	if(isset($GLOBALS['db'])) {
		$res = $GLOBALS['db'];
		unset($GLOBALS['db']);
		return dba_close($res);
	}
}

function db4_insert($key, $val) {
	$binkey = bin4($key);
	dbg_log("inserting key #$key in binary that's ($binkey)");
	$ret = dba_insert(bin4($key), $val, $GLOBALS['db']);
	if($ret === false) {
		die('failed to insert');
	}
	return $ret;
}

function db4_replace($key, $val) {
	dbg_log("db4_replace($key, $val)<br />");
	$ret = dba_replace(bin4($key), $val, $GLOBALS['db']);
	if($ret === false) {
		die('failed to replace');
	}
	return $ret;
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
	$int += 1;
	$int -= 1;
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
