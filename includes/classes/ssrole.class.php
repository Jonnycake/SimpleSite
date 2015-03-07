<?php
/**
 * Default SimpleRole implementation.
 *
 * @package SimpleRole
 * @author Jonathan Stockton <jonathan@simplesite.ddns.net>
 */

/*
 * SSRole class which implements abstract functions required by SimpleRole
 */
class SSRole extends SimpleRole
{
	/*
	 * Whether or not the role should be defined as an admin (permissions must be specifically denied)
	 *
	 * @var bool
	 */
	public $is_admin=false;

	/*
	 * Database configurations (generally taken from $configs['database'] defined in config.inc.php)
	 *
	 * @var array
	 */
	public $dbconf=array();

	/*
	 * Privileges associated with the role
	 *
	 * @var null|array
	 */
	public $privs=null;

	/*
	 * Constructor sets database configurations, the name, and then calls parent::__construct()
	 *
	 * @param string $name The name of the role to be constructed
	 * @param array $dbconf The database configurations to use to connect to the database
	 * @return null
	 */
	public function __construct($name, $dbconf=array())
	{
		$this->name=$name;
		$this->dbconf=$dbconf;
		parent::__construct($name, $this->is_admin);
	}

	/*
	 * Retrieves the privileges out of the database (requires SimpleDB)
	 *
	 * @param bool $force_reload Whether or not to reload the privileges if the array is already populated
	 */
	public function getPrivileges($force_reload=false)
	{
		if(is_null($this->privs) || $force_reload)
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
			$this->privs=$privs;
		}
		else
		{
			$privs=$this->privs;
		}
		return $privs;
	}

	/*
	 * Checks if SSRole is set up correctly
	 *
	 * @return bool Whether or not SSRole will work.
	 */
	public function isInstalled()
	{
		return TRUE;
	}
}
?>
