#!/usr/bin/php
<?php
include("../../conf/SimpleConfiguration.php");
global $_SERVER;
global $_SESSION;
$_SESSION['selected_theme'] = "blahzor";
$_SERVER =array("DOCUMENT_ROOT" => "/var/www/html");
$configs = SimpleConfiguration::instance(__DIR__."/conf/");
print_r($configs);
$_SESSION['selected_theme'] = "";
$configs = SimpleConfiguration::reload();
print_r($configs);
?>
