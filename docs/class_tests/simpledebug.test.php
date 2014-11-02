<?php
define("SIMPLESITE", 1);

include("../../includes/classes/simpleutils.class.php");
include("../../includes/classes/simpledebug.class.php");

register_shutdown_function("SimpleDebug::shutdownFunction");
set_exception_handler("SimpleDebug::exceptionHandler");
SimpleDebug::setSettings(array("loud"=>SDBG_DEPEND, "savelog"=>true));
SimpleDebug::regDepend( array("name"=>"somesql_escape_string", "description"=>"Function deprecated in PHP 5.3.x"), "function somesql_escape_string(\$unescaped_string) { return \$unescaped_string; }", "return !function_exists('somesql_escape_string');" );
echo "End of filtered print\n\n";
echo "asdf";
SimpleDebug::rootCauseFinder();

class SomeFirstclass extends SimpleUtils
{
	public function __construct()
	{
		$this->createDbgInstance();
		$this->debug->logInfo("Test loginfo on SomeFirstClass");

		$st=SimpleDebug::trace();
		//print_r($st);
	}
}
class SomeClass extends SimpleUtils
{
	public function __construct()
	{
		$this->createDbgInstance();
		$this->debug->logInfo("Testing logInfo");
		//$this->debug->printLog();
		SimpleDebug::logInfo("test logInfo static.");
		sleep(rand(1,5));
		/*if(rand(1, 10)>=8)
			$this->messUp();*/
	}
	public function messUp()
	{
		throw new Exception("Exception thingy");
	}
}
//SimpleDebug::checkDepend(null, true);

new SomeFirstclass();
new SomeClass();
//SimpleDebug::printLog(null, true);
