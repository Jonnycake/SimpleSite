#!/usr/bin/php
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
$configs['host']= trim(fgets($stdin));
echo "DB Login: ";
$configs['username']= trim(fgets($stdin));
echo "DB Password: ";
$configs['password']=trim(fgets($stdin));
echo "DB Database: ";
$configs['database']=trim(fgets($stdin));
echo "DB Table Prefix: ";
$configs['tbl_prefix']=trim(fgets($stdin));

// Test adding a new user
echo "New Username: ";
$newUsername = trim(fgets($stdin));
echo "New User Passsword: ";
$newPassword = trim(fgets($stdin));
echo "New User Description: ";
$description = trim(fgets($stdin));

// $username=null, $password=null, $dbconf=array(), $require_password=true
$userObj = new SSUser($newUsername, $newPassword, $configs, false);
$userObj->save(true);

//  Test logging in as that user
$userObjLoggedIn = new SSUser($newUsername, $newPassword, $configs);
$userObjGuest = new SSUser($newUsername, $newPassword . "fail", $configs);
if($userObjLoggedIn->isGuest()) {
	echo "Failed to log in properly.\n";
}
else {
	echo "Logged in properly :D\n";
}

if(!$userObjGuest->isGuest()) {
	echo "Failed to prevent log in with wrong password.\n";
}
else {
	echo "Prevented login with wrong password :D\n";
}

// Test updating that user's profile
foreach($userObjLoggedIn->getInfo() as $attr => $val) {
	echo "$attr: ";
	$userObjLoggedIn->setInfo($attr, trim(fgets($stdin)));
}
$userObjLoggedIn->save();

foreach($userObjLoggedIn->getInfo(null, true) as $attr => $val) {
	echo "$attr: $val\n";
}


//  Test deleting that user
$userObjLoggedIn->delete();


// Uninstall
$userObjLoggedIn->uninstall();
?>
