<?php
/**
 *
 */

/**
 *
 *
 *
 *
 *
 */
class SSUser extends SimpleUser
{
	/**
	 *
	 *
	 *
	 *
	 *
	 */
	private $dbconf=null;

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public $roles=array();

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function __construct($username, $password, $dbconf=array())
	{
		$this->dbconf=$dbconf;
		parent::__construct($username, $password);
	}

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function login($username, $password)
	{
		$db=new SimpleDB($this->dbconf);
		$userTbl=$db->openTable("users");
		$userTbl->select('*', array(
			'AND'=>array(
					'username'=>array(
						'op'=>'=', 
						'val'=>$username
					),
					'password'=>array(
						'op'=>'=',
						'val'=>md5($password)
					)
				)
			)
		);
		$rows=$userTbl->sdbGetRows();
		if(isset($rows[0])) {
			$this->userInfo=$rows[0];
			$this->roles=$this->getRoles();
			return $this->userInfo;
		} else {
			return false;
		}
	}

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function logout()
	{
		$this->is_logged_in=false;
	}

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function getRoles()
	{
		$db=new SimpleDB($this->dbconf);
		$tbl=$db->openTable("user_roles");
		$tbl->select('SS_roles.name', array('AND'=>array('uid'=>array('op'=>'=','val'=>$this->userInfo->getId()))), array('JOIN'=>array('roles'=>array('rid'=>'id'))));
		$roles=array();
		$rows=$tbl->sdbGetRows();
		foreach($rows as $row) {
			$roles[]=$row->getName();
		}
		return array_unique($roles);
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
		foreach($this->roles as $role) {
			$roleObj=new SSRole($role, $this->dbconf);
			if($roleObj->hasPrivilege($privilege)) {
				return true;
			}
		}
		return false;
	}
}
?>
