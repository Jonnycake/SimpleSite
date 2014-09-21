<?php
define("SIMPLESITE", 1);

include("../../includes/classes/simpleutils.class.php");
include("../../includes/classes/simpledebug.class.php");

class SomeClass extends SimpleUtils
{
	public function __construct()
	{
		$this->createDbgInstance();
		$this->instance->logInfo("Testing logInfo");
		$this->instance->printLog();
		SimpleDebug::logInfo("test logInfo static.");
		SimpleDebug::printLog();
	}
}
new SomeClass();
