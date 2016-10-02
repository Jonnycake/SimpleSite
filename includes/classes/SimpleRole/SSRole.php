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
	private $roleRow = null;

	/**
	 * Database configurations (generally taken from $configs['database'] defined in config.inc.php)
	 *
	 * @var array The same as the array defined in config.inc.php as $configs['database']
	 */
	public $dbconf=array();

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
	public function __construct($identifier, $dbconf=array())
	{
		if(!count($dbconf)) {
			$this->db = SimpleDB::getConnection();
		}
		else {
			$this->db=new SimpleDB($dbconf);
		}

		if(is_string($identifier)) {
			$this->name=$identifier;
			$row = self::getByName($identifier, true);
			if($row) {
				$this->description = $row->getDescription();
				$this->id = $row->getId();
			}
		}
		else if(is_int($identifier)) {
			$row = self::getById($identifier, true);
			if($row) {
				$this->id = $row->getId();
				$this->name = $row->getName();
				$this->description = $row->getDescription();
			}
		}
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public static function getByID($id, $row = false)
	{
		$tbl=SimpleDB::getConnection("Main");
		$tbl->select('*', array('AND'=>array('id'=>array('op'=>'=', 'val'=>$id))));
		$rows=$tbl->sdbGetRows();
		if(isset($rows[0])) {
			if($row) {
				return $rows[0];
			}
			else {
				return new SSRole($rows[0]->getName());
			}
		} else {
			return false;
		}
	}

	public static function getByName($name, $row = false)
	{
		$tbl = SimpleDB::getConnection("Main")->openTable("roles");
		$tbl->select('*', array('AND' => array('name' => array('op'=> '=', 'val' => $name))));
		$rows = $tbl->sdbGetRows();
		if(isset($rows[0])) {
			if($row) {
				return $rows[0];
			}
			else {
				return new SSRole($rows[0]->getName());
			}
		}
		else {
			return false;
		}
	}

	/**
	 * Creates/updates a role
	 *
	 * @return void
	 */
	public function save($new=false)
	{
		$tbl = $this->db->openTable("roles");
		if($new || !$this->getID(true)) {
			$tbl->insert(array('name' => (string)$this->name, 'description' => (string)$this->description, 'is_admin' => (bool)$this->is_admin));
			$this->id = $this->getID(true);
		}
		else {
			// Update
			$tbl->update(array('name' => (string) $this->name, 'description' => (string) $this->description, 'is_admin' => (bool) $this->is_admin), array('AND'=> array('id' => array('op' => '=', 'val' => $this->getID(true)))));
		}
	}

	public function getDescription()
	{
		return $this->description;
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
			$db=SimpleDB::getConnection("Main");
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
		// Privileges
		if($this->getID(true)) {
			$db = SimpleDB::getConnection("Main");
			$tbl = $db->openTable("roles");
			$tbl->delete(array('AND'=>array('id' => array("op" => "=", "val" => $this->getID()))));
		}
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
		 */
		$defaultTbls=array(
					"roles"
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
							"description" => "varchar(250) NOT NULL"
						),
			);

		return SimpleUtils::installReqTbls($tables, array("database" => ((is_null($dbconf))?$this->dbconf:$dbconf)));
	}

	/**
	 * Uninstall the rolee table
	 */
	public function uninstall()
	{
		SimpleDB::getConnection("Main")->openTable("roles")->drop();
	}
}
