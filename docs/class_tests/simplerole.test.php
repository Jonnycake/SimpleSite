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
echo "User Role: ";
$roleName=trim(fgets($stdin));
echo "Privilege Name: ";
$permName=trim(fgets($stdin));

$role=new SSRole($roleName, $configs);

if($role->hasPrivilege($permName))
{
	echo "Yeah they can do that.\n";
}
else
{
	echo "Nope...\n";
}
print_r($role->getPrivileges());

echo "Another Privilege: ";
$permName2=trim(fgets($stdin));
fclose($stdin);
if($role->hasPrivilege(array($permName,$permName2)))
{
	echo "Yeah they can do both.\n";
}
else
{
	echo "Nope...\n";
}

?>
