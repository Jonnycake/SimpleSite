<?php
define("SIMPLESITE", 1);

include("../../includes/classes/simpleutils.class.php");
include("../../includes/classes/simpledebug.class.php");

register_shutdown_function("SimpleDebug::shutdownFunction");
set_exception_handler("SimpleDebug::exceptionHandler");
SimpleDebug::setSetting("loud", 1);

class SomeFirstclass extends SimpleUtils
{
	public function __construct()
	{
		$this->createDbgInstance();
		$this->instance->logInfo("Test loginfo on SomeFirstClass");
	}
}
class SomeClass extends SimpleUtils
{
	public function __construct()
	{
		$this->createDbgInstance();
		$this->instance->logInfo("Testing logInfo");
		$this->instance->printLog();
		SimpleDebug::logInfo("test logInfo static.");
		sleep(rand(1,5));
		$this->instance->logDepends("A dependency error");
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
SimpleDebug::printLog();
