#!/usr/bin/php
<?php
include("../../conf/SimpleConfiguration.php");
$configs = SimpleConfiguration::instance(__DIR__."/conf/");
print_r($configs);
?>
