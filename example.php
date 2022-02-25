<?php
include_once "imap.php";

// Call this file like this: php -f example.php "<account>" "<password>" "<url>" "<port>"
$account = "{$argv[1]}";
$password = "{$argv[2]}";
$url = "{$argv[3]}";
$port = (isset($argv[4]) && !empty("{$argv[4]}")) ? "{$argv[4]}" : 993;
// Two way to call the class
$url = "{{$url}:{$port}/service=imap/ssl/novalidate-cert}";
$tmp = new \Taniguchi\Imap($account, $password, $url);
/*$tmp = new \Taniguchi\Imap($account, $password, $url, $port);
$tmp->setSsl()->setValidate(false);*/
var_dump($tmp->addRejects('daticert.xml','smime.p7s')->read(1, 2));
?>
