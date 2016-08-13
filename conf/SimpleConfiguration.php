<?php
class SimpleConfiguration implements ArrayAccess
{
        protected static $instance = null;

	protected $configs = array();
	protected $dynamicConfigs = array();

	protected function __construct($config_directory)
	{
		$parseDirPath = $config_directory."/parse";
		$config_files = scandir($parseDirPath);

		// Load up all of the configs
		foreach($config_files as $file) {
			if(substr($file, -5, 5) == ".json") {
				$config_info = json_decode(file_get_contents($parseDirPath."/".$file), true);
				$this->offsetSet(substr($file, 0, -5), $config_info);
			}
		}

		// Determine which configs have dynamic values
		foreach($this->configs as $subconfig => $properties) {
			foreach($properties as $property => $value) {
				if(is_array($value)) {
					$this->dynamicConfigs[] = "this.${subconfig}.${property}";
				}
			}
		}
	}

	protected function parseDynamicConfigs($config = null)
	{
		if(is_null($config)) {
			foreach($this->dynamicConfigs as $config) {
				$val = "";
				$expandedConfig = self::getVariableByAlias($config);
				foreach($expandedConfig as $portion) {
					if(is_array($portion)) {
						$val .= $this->parseDynamicConfigs($portion);
					}
					else {
						$val .= $portion;
					}
				}
				self::setVariableByAlias($this, $config, $val);
			}
		}
		else {
			$val = "";
			if(is_array($config)) {
				foreach($config as $portion) {
					if(is_array($portion)) {
						$val .= $this->parseDynamicConfigs($portion);
					}
					else {
						$val .= self::getVariableByAlias($portion);
					}
				}
				return $val;
			}
			else {
				return self::getVariableByAlias($config);
			}
		}
	}

	public static function getVariableByAlias($alias)
	{
		$expanded = explode(".", $alias);
		$val = null;
		switch($expanded[0])
		{
			case "this":
				for($i = 1; $i < count($expanded); $i++) {
					if($val == null) {
						$val = self::$instance[$expanded[$i]];
					} else {
						$val = $val[$expanded[$i]];
					}
				}
				break;
			case "server":
				break;
			case "get":
				break;
			case "post":
				break;
		}
		return $val;
	}

	public static function setVariableByAlias($self, $alias, $val)
	{
		$expanded = explode(".", $alias);
		$reference = &$self->configs;
		unset($expanded[0]);
		foreach($expanded as $expander) {
			$reference = &$reference[$expander];
		}
		$reference = $val;
		return $val;
	}

	// Singleton
        public static function instance($config_directory = __DIR__)
        {
                if(is_null(self::$instance)) {
                        self::$instance = new SimpleConfiguration($config_directory);
			self::$instance->parseDynamicConfigs();
                }

                return self::$instance;
        }


	// Interface: ArrayAccess
	public function offsetExists($offset)
	{
		return isset($this->configs[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->configs[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->configs[$offset] = $value;
		return;
	}

	public function offsetUnset($offset)
	{
		unset($this->configs[$offset]);
		return;
	}
}
