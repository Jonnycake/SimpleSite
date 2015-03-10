<?php
abstract class SimpleUser implements simpleUserI
{
	public $name="";
	public $is_admin=false;
	public $is_logged_in=false;

	protected $uid=null;
	protected $userInfo=null;

	private $privileges=null;

	public function __construct($username, $password)
	{
		if($this->login($username, $password))
		{
			$this->is_logged_in=true;
			$this->uid=$this->userInfo->getId();
			$this->is_admin=$this->userInfo->getIs_admin();
		}
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
