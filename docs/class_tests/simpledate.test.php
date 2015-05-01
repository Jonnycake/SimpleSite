<?php
define("SIMPLESITE", 1);
include("../../includes/classes/simpleutils.class.php");
include("../../includes/classes/simpledate.class.php");

$dateObj=SimpleDate::getObject();
echo $dateObj->milTime()."\n";
?>
