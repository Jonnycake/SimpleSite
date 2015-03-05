<?php
// SimpleUser includes
include("../../includes/interfaces/simpleuser.interface.php");
include("../../includes/abstracts/simpleuser.abstract.php");
include("../../includes/classes/simpledb.class.php");
include("../../includes/classes/ssuser.class.php");

// SimpleRole Includes
/*include("../../includes/interfaces/simplerole.interface.php");
include("../../includes/abstracts/simplerole.abstract.php");
include("../../includes/classes/ssrole.class.php");*/

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
echo "Username: ";
$username=trim(fgets($stdin));
echo "Password: ";
$password=trim(fgets($stdin));
fclose($stdin);
$user=new SSUser($username, $password, $configs);
/*
$role=new SSRole($roleName, $configs);

if($role->hasPrivilege($permName))
{
	echo "Yeah they can do that.\n";
}
else
{
	echo "Nope...\n";
}
print_r($role->getPrivileges());*/

?>
