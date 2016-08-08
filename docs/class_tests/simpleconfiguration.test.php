#!/usr/bin/php
<?php
include("../../conf/SimpleConfiguration.php");
$configs = SimpleConfiguration::instance();
print_r($configs['path']);
?>
