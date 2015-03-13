<?php
/**
 * Default SimpleRole implementation.
 *
 * @package SimpleRole
 * @author Jonathan Stockton <jonathan@simplesite.ddns.net>
 */

/**
 * SSRole class which implements abstract functions required by SimpleRole
 */
class SSRole extends SimpleRole
{
	/**
	 * Whether or not the role should be defined as an admin (permissions must be specifically denied)
	 *
	 * @var bool Whether or not the role is an administrator role (able to do anything)
	 */
	public $is_admin=false;

	/**
	 * Database configurations (generally taken from $configs['database'] defined in config.inc.php)
	 *
	 * @var array The same as the array defined in config.inc.php as $configs['database']
	 */
	public $dbconf=array();

	/**
	 * Privileges associated with the role
	 *
	 * @var null|array Array of strings (or null prior to being set)
	 */
	public $privs=null;

	/**
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

	/**
	 * Retrieves the privileges out of the database (requires SimpleDB)
	 *
	 * Notable Side-effect: sets $this->privs;
	 * @param bool $force_reload Whether or not to reload the privileges if the array is already populated
	 * @return array List of privilege names
	 */
	public function getPrivileges($force_reload=false)
	{
		if(is_null($this->privs) || $force_reload)
		{
			$dbconf=$this->dbconf;
			$db=new SimpleDB($dbconf);
			$relationships=array( 
						"role_privs" => array(
							"id"=>"role_id",
							"privileges"=>array(
								"priv_id"=>"id",
							)
						)
					);
			$tbl=$db->openTable('roles');
			$tbl->select(array('privileges'=>array('name'), 'roles'=>array('is_admin')), array('AND'=>array('name'=>array('tbl'=>'roles', 'op'=>'=', 'val'=>$this->name))), array('JOIN'=>$relationships, 'JTYPE'=>'LEFT'));
			$res=$tbl->sdbGetRows();

			$db->disconnect();
			$is_admin=false;

			$privs=array();
			foreach($res as $row)
			{
				$privs[]=$row->getName();
				if($row->getIs_admin())
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

	/**
	 * Checks if SSRole is set up correctly
	 *
	 * @return bool Whether or not SSRole will work.
	 */
	public function isInstalled()
	{
		return true;
	}

	/**
	 * Install the role system (and return true if success or false if not)
	 *
	 * @return bool Whether or not the role system could be installed
	 */
	public function install()
	{
		return true;
	}
}
?>
