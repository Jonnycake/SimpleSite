<?php
define("SIMPLESITE", 1);

include("../../includes/classes/simpleutils.class.php");
include("../../includes/classes/simpledebug.class.php");

set_exception_handler("SimpleDebug::logException");

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
		sleep(1);
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
//print_r(SimpleDebug::getFullLog(array("SomeFirstclass", "SomeClass")));
SimpleDebug::printLog(null, "Info");
