<?php
include_once "imap.php";
$account = '<EMAIL>';
$password = '<PASSWORD>';
$url = '{<URL>:993/imap/ssl/novalidate-cert}';
$tmp = new \Taniguchi\Imap($account, $password);
var_dump($tmp->setUrl($url)->getFolders());
?>
