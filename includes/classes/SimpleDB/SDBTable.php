<?php
class SDBTable extends SimpleDB
{
	private $name="";
	private $primaryKey="";
	private $foreignKeys=array();
	private $rows=array();

	public function __construct($conn,$name,$configs,$primaryKey="")
	{
		$this->connection=$conn;
		parent::__construct($configs);
		$this->sdbSetName($name);
		$this->primaryKey=$primaryKey;
	}
	public function __destruct()
	{
		parent::__destruct();
		unset($this->name);
		unset($this->connection);
		unset($this->rows);
		unset($this->foreignKeys);
	}

	// Loading rows
	public function sdbGetByKey($key)
	{
		if($this->primaryKey=="")
		{
			return false;
		}
		else
		{
			if(array_key_exists($key,$this->rows))
				return $this->rows[$key];
			else
				return false;
		}
	}

	// Query building
	public function where($conditions=array(), $tblPrefix)
	{
		$query="";
		$params=array();
	/*       $conditions = array( 
	 *                               "AND" => array(
	 *                                               "username" => array(
	 *                                                                       'op' => '=',
	 *                                                                       'val' => "admin"
	 *                                                               ),
	 *                                               "password" => array(
	 *                                                                        'op' => '=',
	 *                                                                        'val' => md5("admin")
	 *                                                                )
	 *                                        )
	 *                          );
	 */
		if(count($conditions))
		{
			$query.=' WHERE';
			$x=0;
			foreach($conditions as $k=>$v)
			{
				if($v['op'])
				{
					$v=array($k => $v);
					$k="AND";
				}
				foreach($v as $col=>$arr)
				{
					$isInt=is_int($arr['val']);
					if(isset($arr['tbl']))
						$query.=" `${tblPrefix}${arr['tbl']}`.`${col}` ${arr['op']} :${col}";
					else
						$query.=" `$col` ".$arr['op']." :$col";
					$params[":$col"]=$arr['val'];
					if(++$x<count($conditions[$k]))
						$query.=" $k";
				}
			}
		}
		return array($query,$params);
	}
	/*
	Table_1
	array(
		"Table_2" => array(
					"y"=>"x",
					"Table_3"=>array(
							"y"=>"x"
					)
		)
	)
	*/
	public function join($table,$relationships, $jointype=null, $tbl_prefix="")
	{
		$query="";
		$joins="";
		foreach($relationships as $k=>$v)
		{
			if(!is_null($jointype))
				$query.=" ${jointype}";
			$query.=" JOIN `${tbl_prefix}${k}` ON `${tbl_prefix}${table}`";
			$opNum=0;
			foreach($v as $sk=>$sv)
			{
				if(is_array($sv))
				{
					$joins.=$this->join($k, array("$sk"=>$sv), $jointype, $tbl_prefix);
				}
				else
				{
					if(!is_int($sk))
					{
						if(isset($v[$opNum-1]))
						{
							$query.=" ".$v[$opNum-1]." `${tbl_prefix}${table}`";
						}
						else if($opNum>0)
						{
							$query.=" AND `${tbl_prefix}${table}`";
						}
						$opNum++;
						$query.=".`$sk`=`${tbl_prefix}${k}`.`$sv`";
					}
				}
			}
		}
		$query.=$joins;
		return $query;
	}
	public function select($cols,$conditions=array(),$extra=array(),$union=false)
	{
		if(!$this->connected())
			$this->connect();
		if($this->sdbGetErrorLevel())
			return false;

		$tblPrefix=@$this->configs['tbl_prefix'];
		$query="SELECT";

		$x=0;

		// Set up columns for the query
		if(is_array($cols))
		{
			foreach($cols as $k=>$col)
			{
				if(is_array($col))
				{
					$y=0;
					foreach($col as $colName)
						$query.=" `${tblPrefix}${k}`.`$colName`".((++$y<count($col))?',':'');
				}
				else
				{
					if($col!='*')
						$col="`$col`";
					else
						$query.=" $col";
				}
				$query.=((++$x<count($cols))?',':'');
			}
		}
		else
			$query.=" $cols";
		$query.=' FROM `'.$tblPrefix.$this->sdbGetName().'`';

		// $extra['JTYPE']="LEFT";
		// $extra['JOIN']=array('userid'=>array('uid'='id'));
		if(isset($extra['JOIN']))
		{
			$query.=$this->join($this->sdbGetName(), $extra['JOIN'], (isset($extra['JTYPE']))?$extra['JTYPE']:null,$tblPrefix);
		}

		// Get conditional statement
		$where=$this->where($conditions, $tblPrefix);
		$query.=$where[0];
		$params=$where[1];

		// "SORT"   => 'id'
		// "ORDER"  => 'DESC',
		$sorting='';
		if(isset($extra['SORT']))
		{
			$sorting.=" ORDER BY ";
			if(is_array($extra['SORT']))
			{
				$x=0;
				$count=count($extra['SORT']);
				foreach($extra['SORT'] as $sortCol)
				{
					$sorting.="`$sortCol`".((++$x<$count)?',':'');
				}
			}
			else
				$sorting.="`${extra['SORT']}`";
			if(isset($extra['ORDER']))
				$sorting.=" ${extra['ORDER']}";
		}
		if(!(isset($extra['UNION']))) $query.=$sorting;


		// "OFFSET" => '0'
		// "LIMIT"  => '1'
		if(isset($extra['LIMIT']))
		{
			$query.=" LIMIT ";
			if(isset($extra['OFFSET']))
				$query.="${extra['OFFSET']},";
			$query.=$extra['LIMIT'];
		}

		// "UNION" => array(); // Uses recursion to create the query...could cause problems
		if (isset($extra['UNION']))
		{
			foreach ($extra['UNION'] as $tbl => $details)
			{
				$unionArr=$this->openTable($tbl)->select($details['cols'],$details['conditions'],$details['extra'],true);
				$query.=" UNION ${unionArr['query']}";
				$params=array_merge($params,$unionArr['params']);
			}
		}
		if ($union) return array('query' => $query, 'params' => $params);
		else if (isset($extra['UNION'])) $query.=$sorting;

		// Execute the query and get the rows
		$stmt=$this->connection->prepare($query);
		$stmt->execute($params);
		$rows=$stmt->fetchAll();

		// Set up the array with SDBRes elements
		$ret=array();
		$x=0;
		foreach($rows as $row)
		{
			$index=(($this->primaryKey=="")?$x++:$this->primaryKey); // This really isn't going to work, x++ isn't necessarily the same element, could also have multiple primary keys
			$this->rows[$index]=new SDBRes($query);
			$this->rows[$index]->Values=$row;
		}
		return count($rows);
	}
	public function insert($values)
	{
		if(!$this->connected())
			$this->connect();
		if($this->sdbGetErrorLevel())
			return false;

		$vals=array();
		$query='INSERT INTO `'.$this->configs['tbl_prefix'].$this->sdbGetName().'` (';

		// $values = array("column" => "value");
		$x=0;
		foreach($values as $k=>$v)
		{
			$query.="`$k`".(((++$x)<count($values))?',':'');
			$vals[":$k"]=$v;
		}

		$query.=') VALUES ('.join(", ",array_keys($vals)).');';
		$stmt=$this->connection->prepare($query);
		$stmt->execute($vals);
	}
	public function update($values,$conditions=array(),$extra=array())
	{
		if(!$this->connected())
			$this->connect();
		if($this->sdbGetErrorLevel())
			return false;

		$query='UPDATE `'.(@$this->configs['tbl_prefix']).$this->sdbGetName().'` SET';
		$x=0;
		$valcount=count($values);
		$params=array();

		// $values=array( "column" => "value" );
		foreach($values as $k=>$v)
		{
			$query.=" `$k`=:$k".((++$x<$valcount)?',':'');
			$params[":$k"]=$v;
		}

		// $conditions=array("id" => 5);
		$where=$this->where($conditions);
		$query.=$where[0];
		$params=array_merge($params, $where[1]);

		$stmt=$this->connection->prepare($query);
		return $stmt->execute($params);
	}
	public function delete($conditions)
	{
		if(!$this->connected())
			$this->connect();
		if($this->sdbGetErrorLevel())
			return false;

		$query='DELETE FROM `'.(@$this->configs['tbl_prefix']).$this->sdbGetName().'`';

		// $conditions=array("id" => 5);
		$where=$this->where($conditions);
		$query.=$where[0];
		$params=$where[1];
		$stmt=$this->connection->prepare($query);
		return $stmt->execute($params);
	}

	// Getters
	public function sdbGetName()
	{
		return $this->name;
	}
	public function sdbGetPrimaryKey()
	{
		return $this->primaryKey;
	}
	public function sdbGetForeignKeys()
	{
		return $this->foreignKeys;
	}
	public function sdbGetRows()
	{
		return (count($this->rows)) ? $this->rows : false;
	}

	// Setters
	public function sdbSetName($name)
	{
		$this->name=$name;
	}
	public function sdbSetForeignKeys($keys)
	{
		$this->foreignKeys=$keys;
	}
	public function resetRows()
	{
		$this->rows=array();
	}
}
