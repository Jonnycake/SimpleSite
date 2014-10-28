<?php
define("SIMPLESITE", 1);

include("../../includes/classes/simpleutils.class.php");
include("../../includes/classes/simpledebug.class.php");

register_shutdown_function("SimpleDebug::shutdownFunction");
set_exception_handler("SimpleDebug::exceptionHandler");
SimpleDebug::setSettings(array("loud"=>SDBG_EXCEPT | SDBG_DEPEND, "savelog"=>true));

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
		$this->debug->printLog();
		SimpleDebug::logInfo("test logInfo static.");
		sleep(rand(1,5));
		$this->debug->logDepends("A dependency error");
		if(rand(1, 10)>=8)
			$this->messUp();
	}
	public function messUp()
	{
		throw new Exception("Exception thingy");
	}
}

new SomeFirstclass();
new SomeClass();
SimpleDebug::printLog(null, true);
SimpleDebug::regDepend( array( "name"=>"SIMPLESITE", "description"=>"Can not be loaded on it's own." ), "return defined(\"SIMPLESITE\");" );
SimpleDebug::checkDepend("SIMPLESITE");
SimpleDebug::regDepend( array( "name"=>"SOMETHINGNOTDEFINED", "description"=>"Can not be loaded on it's own." ) );
SimpleDebug::checkDepend("SOMETHINGNOTDEFINED");
echo "End of filtered print\n\n";
