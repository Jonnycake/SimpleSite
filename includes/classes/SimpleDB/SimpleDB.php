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
		try
		{
			$this->connect();
		}
		catch(Exception $e)
		{
			SimpleDebug::logException($e);
		}
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
		if($this->sdbGetErrorLevel()>2)
			return false;

		if(!$this->connected())
		{
			$dsn=$this->configs['type'].':host='.$this->configs['host'];// Construct connection string from $this->configs

			// Attempt to create a PDO database connection
			try
			{
				$this->connection=new PDO($dsn,$this->configs['username'],$this->configs['password'], array(PDO::ATTR_PERSISTENT => true));
				$this->connection->query("USE `"  . $this->configs['database'] . "`;");
			}
			catch(Exception $e)
			{
				$this->sdbSetErrorLevel($this->errorLevel+1);
				if(@($this->debug==1))
				{
					throw new Exception("Database Connection Error: ".$e->getMessage());
				}
			}
			//if($this->connection==null)
		}
	}
	public function disconnect()
	{
		// Close $this->connection
		if(!$this->connected())
			return true;
		else
			return ($this->connection=null);
	}
	public function openTable($name, $primaryKey="")
	{
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
				$index=((isset($this->primaryKey))?(($this->primaryKey=="")?$x++:$this->primaryKey):$x++);
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
		$dbconf=$this->sdbGetConfigs();
		$res=$this->rawQry(str_replace('{DATABASE}',($database)?$database:$dbconf['database'],str_replace('{TABLE}',$table,$this->compatQrys['SHOW CREATE TABLE'][$this->configs['type']])),array(),false);
		return $res[0][1];
	}
	public function sdbGetColumns($table="",$database=null,$like='%')
	{
		// SHOW COLUMNS IN $table WHERE column_name LIKE $like[1] OR column_name LIKE $like[2]...
		// orrr $tbl=$this->openTable($table);$tbl->select('*');getColumns
		$columns=array();
		$dbconf=$this->sdbGetConfigs();
		$res=$this->rawQry(str_replace('{DATABASE}',($database)?$database:$dbconf['database'],str_replace('{TABLE}',$table,$this->compatQrys['SHOW COLUMNS'][$this->configs['type']])),array(':like' => $like),false);
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
