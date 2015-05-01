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
	private static $roleClass="SSRole";

	public $username="";
	public $password="";
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
	public function __construct($username, $password, $require_password=true)
	{
		if(!$this->isInstalled())
		{
			$this->install();
		}
		if($this->_login($username, $password, $require_password))
		{
			$this->is_logged_in=true;
			$this->uid=$this->userInfo->getId();
			$this->is_admin=$this->userInfo->getIs_admin();
			$this->username=$username;
			$this->password=$password;
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

	public static function getRoleClass()
	{
		return SimpleUser::$roleClass;
	}

	public static function setRoleClass($className=null)
	{
		SimpleUser::$roleClass=(is_null($className))?"SSRole":$className;
	}


	/**
	 *
	 *
	 *
	 *
	 */
	abstract public function save($new=false);

	/**
	 *
	 *
	 *
	 *
	 */
	abstract public function delete();

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

	/**
	 * Should return if the user system is properly installed
	 *
	 * @return bool Whether or not the user system is installed
	 */
	abstract public function isInstalled();

	/**
	 * Should install the user system (and return true if success or false if not)
	 *
	 * @return bool Whether or not the user system could be installed
	 */
	abstract public function install();

	public function hashPassword($password=null)
	{
		return (is_null($password))?md5($this->password):md5($password);
	}
}
?>
