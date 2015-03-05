<?php
abstract class SimpleUser implements simpleUserI
{
	public $name="";
	public $is_admin=false;
	public $is_logged_in=false;

	public function __construct($username, $password)
	{
		$this->login($username, $password);
		$this->getRoles();
	}
	public function isAdmin()
	{
		return $this->is_admin;
	}
	public function isGuest()
	{
		return !($this->is_logged_in);
	}
	abstract public function login($username, $password);
	abstract public function logout();
	abstract public function getRoles();
}
?>
