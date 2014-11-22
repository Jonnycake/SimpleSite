<?php
class SSRole extends SimpleRole
{
	public $is_admin=false;
	public $name="";
	public $dbconf=array();

	public function __construct($name, $dbconf=array())
	{
		$this->name=$name;
		$this->dbconf=$dbconf;
		parent::__construct($name);
	}
	public function getPrivileges()
	{
		$dbconf=$this->dbconf;
		$db=new SimpleDB($dbconf);
		$query="select ${dbconf['tbl_prefix']}roles.name, ${dbconf['tbl_prefix']}roles.is_admin, ${dbconf['tbl_prefix']}privileges.name as priv from ${dbconf['tbl_prefix']}roles left join ${dbconf['tbl_prefix']}role_privs on role_id=${dbconf['tbl_prefix']}roles.id left join ${dbconf['tbl_prefix']}privileges on priv_id=${dbconf['tbl_prefix']}privileges.id WHERE ${dbconf['tbl_prefix']}roles.name='".$this->name."';";
		$res=$db->rawQry($query, array(), false);
		$db->disconnect();
		$is_admin=false;

		$privs=array();
		foreach($res as $row)
		{
			$privs[]=$row['priv'];
			if($row['is_admin'])
			{
				$this->is_admin=true;
			}
		}
		return $privs;
	}
}
?>
