<?php
// SimpleUser includes
define('SIMPLESITE',1);
include("../../includes/classes/Core/SimpleUtils.php");
include("../../includes/classes/Core/SimpleDebug.php");
include("../../includes/interfaces/simpleuser.interface.php");
include("../../includes/interfaces/SimpleRole/simpleRoleI.php");
include("../../includes/abstracts/simpleuser.abstract.php");
include("../../includes/abstracts/SimpleRole/SimpleRole.php");
include("../../includes/classes/SimpleDB/SimpleDB.php");
include("../../includes/classes/SimpleDB/SDBTable.php");
include("../../includes/classes/SimpleDB/SDBRes.php");
include("../../includes/classes/SimpleRole/SSRole.php");
include("../../includes/classes/ssuser.class.php");

register_shutdown_function("SimpleDebug::shutdownFunction");
set_exception_handler("SimpleDebug::exceptionHandler");
SimpleDebug::setSettings(array("loud"=>1, "savelog"=>true));
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
$user=new SSUser($username, $password, $configs);
echo "New Password: ";
$newpassword=trim(fgets($stdin));
$user->password=$newpassword;
echo "Current Roles:\n";
print_r($user->getRoles());
echo "Removed Role(s): ";
$removedRoles=explode(';', trim(fgets($stdin)));
$user->removeRole($removedRoles);
echo "New Role(s): ";
$newRoles=explode(';', trim(fgets($stdin)));
$user->addRole($newRoles);
$user->save();
echo "New Username: ";
$newuser=trim(fgets($stdin));
echo "Password for ${newuser}: ";
$newuserpass=trim(fgets($stdin));
echo "New User Role: ";
$newuserrole=trim(fgets($stdin));
$user2=new SSUser(null, null, $configs);
$user2->roles[]=$newuserrole;
print_r($user2->roles);
$user2->username=$newuser;
$user2->password=$newuserpass;
$user2->save(true);
echo "User(s) to Delete: ";
$usersDeleted=explode(';', trim(fgets($stdin)));
fclose($stdin);
foreach($usersDeleted as $username) {
	$user3=new SSUser($username, null, $configs, false);
	$user3->delete();
}

// Get user info
print_r($user->getInfo());
print_r($user->getInfo());

// Get user's roles
print_r($user->roles);

//$user->delete();

/// Check their permissions
if($user->hasPrivilege("View Site")) {
	echo "Yes they can view the site.\n";
	if($user->hasPrivilege("Edit Templates")) {
		echo "Yes they can edit templates.\n";
	}
} else {
	echo "No.\n";
}
?>
