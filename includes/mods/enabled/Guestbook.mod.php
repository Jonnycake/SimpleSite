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
				$values=array("name"=>$_POST['name'],"message"=>$_POST['message']);
				$table->insert($values);
			}
			else if($_GET['debug']==1)
				echo "Dbg: Already performed function...skipping.\n";
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
		$defaultFiles=array("Guestbook.template" => "PGZvcm0gYWN0aW9uPSJ7Q09ORklHU19wYXRoX3Jvb3R9P21vZD1HdWVzdGJvb2siIG1ldGhvZD0icG9zdCI+Cgk8bGFiZWwgZm9yPSJuYW1lIj5OYW1lOjwvbGFiZWw+PGlucHV0IHR5cGU9InRleHQiIG5hbWU9Im5hbWUiLz48YnIvPgoJPGxhYmVsIGZvcj0ibWVzc2FnZSI+TWVzc2FnZTogPC9sYWJlbD48aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ibWVzc2FnZSIvPjxici8+Cgk8aW5wdXQgdHlwZT0ic3VibWl0IiB2YWx1ZT0iUG9zdCIvPgo8L2Zvcm0+PGJyLz4Ke0VOVFJJRVN9");
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
