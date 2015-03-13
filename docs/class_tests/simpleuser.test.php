<?php
// SimpleUser includes
include("../../includes/interfaces/simpleuser.interface.php");
include("../../includes/interfaces/simplerole.interface.php");
include("../../includes/abstracts/simpleuser.abstract.php");
include("../../includes/abstracts/simplerole.abstract.php");
include("../../includes/classes/simpledb.class.php");
include("../../includes/classes/ssrole.class.php");
include("../../includes/classes/ssuser.class.php");

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
print_r($user->roles);
if($user->hasPrivilege("View Site")) {
	echo "Yes they can view the site.\n";
	if($user->hasPrivilege("Edit Templates")) {
		echo "Yes they can edit templates.\n";
	}
} else {
	echo "No.\n";
}
?>
