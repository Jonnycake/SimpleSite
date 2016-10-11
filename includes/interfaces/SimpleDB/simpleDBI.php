<?php
interface simpleDBI
{
        public function __construct($configs=array("type" => "mysql", "host" => "127.0.0.1", "username" => "root", "password" => "", "database" => "", "tbl_prefix" => ""),$debug=0, $connectionName = "Main");
        public function __destruct();
        // Basic connection functions
        public function connected();
        public function connect($connectionName = "Main");
        public static function getConnection($connectionName);
        public function disconnect();
        public function openTable($name, $primaryKey="");
        public function closeTable($name);
        public function rawQry($query,$params=array(),$save=true);
        public function sdbGetTable($name);
        public function sdbGetConfigs();
        public function sdbGetErrorLevel();
        public function sdbGetDatabases();
        public function sdbGetTables($database=null, $like='%');
        public function sdbGetTblCreate($table, $database=null);
        public function sdbGetColumns($table="",$database=null,$like='%');
        public function sdbGetRows();
        public function resetRows();
        public function sdbSetConfigs($configs);
        public function sdbSetErrorLevel($errorLevel);
        public function quote($string,$extraFilter=null);
}
