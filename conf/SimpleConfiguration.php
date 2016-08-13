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

	protected function parseDynamicConfigs($config = null, $resolve = false)
	{
		if(is_null($config)) {
			foreach($this->dynamicConfigs as $config) {
				$val = "";
				$expandedConfig = self::getVariableByAlias($config);
				foreach($expandedConfig as $portion) {
					if(is_array($portion)) {
						$val .= $this->parseDynamicConfigs($portion, true);
					}
					else {
						$val .= $portion;
					}
				}
				self::setVariableByAlias($config, $val);
			}
		}
		else {
			$val = "";
			if(is_array($config)) {
				if(isset($config["check"])) {
					$matches = array();
					switch($config["check"][0])
					{
						case "=":
							if(preg_match("/{(.*)}/si", $config["check"][1], $matches)) {
								$val1 = self::getVariableByAlias($matches[1]);
							}
							else {
								$val1 = $config["check"][1];
							}

							if(preg_match("/{(.*)}/si", $config["check"][2], $matches)) {
								$val2 = self::getVariableByAlias($matches[2]);
							}
							else {
								$val2 = $config["check"][2];
							}

							if($val1 == $val2) {
								return $this->parseDynamicConfigs($config["true"]);
							}
							else {
								return $this->parseDynamicConfigs($config["false"]);
							}
							break;
						case "<>":
							break;
						case "<":
							break;
						case ">":
							break;
						case "<=":
							break;
						case ">=":
							break;
					}
				}
				else {
					foreach($config as $portion) {
						if(is_array($portion)) {
							$val .= $this->parseDynamicConfigs($portion, true);
						}
						else {
							if($resolve) {
								$val .= self::getVariableByAlias($portion);
							} else {
								$val .= $portion;
							}
						}
					}
					return $val;
				}
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
				$val = self::$instance;
				break;
			case "server":
				$val = $_SERVER;
				break;
			case "get":
				$val = $_GET;
				break;
			case "post":
				$val = $_POST;
				break;
			case "session":
				$val = $_SESSION;
				break;
			default:
				return null;
		}

		for($i = 1; $i < count($expanded); $i++) {
			$val = $val[$expanded[$i]];
		}
		return $val;
	}

	public static function setVariableByAlias($alias, $val)
	{
		$expanded = explode(".", $alias);
		switch($expanded[0])
		{
			case "this":
				$reference = &self::$instance->configs;
				break;
			case "server":
				$reference = &$_SERVER;
				break;
			case "get":
				$reference = &$_GET;
				break;
			case "post":
				$reference = &$_POST;
				break;
			case "session":
				$reference = &$_SESSION;
				break;
			default:
				return false;
		}
		unset($expanded[0]);
		foreach($expanded as $expander) {
			$reference = &$reference[$expander];
		}
		$reference = $val;
		return true;
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
