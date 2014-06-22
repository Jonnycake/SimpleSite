<?php
/*
 *    SimpleDB Class v0.2: Allow for easy and secure database access.
 *    Copyright (C) 2014 Jon Stockton
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class SimpleDB
{
	protected $configs=array();
	protected $connection=null;
	protected $debug=0;
	private $qryCache=array();
	private $curConfigs=array();
	private $tables=array();
	private $errorLevel=0;
	protected $compatQrys=array(
					'SHOW TABLES' => array(
								'mysql' => 'SHOW TABLES IN `{DATABASE}` LIKE :like'
							),
					'SHOW COLUMNS' => array(
								'mysql' => 'SHOW COLUMNS IN `{DATABASE}`.`{TABLE}` WHERE Field LIKE :like'
							),
					'SHOW DATABASES' => array(
								'mysql' => 'SHOW DATABASES'
							),
					'SHOW CREATE TABLE' => array(
								'mysql' => 'SHOW CREATE TABLE {TABLE}'
							),
				);

	public function __construct($configs=array("type" => "mysql", "host" => "127.0.0.1", "username" => "root", "password" => "", "database" => "", "tbl_prefix" => ""),$debug=0)
	{
		$this->configs=$configs;
		$this->curConfigs=$this->configs;
		$this->debug=$debug;

		// Create a connection
		$this->connect();
	}
	public function __destruct()
	{
		// Close the connection if connected
		if($this->connected())
			$this->disconnect();
	}

	// Basic connection functions
	public function connected()
	{
		return ($this->connection==null)?false:true;
	}
	public function connect()
	{
		if($this->getErrorLevel()>2)
			return false;

		if(!$this->connected())
		{
			if(@($this->debug==1))
			{
				echo "Dbg: Attempting database connection...";
			}
			$dsn=$this->configs['type'].':host='.$this->configs['host'].';dbname='.$this->configs['database'];// Construct connection string from $this->configs

			// Attempt to create a PDO database connection
			try
			{
				$this->connection=new PDO($dsn,$this->configs['username'],$this->configs['password'], array(PDO::ATTR_PERSISTENT => true));
				if(@($this->debug==1))
				{
					echo "Success!\n";
				}
			}
			catch(Exception $e)
			{
				if(@($this->debug==1))
				{
					echo "Error: ".$e->getMessage()."\n";
					print_r($this->configs);
				}
			}
			finally
			{
				if($this->connection==null)
					$this->sdbSetErrorLevel($this->errorLevel+1);
			}
		}
	}
	public function disconnect()
	{
		if(@($this->debug==1))
		{
			echo "Dbg: Disconnecting from database.\n";
		}

		// Close $this->connection
		if(!$this->connected())
			return true;
		else
			return ($this->connection=null);
	}
	public function openTable($name, $primaryKey="")
	{
		if(@($this->debug==1))
		{
			echo "Dbg: Opening table $name.\n";
		}

		// Create a new table object in the current DBO
		if(!(isset($this->tables[$name])))
			$this->tables[$name]=new SDBTable($this->connection,$name,$this->configs,$primaryKey);

		return $this->tables[$name]; // Return to avoid having to use sdbGetTable
	}
	public function closeTable($name)
	{
		if(isset($this->tables[$name]))
			unset($this->tables[$name]);
		return true;
	}
	public function rawQry($query,$params=array(),$save=true)
	{
		if($this->connected())
			$this->connect();
		if($this->sdbGetErrorLevel())
			return false;

		if($save)
		{
			if(!isset($this->rows))
				$this->rows=array();
			$x=count($this->rows);
		}

		$stmt=$this->connection->prepare($query);
		$stmt->execute($params);
		$rows=$stmt->fetchAll();

		if($save)
		{
			$x=0;
			foreach($rows as $row)
			{
				$index=(($this->primaryKey=="")?$x++:$this->primaryKey);
				$this->rows[$index]=new SDBRes($query);
				$this->rows[$index]->Values=$row;
			}
		}
		return ($save) ? count($this->rows) : $rows;
	}

	// Getters
	public function sdbGetTable($name)
	{
		if(isset($this->tables[$name]))
			return $this->tables[$name];
		else
			return false;
	}
	public function sdbGetConfigs()
	{
		return $this->curConfigs;
	}
	public function sdbGetErrorLevel()
	{
		return $this->errorLevel;
	}
	public function sdbGetDatabases()
	{
		$databases=array();

		$res=$this->rawQry($this->compatQrys['SHOW DATABASES'][$this->configs['type']], array(':like' => $like), false);
		foreach($res as $db)
			$databases[]=$db;

		return $databases;
	}
	public function sdbGetTables($database=null, $like='%')
	{
		// SHOW TABLES IN $database LIKE '$like[0]'
		$database=(!$database) ? $this->configs['database'] : $database;
		$tables=array();
		$res=$this->rawQry(str_replace('{DATABASE}',((!$database)?$this->configs['database']:$database),$this->compatQrys['SHOW TABLES'][$this->configs['type']]),array(':like' => $like),false);
		foreach($res as $table)
			$tables[]=$table[0];

		return $tables;
	}
	public function sdbGetTblCreate($table, $database=null)
	{
		// SHOW CREATE TABLE 
		$res=$this->rawQry(str_replace('{DATABASE}',($database)?$database:$this->sdbGetConfigs()['database'],str_replace('{TABLE}',$table,$this->compatQrys['SHOW CREATE TABLE'][$this->configs['type']])),array(),false);
		return $res[0][1];
	}
	public function sdbGetColumns($table="",$database=null,$like='%')
	{
		// SHOW COLUMNS IN $table WHERE column_name LIKE $like[1] OR column_name LIKE $like[2]...
		// orrr $tbl=$this->openTable($table);$tbl->select('*');getColumns
		$columns=array();
		$res=$this->rawQry(str_replace('{DATABASE}',($database)?$database:$this->sdbGetConfigs()['database'],str_replace('{TABLE}',$table,$this->compatQrys['SHOW COLUMNS'][$this->configs['type']])),array(':like' => $like),false);
		foreach($res as $column)
			$columns[$column['Field']] = $column;
		return $columns;
	}
	public function sdbGetRows()
	{
		return (count($this->Values)) ? $this->Values : false;
	}
	public function resetRows()
	{
		$this->Values=array();
	}

	// Setters
	public function sdbSetConfigs($configs)
	{
		$this->configs=$configs;
		$this->disconnect();
		$this->connect();
	}
	public function sdbSetErrorLevel($errorLevel)
	{
		$this->errorLevel=$errorLevel;
	}

	// Input filtering
	public function quote($string,$extraFilter=null)
	{
		if($extraFilter)
		{
			$string=$extraFilter($string);
		}
		return $this->connection->quote($string);
	}
}
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
	public function where($conditions=array())
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
					$query.=" `$col` ".$arr['op']." :$col";
					$params[":$col"]=$arr['val'];
					if(++$x<count($conditions[$k]))
						$query.=" $k";
				}
			}
		}
		return array($query,$params);
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
			foreach($cols as $col)
			{
				if($col!='*')
					$col="`$col`";
				$query.=" $col".((++$x<count($cols))?',':'');
			}
		}
		else
			$query.=" $cols";
		$query.=' FROM `'.$tblPrefix.$this->sdbGetName().'`';

		// $extra['JTYPE']="LEFT";
		// $extra['JOIN']=array('userid'=>array('uid'='id'));
		if(isset($extra['JOIN']))
		{
			if(!(isset($extra['Table'])))
			{
				foreach($extra['JOIN'] as $k=>$v)
				{
					if(isset($extra['JTYPE']))
						$query.=" ${extra['JTYPE']}";
					$query.=" JOIN `${tblPrefix}${k}` ON `$tblPrefix".$this->sdbGetName().'`.';
					foreach($v as $sk=>$sv)
						$query.="`$sk`=`${tblPrefix}${k}`.`$sv`";
				}
			}
		}

		// Get conditional statement
		$where=$this->where($conditions);
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
?>
