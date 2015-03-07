<?php
abstract class SimpleRole implements simpleRoleI
{
	public $is_admin=false;
	private $privileges=array();
	private $name="";

	public function __construct($name="Guest", $admin=false)
	{
		$this->is_admin=$admin;
		$this->name=$name;
		$this->privileges=$this->getPrivileges();
	}

	public function hasPrivilege($privilege)
	{
		var_dump($this->isAdmin());
		return (in_array($privilege, $this->privileges) || $this->isAdmin());
	}

	public function isAdmin()
	{
		return $this->is_admin;
	}

	public function getName()
	{
		return $this->name;
	}
	abstract public function getPrivileges();
	abstract public function isInstalled();
}
?>
