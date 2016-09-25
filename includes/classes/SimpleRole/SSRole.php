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
	 *
	 *
	 *
	 *
	 *
	 */
	public $description=null;

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
	 *
	 *
	 *
	 *
	 */
	public static function getByID($id, $dbconf)
	{
		$db=new SimpleDB($dbconf);
		$tbl=$db->openTable("roles");
		$tbl->select('name', array('AND'=>array('id'=>array('op'=>'=', 'val'=>$id))));
		$rows=$tbl->sdbGetRows();
		if(isset($rows[0])) {
			$row=$rows[0];
			return new SSRole($row->getName(), $dbconf);
		} else {
			return false;
		}
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
		if($this->isInstalled()) {
			if(is_null($this->privs) || $force_reload) {
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
				$tbl->select(array('privileges'=>array('name'), 'roles'=>array('id', 'description', 'is_admin')), array('AND'=>array('name'=>array('tbl'=>'roles', 'op'=>'=', 'val'=>$this->name))), array('JOIN'=>$relationships, 'JTYPE'=>'LEFT'));
				$res=$tbl->sdbGetRows();
				$firstRow=$res[0];
				$db->disconnect();

				if(is_object($firstRow)) {
					$this->id=$firstRow->getId();
					$this->description=$firstRow->getDescription();

					$is_admin=false;

					$privs=array();
					foreach(@$res as $row) {
						$privs[]=$row->getName();

						if($row->getIs_admin()) {
							$this->is_admin=true;
						}
					}
					$this->privs=$privs;
				} else {
					$privs=array();
				}
			} else {
				$privs=$this->privs;
			}
		} else {
			$this->install();
			if($this->isInstalled()) {
				return $this->getPrivileges();
			}
		}
		return $privs;
	}

	/**
	 * Creates/updates a role
	 *
	 * @return void
	 */
	public function save($new=false)
	{
		// Name description
		// Privileges
	}

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function getID($force_reload=false)
	{
		if(is_null($this->id) || $force_reload) {
			$db=new SimpleDB($this->dbconf);
			$tbl=$db->openTable("roles");
			$count=$tbl->select('*', array('AND'=>array('name'=>array('op'=>'=', 'val'=>$this->name))));
			if($count) {
				$rows=$tbl->sdbGetRows();
				$row=$rows[0];
				$this->id=$row->getId();
			} else {
				return null;
			}
		}
		return $this->id;
	}

	/**
	 * Deletes the currently loaded role
	 *
	 * @return void
	 */
	public function delete()
	{
		// Name description
		$db = SimpleDB::getConnection("Main");
		$tbl = $db->openTable("roles");
		$tbl->delete(array('AND'=>array('id' => $this->getID(true))));

		// Privileges
	}

	/**
	 * Checks if SSRole is set up correctly
	 *
	 * @return bool Whether or not SSRole will work.
	 */
	public function isInstalled($dbconf=null)
	{
		/* Tables:
		 * -------
		 * roles
		 * privileges
		 * role_privs
		 */
		$defaultTbls=array(
					"roles",
					"privileges",
					"role_privs"
				);
		return SimpleUtils::checkReqTbls($defaultTbls, array("database" => ((is_null($dbconf))?$this->dbconf:$dbconf)));
	}

	/**
	 * Install the role system (and return true if success or false if not)
	 *
	 * @return bool Whether or not the role system could be installed
	 */
	public function install($dbconf=null)
	{
		$tables=array(
		 /* roles
		 * `id` int(11) NOT NULL AUTO_INCREMENT,
		 * `name` varchar(50) NOT NULL,
		 * `description` varchar(250) NOT NULL,
		 * `is_admin` tinyint(4) NOT NULL DEFAULT '0',
		 * PRIMARY KEY (`id`),
		 * UNIQUE KEY `name` (`name`)
		 *
		 * ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 */

				"roles"      => array(
							"id"          => "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY",
							"name"        => "varchar(50) NOT NULL UNIQUE KEY",
							"description" => "varchar(250) NOT NULL",
							"is_admin"    => "tinyint(4) NOT NULL DEFAULT '0'"	
						),
		 /* privileges
		 * `id` int(11) NOT NULL AUTO_INCREMENT,
		 * `name` varchar(50) NOT NULL,
		 * `description` varchar(250) NOT NULL,
		 * PRIMARY KEY (`id`),\n  UNIQUE KEY `name` (`name`)
		 *
		 * ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 */
				"privileges" => array(
							"id"          => "int NOT NULL AUTO_INCREMENT PRIMARY KEY",
							"name"        => "varchar(50) NOT NULL UNIQUE KEY",
							"description" =>"varchar(250) NOT NULL"
						),
		 /* role_privs
		 * `role_id` int(11) NOT NULL,
		 * `priv_id` int(11) NOT NULL,
		 * PRIMARY KEY (`role_id`,`priv_id`) */
				"role_privs" => array(
							"role_id" => "int NOT NULL",
							"priv_id" => "int NOT NULL"
						)
			);

		return SimpleUtils::installReqTbls($tables, array("database" => ((is_null($dbconf))?$this->dbconf:$dbconf)));
	}
}
