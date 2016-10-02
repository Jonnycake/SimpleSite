<?php
/**
 * SimpleRole Abstract Class
 * 
 * @package SimpleRole
 * @author Jonathan Stockton <jonathan@simplesite.ddns.net>
 */
 
/**
 * SimpleRole abstract
 */
abstract class SimpleRole implements simpleRoleI
{
	/**
	 *
	 *
	 *
	 *
	 */
	protected $id=null;

	/**
	 * Whether or not the role is an administrative role (can do anything)
	 *
	 * @var bool $is_admin
	 */
	public $is_admin=false;


	/**
	 * The name of the role
	 *
	 * @var string $name
	 */
	private $name="";

	/**
	 * Create the SimpleRole object
	 *
	 * @param string $name The name of the role to use.
	 * @param bool $admin Whether or not the roe is an administrative role (able to do anything)
	 *
	 * @return void
	 */
	public function __construct($name="Guest")
	{
		$this->id=$this->getID();
		$this->name=$name;
		$this->description = $this->getDescription();
	}

	/**
	 * Returns the name of the role
	 *
	 * @return string The name of the role
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 *
	 *
	 *
	 *
	 */
	abstract public function getID();

	/**
	 *
	 *
	 *
	 *
	 */
	abstract public function save($new=false);

	abstract public static function getByName($name);
	abstract public static function getById($id);

	/**
	 *
	 *
	 *
	 *
	 */
	abstract public function delete();

	/**
	 * Should return if the role system is properly installed
	 *
	 * @return bool Whether or not the role system is installed
	 */
	abstract public function isInstalled();

	/**
	 * Should install the role system (and return true if success or false if not)
	 *
	 * @return bool Whether or not the role system could be installed
	 */
	abstract public function install();
}
?>
