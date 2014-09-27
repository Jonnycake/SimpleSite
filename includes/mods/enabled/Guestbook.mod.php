<?php
if(SIMPLESITE!=1)
	die("Can't access this file directly.");
class Guestbook extends SimpleModule
{
	public static $info=array(  "author"  => "Jon Stockton",
				    "name"=> "Guestbook",
				    "version" => "0.1",
				    "date"=> "??? ??, 2014"
	);
	public function choosePage()
	{
		return "";
	}
	public function sideparse($content,$configs=array())
	{
		if(@(($_POST['name']!="") && ($_POST['message']!="")))
		{
			if(($GLOBALS['funcsperformed'])==0)
			{
				$GLOBALS['funcsperformed']++;
				$this->db->openTable("Guestbook");
				$table=$this->db->sdbGetTable("Guestbook");
				$values=array("name"=>$this->simpleFilter($_POST['name'], false),"message"=>$this->simpleFilter($_POST['message'], false));
				$table->insert($values);
			}
			else
				SimpleDebug::logInfo("Already performed function...skipping.");
		}
		if((preg_match("/{ENTRIES}/si",$content,$match)))
		{
			$guestbook="";
			$cols=array("name","message");
			$this->db->openTable("Guestbook");
			$table=$this->db->sdbGetTable("Guestbook");
			$table->select($cols);
			$dataArr=array();
			foreach($table->sdbGetRows() as $row)
				$dataArr[]=$row->sdbGetValues();
			$guestbook=$this->arr2Feed($configs['path']['mod_templates']."/Guestbook_ENTRIES.template",$dataArr,$configs,true);
		}
		return str_replace("{ENTRIES}",$guestbook,$content);
	}
	public function isInstalled($configs=array())
	{
		if(!$this->checkReqTbls(array("Guestbook"), $configs))
			return false;
		return $this->checkReqFiles(
						array(
							$_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/Guestbook.template",
							$_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/Guestbook_ENTRIES.template"
						),
						$configs
   					);
	}
	public function install($configs=array())
	{
		$defaultTbls=array("Guestbook" => array("id" => "int NOT NULL AUTO_INCREMENT PRIMARY KEY", "name" => "varchar(50) NOT NULL", "message" => "varchar(250) NOT NULL"));
		$this->installReqTbls($defaultTbls,$configs);
		$defaultFiles=array(
			"Guestbook.template" => "PGZvcm0gYWN0aW9uPSJ7Q09ORklHU19wYXRoX3Jvb3R9P21vZD1HdWVzdGJvb2siIG1ldGhvZD0icG9zdCI+DQoJPGxhYmVsIGZvcj0ibmFtZSI+TmFtZTo8L2xhYmVsPjxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJuYW1lIi8+PGJyLz4NCgk8bGFiZWwgZm9yPSJtZXNzYWdlIj5NZXNzYWdlOiA8L2xhYmVsPjxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJtZXNzYWdlIi8+PGJyLz4NCgk8aW5wdXQgdHlwZT0ic3VibWl0IiB2YWx1ZT0iUG9zdCIvPg0KPC9mb3JtPjxici8+DQp7RU5UUklFU30=",
			"Guestbook_ENTRIES.template" => "PHNwYW4+e0RhdGFBcnJfbmFtZX06IHtEYXRhQXJyX21lc3NhZ2V9PC9zcGFuPjxici8+"
		);
		$this->installReqFiles($defaultFiles,$configs);
		return TRUE;
	}
	public function uninstall($configs=array())
	{
		return FALSE;
	}
	public function getContent($configs=array())
	{
		return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/Guestbook.template","Guestbook");
	}
}
?>
