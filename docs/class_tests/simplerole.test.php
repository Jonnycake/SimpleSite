<?php
include("../../includes/interfaces/simplerole.interface.php");
include("../../includes/abstracts/simplerole.abstract.php");
include("../../includes/classes/simpledb.class.php");
include("../../includes/classes/ssrole.class.php");

$configs=array();
$stdin=fopen("php://stdin","r");

// Get database information
echo "DB Type: ";
$configs['type']=trim(fgets($stdin));
echo "DB Hostname: ";
$configs['host']=trim(fgets($stdin));
echo "DB Login: ";
$configs['username']=trim(fgets($stdin));
echo "DB Password: ";
$configs['password']=trim(fgets($stdin));
echo "DB Database: ";
$configs['database']=trim(fgets($stdin));
echo "DB Table Prefix: ";
$configs['tbl_prefix']=trim(fgets($stdin));

fclose($stdin);

$adminRole=new SSRole("Administrator", $configs);

if($adminRole->hasPrivilege("View Site"))
{
	echo "Yay\n";
}
else
{
	echo "Nope...\n";
}

?>
