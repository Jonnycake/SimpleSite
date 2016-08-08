<?php
class SimpleConfiguration implements ArrayAccess
{
        protected static $instance = null;

	protected $configs = array();

	protected function __construct($config_directory)
	{
		echo "Construct";
		$parseDirPath = $config_directory."/parse";
		$config_files = scandir($parseDirPath);
		foreach($config_files as $file) {
			if(substr($file, -5, 5) == ".json") {
				$config_info = json_decode(file_get_contents($parseDirPath."/".$file), true);
				$this->offsetSet(substr($file, 0, -5), $config_info);
			}
		}
	}


	// Singleton
        public static function instance()
        {
		echo "Instance";
                if(is_null(self::$instance)) {
                        self::$instance = new SimpleConfiguration(__DIR__);
                }

                return self::$instance;
        }


	// Interface: ArrayAccess
	public function offsetExists($offset)
	{
		echo "Exists";

		return isset($this->configs[$offset]);
	}

	public function offsetGet($offset)
	{
		echo "get";
		echo "wtf";
		print_r($this->configs);
		echo "wtf2";
		return $this->configs[$offset];
	}

	public function offsetSet($offset, $value)
	{
		echo "set";
		$this->configs[$offset] = $value;
		return;
	}

	public function offsetUnset($offset)
	{
		echo "unset";
		unset($this->configs[$offset]);
		return;
	}
}
