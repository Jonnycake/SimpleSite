<?php
class SSUser extends SimpleUser
{
	private $dbconf=null;
	private $userInfo=null;

	public function __construct($username, $password, $dbconf=array())
	{
		$this->dbconf=$dbconf;
		if($this->login($username, $password))
		{
			echo "Welcome ".$this->userInfo->getUsername();
		}
	}
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
			return $this->userInfo;
		} else {
			return false;
		}
	}
	public function logout()
	{
	}
	public function getRoles()
	{
	}
}
?>
