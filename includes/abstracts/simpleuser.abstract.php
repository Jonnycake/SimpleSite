<?php
/**
 *
 */

/**
 *
 *
 *
 *
 */
abstract class SimpleUser implements simpleUserI
{
	/**
	 *
	 *
	 *
	 *
	 */
	public $name="";

	/**
	 *
	 *
	 *
	 *
	 */
	public $is_admin=false;

	/**
	 *
	 *
	 *
	 *
	 */
	public $is_logged_in=false;

	/**
	 *
	 *
	 *
	 *
	 */
	protected $uid=null;

	/**
	 *
	 *
	 *
	 *
	 */
	public $userInfo=null;

	/**
	 *
	 *
	 *
	 *
	 */
	public function __construct($username, $password)
	{
		if($this->_login($username, $password))
		{
			$this->is_logged_in=true;
			$this->uid=$this->userInfo->getId();
			$this->is_admin=$this->userInfo->getIs_admin();
		}
		$this->getRoles();
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function isAdmin()
	{
		return $this->is_admin;
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function isGuest()
	{
		return !($this->is_logged_in);
	}

	/**
	 *
	 *
	 *
	 *
	 */
	abstract public function login($username, $password);

	/**
	 *
	 *
	 *
	 *
	 */
	public function logout()
	{
		$this->is_logged_in=false;
		$this->uid=-1;
		$this->is_admin=false;
		$this->name=null;
		$this->userInfo=null;
		$this->roles=null;
	}

	/**
	 *
	 *
	 *
	 *
	 */
	abstract public function getRoles();

	/**
	 *
	 *
	 *
	 *
	 */
	abstract public function hasPrivilege($privilege);
}
?>
