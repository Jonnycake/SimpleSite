<?php
define("SIMPLESITE", 1);
include("../../includes/classes/simpleutils.class.php");
include("../../includes/classes/simpledate.class.php");

echo "Current Time (24 hr): ";
$dateObj=SimpleDate::getObject();
echo $dateObj->milTime()."\n";
$stdin=fopen("php://stdin","r");
echo "Testing getDOW:\n";
echo "\tDay: ";
$day=trim(fgets($stdin));
echo "\tMonth: ";
$month=trim(fgets($stdin));
echo "\tYear: ";
$year=trim(fgets($stdin));
echo "\t"SimpleDate::getDOW($day, $month, $year)."\n";
echo "Testing Timezone Translation:\n";
echo "\tTarget Timezone: ";
$timezone=trim(fgets($stdin));
echo "Testing Daylight Savings Check:\n";
echo "\tDay: ";
$day=trim(fgets($stdin));
echo "\tMonth: ";
$month=trim(fgets($stdin));
echo "\tYear: ";
$year=trim(fgets($stdin));
echo "Testing Date Calculations (Time from Date):\n";
echo "\tDay: ";
$day=trim(fgets($stdin));
echo "\tMonth: ";
$month=trim(fgets($stdin));
echo "\tYear: ";
$year=trim(fgets($stdin));
echo "\tTime to Advance: "
$tta=trim(fgets($stdin));
echo "Testing 24hr to AM/PM:\n";
echo "\t24 Hour Time: ";
$time=trim(fgets($stdin));
echo "Testing Timestamp Translation:\n";
echo "\nUnix Timestamp: ";
$time=trim(fgets($stdin));
echo "\nFormatted Time (m/d/Y h:i:s: ";
$time=trim(fgets($stdin));
fclose($stdin);
?>
