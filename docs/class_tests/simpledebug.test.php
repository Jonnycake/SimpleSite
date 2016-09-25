#!/usr/bin/php
<?php
define("SIMPLESITE", 1);

include("../../includes/classes/simpleutils.class.php");
include("../../includes/classes/simpledebug.class.php");

register_shutdown_function("SimpleDebug::shutdownFunction");
set_exception_handler("SimpleDebug::exceptionHandler");
SimpleDebug::setSettings(array("loud"=>SDBG_ALL, "savelog"=>true, "fatalHandler"=>"fatalHandler" ));
SimpleDebug::regDepend( array("name"=>"somesql_escape_string", "description"=>"Function deprecated in PHP 5.3.x"), "function somesql_escape_string(\$unescaped_string) { return \$unescaped_string; }", "return !function_exists('somesql_escape_string');", array(), false );
SimpleDebug::checkDepend();

class SomeFirstclass extends SimpleUtils
{
	public function __construct()
	{
		$this->createDbgInstance();
		$this->debug->logInfo("Test loginfo on SomeFirstClass");

		$st=SimpleDebug::trace();
	}
}
class SomeClass extends SimpleUtils
{
	public function __construct($disable_include=false)
	{
		echo somesql_escape_string("blah")."\n";
		$this->createDbgInstance();
		$this->debug->logInfo("Testing logInfo");
		SimpleDebug::logInfo("test logInfo static.");
		sleep(rand(1,5));
		if(rand(1, 10)>=8)
			$this->messUp();
		if((rand(1,100)>=30) && !($disable_include))
			include("simpledebug_syntaxerror.test.php");
		echo "we got to the end :O";
	}
	public function messUp()
	{
		throw new Exception("Exception thingy");
	}
}
//SimpleDebug::checkDepend(null, true);
function fatalHandler()
{
	echo "There has been an error which can not be recovered from.";
}
new SomeFirstclass();
new SomeClass();
//SimpleDebug::printLog(null, true);
