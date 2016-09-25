<?php
class SDBRes extends SDBTable
{
	private $query="";
	private $canChange=false;
	private $columns=array();
	protected $Values=array();

	// Boostrap the getters and setters
	public function __call($method,$args)
	{
		$col=strtolower(substr($method,3,1)).substr($method,4);
		switch(substr($method,0,3))
		{
			case "get":
				if(in_array($col,$this->sdbGetColumns()))
					return $this->Values[$col];
				break;
			case "set":
				if(in_array($col,$this->sdbGetColumns()))
					$this->Values[$col]=$args[0];
				break;
		}
	}

	// Initialize result values
	public function __construct($query="",$columns=array())
	{
		$this->query=$query;
		$this->columns=$columns;
	}
	public function sdbUpdateValues()
	{
		if($this->canChange)
			$this->rawQry($this->query,'');
	}

	// Basic Getters
	public function sdbGetColumns($table="",$database=null,$like='%')
	{
		if(count($this->columns)==0)
			foreach($this->Values as $k=>$v)
				if(!(is_int($k)))
					$this->columns[]=$k;
		return $this->columns;
	}
	public function sdbGetQuery()
	{
		return $this->query;
	}
	public function sdbGetValues()
	{
		return $this->Values;
	}

	// Basic Setters
	public function sdbSetColumns($columns)
	{
		$this->columns=$columns;
	}
	public function sdbSetQuery($query)
	{
		$this->query=$query;
	}
	public function sdbSetValues($values)
	{
		$this->Values=$values;
	}
}
