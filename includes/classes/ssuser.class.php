<?php
/**
 * Default SimpleUser implementation
 *
 * @package SimpleUser
 * @author Jonathan Stockton <jonathan@simplesite.ddns.net>
 */

/**
 * SSUser class which implements the functions required by SimpleUser abstract
 */
class SSUser extends SimpleUser
{
	private $attributes=null;
	/**
	 * Role class to use for user permissions
	 *
	 * @var array
	 */
	public static $roleClass="SSRole";

	/**
	 * Database configuration array
	 *
	 * @var array
	 */
	private $dbconf=null;

	/**
	 * Array of roles that the user has
	 *
	 * @var array
	 */
	public $roles=array();

	/**
	 *  Constructor for the SSUser object
	 *
	 * @param string $username The username of the user to log in
	 * @param string $password The password to use for authentication
	 * @param array $dbconf The database configurations to use
	 *
	 * @return void
	 */
	public function __construct($username=null, $password=null, $dbconf=array(), $require_password=true)
	{
		$this->dbconf=$dbconf;
		if(!is_null($username)) {
			parent::__construct($username, $password, $require_password);
		}
	}

	public static function getByID($id, $dbconf)
	{
		$db=new SimpleDB($dbconf);
		$tbl=$db->openTable("users");
		if($tbl->select('username', array('AND'=>array('id'=>array('op'=>'=', 'val'=>$id))))) {
			$user=$tbl->sdbGetRows();
			return new self($user->getUsername(), null, $dbconf, false);
		} else {
			return false;
		}
	}

	/**
	 * Login function
	 *
	 * It is called from parent::__construct()
	 *
	 * @param string $username The username of the user to log in
	 * @param string $password The password to use for authentication
	 *
	 * @return array|false Returns the userInfo array if the login is correct and false otherwise
	 */
	protected function _login($username, $password, $require_password=true)
	{
		$db=new SimpleDB($this->dbconf);
		$userTbl=$db->openTable("users");
		$conditions=array(
			'AND'=>array(
					'username'=>array(
						'op'=>'=', 
						'val'=>$username
					)
				)
			);
		if($require_password) {
			$conditions['AND']['password']=array('op'=>'=', 'val'=>$this->hashPassword($password));
		}


		$userTbl->select('*', $conditions);
		$rows=$userTbl->sdbGetRows();
		if(isset($rows[0])) {
			$this->userInfo=$rows[0];
			$this->roles=$this->getRoles();
			return $this->userInfo;
		} else {
			$this->userInfo=null;
			return false;
		}
	}

	/**
	 * Login function
	 *
	 * Meant to be called from the user object, acts as a facade for parent::__construct()
	 *
	 * @param string $username The username of the user to login
	 * @param string $password The password to use for authentication
	 *
	 * @return void
	 */
	public function login($username, $password)
	{
		parent::__construct($username, $password);
	}

	/**
	 * Logout the user
	 *
	 * @return void
	 */
	public function logout()
	{
		$this->is_logged_in=false;
	}

	/**
	 * Retrieve the roles assigned to the current user
	 *
	 * @return array The array of roles
	 */
	public function getRoles()
	{
		$dbconf=$this->dbconf;
		$db=new SimpleDB($dbconf);
		$tbl=$db->openTable("user_roles");
		$roles=array();
		if(!is_null($this->userInfo)) {
			$tbl->select('*', array('AND'=>array('uid'=>array('op'=>'=','val'=>$this->userInfo->getId()))));
			$rows=$tbl->sdbGetRows();
			$roleClass=self::$roleClass;
			foreach($rows as $row) {
				$role=$roleClass::getByID($row->getRid(), $dbconf);
				if(is_object($role)) {
					$roles[]=$role->getName();
				}
			}
		}
		return array_unique($roles);
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function getInfo($attribute=null, $force_reload=false)
	{
		if($force_reload || is_null($this->attributes)) {
			$db=new SimpleDB($this->dbconf);
			$tbl=$db->openTable("user_info");
			$attributes=array();
			if(!is_null($this->userInfo)) {
				if(!is_null($attribute)) {
					$tbl->select('*', array('AND'=>array('uid'=>array('op'=>'=','val'=>$this->userInfo->getId()))), array('JOIN'=>array('user_attributes'=>array('aid'=>'id'))));
				} else {
					$tbl->select('*', array('AND'=>array('uid'=>array('op'=>'=','val'=>$this->userInfo->getId()))), array('JOIN'=>array('user_attributes'=>array('aid'=>'id'))));
				}
				$rows=$tbl->sdbGetRows();
				foreach($rows as $row) {
					$attributes[$row->getName()]=$row->getValue();
				}
			}
			$this->attributes=$attributes;
		} else {
			$attributes=$this->attributes;
		}
		return $attributes;
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function setInfo($attribute, $value=null)
	{
		$this->attributes[$attribute]=$value;
	}

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function hasPrivilege($privilege)
	{
		$roleClass=self::$roleClass;
		foreach($this->roles as $role) {
			$roleObj=new $roleClass($role, $this->dbconf);
			if($roleObj->hasPrivilege($privilege)) {
				return true;
			}
		}
		return false;
	}

	public function addRole($role)
	{
		if(is_string($role)) {
			$this->roles[]=$role;
		} else if(is_array($role)) {
			$this->roles=array_merge($this->roles, $role);
		}
	}

	public function removeRole($role)
	{
		if(is_string($role)) {
			$this->roles=array_diff($this->roles, array($role));
		} else if(is_array($role)) {
			$this->roles=array_diff($this->roles, $role);
		}
	}
	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function isAdmin()
	{
		$roleClass=self::$roleClass;
		foreach($this->roles as $role) {
			$roleObj=new $roleClass($role, $this->dbconf);
			if($roleObj->is_admin){
				return true;
			}
		}
	}

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function isGuest()
	{
		return !$this->is_logged_in;
	}

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function save($new=false)
	{
		$db=new SimpleDB($this->dbconf);
/*
		$tables=array(
				"users"      => array(
							"id"       => "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY",
							"username" => "varchar(50) NOT NULL UNIQUE",
							"password" => "varchar(32) NOT NULL",
							"is_admin" => "tinyint(4) NOT NULL"
				                ),
				"user_roles" => array(
							"id"       => "int NOT NULL AUTO_INCREMENT PRIMARY KEY",
							"uid"      => "int NOT NULL",
							"rid"      => "int NOT NULL"
				                ),
				"user_attributes" => array(
							"id"       => "int NOT NULL AUTO_INCREMENT PRIMARY KEY",
							"name"     => "varchar(50) UNIQUE"
				                     ),
				"user_info"       => array(
							"uid"      => "int NOT NULL",
							"aid"      => "int NOT NULL",
							"value"    => "varchar(255) NOT NULL"
				                     )
		);
*/
		// Username, password
		$tbl=$db->openTable("users");
		if($new) {
			// Check for presence of username before doing insert
			if($tbl->select('*',array('AND'=>array('username'=>array('op'=>'=', 'val'=>$this->username))))===0) {
				$tbl->insert(array( "username"=>$this->username, "password"=>$this->hashPassword($this->password), "is_admin" => 0));
				$preservedRoles=$this->roles;
				$preservedAttributes=$this->attributes;
				$this->login($this->username, $this->password);
				$userId=$this->userInfo->getId();
			} else {
				return false;
			}
		} else {
			if(is_object($this->userInfo)) {
				$userId=$this->userInfo->getId();
				$tbl->update(array( "username"=>$this->username, "password" => $this->hashPassword($this->password), "is_admin" => $this->is_admin ), array("AND"=>array("id"=>array('op'=>'=', 'val' =>$this->userInfo->getId()))));
			} else {
				return false;
			}
		}

		// User roles
		$tbl=$db->openTable("user_roles");
		$roleClass=self::$roleClass;
		if($new) {
			// Every user gets Authenticated User role
			$newUserRoles=array_merge($this->roles, $preservedRoles);
			$newUserRoles[]="Authenticated User";
			$newUserRoles=array_unique($newUserRoles);

			foreach($newUserRoles as $role) {
				$role=new $roleClass($role, $this->dbconf);
				if(!is_null($role->getID())) {
					$tbl->insert(array('uid'=>$userId, 'rid'=>$role->getID()));
				}
			}
		} else {
			$curRoles=$this->getRoles();
			$newRoles=array_unique($this->roles);
			$removedRoles=array_diff($curRoles, $newRoles);
			foreach($removedRoles as $roleName) {
				$role=new $roleClass($roleName, $this->dbconf);
				if(!is_null($role->getID())) {
					$tbl->delete(array('AND'=>array('uid'=>array('op'=>'=', 'val'=>$userId), 'rid'=>array('op'=>'=', 'val'=>$role->getID()))));
				}
			}

			$addedRoles=array_diff($newRoles, $curRoles);
			foreach($addedRoles as $roleName) {
				$role=new $roleClass($roleName, $this->dbconf);
				if(!is_null($role->getID())) {
					$tbl->insert(array('uid'=>$userId, 'rid'=>$role->getID()));
				}
			}
		}

		// User attributes
		$attributeNames=array();
		$attribTbl=$db->openTable("user_attributes");

		// Get a list of all possible attributes
		$attribTbl->select('*');
		$possibleAttributes=$attribTbl->sdbGetRows();
		foreach($possibleAttributes as $attribute) {
			$attributeNames[$attribute->getName()]=$attribute;
		}

		$attribInDB=array();
		$tbl=$db->openTable("user_info");

		// Check to see what attributes are already in the database
		$tbl->select('aid', array('AND'=>array('uid'=>array('op'=>'=', 'val'=>$userId))));
		$attribRows=$tbl->sdbGetRows();
		foreach($attribRows as $row) {
			$attribInDB[]=$row->getAid();
		}

		// Actually do the inserts/updates
		foreach($attributeNames as $name=>$attribute) {
			if(!in_array($attribute->getId(), $attribInDB)) {
				$tbl->insert(array('uid'=>$userId, 'aid'=>$attribute->getId(), 'value'=>((isset($this->attributes[$name]))?$this->attributes[$name]:'')));
			} else if(isset($this->attributes[$name])) {
				$tbl->update(array('value'=>$this->attributes[$name]), array('AND'=>array('uid'=>array('op'=>'=','val'=>$userId), 'aid'=>array('op'=>'=', 'val'=>$attribute->getId()))));
			}
		}

		return true;
	}

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function delete($archive=false)
	{
		// Archiving may require added field
		$db=new SimpleDB($this->dbconf);


		// User roles
		$tbl=$db->openTable("user_roles");
		$tbl->delete(array("AND" => array("uid"=>array("op"=>'=', "val"=>$this->userInfo->getId()))));

		// User attributes
		$tbl=$db->openTable("user_info");
		$tbl->delete(array("AND" => array("uid"=>array("op"=>'=', "val"=>$this->userInfo->getId()))));

		// Usrname, password
		$tbl=$db->openTable("users");
		$tbl->delete(array("AND" => array("id"=>array("op"=>'=', "val"=>$this->userInfo->getId()))));
	}

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function isInstalled()
	{
		$roleClass=self::$roleClass;
		$tables=array( "users", "user_roles", "user_attributes", "user_info" );
		return ($roleClass::isInstalled($this->dbconf) && SimpleUtils::checkReqTbls($tables, array("database"=>$this->dbconf)));
	}

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function install()
	{
		$tables=array(
				"users"      => array(
							"id"       => "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY",
							"username" => "varchar(50) NOT NULL UNIQUE",
							"password" => "varchar(32) NOT NULL",
							"is_admin" => "tinyint(4) NOT NULL"
				                ),
				"user_roles" => array(
							"id"       => "int NOT NULL AUTO_INCREMENT PRIMARY KEY",
							"uid"      => "int NOT NULL",
							"rid"      => "int NOT NULL"
				                ),
				"user_attributes" => array(
							"id"       => "int NOT NULL AUTO_INCREMENT PRIMARY KEY",
							"name"     => "varchar(50) UNIQUE"
				                     ),
				"user_info"       => array(
							"uid"      => "int NOT NULL",
							"aid"      => "int NOT NULL",
							"value"    => "varchar(255) NOT NULL"
				                     )
		);
		$roleClass=self::$roleClass;
		return ($roleClass::install($this->dbconf) && SimpleUtils::installReqTbls($tables, array("database"=>$this->dbconf)));
	}
}
?>
