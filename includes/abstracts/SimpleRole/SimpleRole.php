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
	 * Whether or not the role is an administrative role (can do anything)
	 *
	 * @var bool $is_admin
	 */
	public $is_admin=false;

	/**
	 * What privileges the role has
	 *
	 * @var array $privileges
	 */
	private $privileges=array();

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
	public function __construct($name="Guest", $admin=false)
	{
		$this->is_admin=$admin;
		$this->name=$name;
		$this->privileges=$this->getPrivileges();
	}

	/**
	 * Check if the user has the specified privilege
	 *
	 * @param string|array $privilege The name(s) of the privilege to be checked.
	 *
	 * @return bool Whether or not the role has the specified privilege
	 */
	public function hasPrivilege($privilege)
	{
		if(is_string($privilege)) {
			return (in_array($privilege, $this->privileges) || $this->isAdmin());
		} else if(is_array($privilege)) {
			$hasPriv=true;
			foreach($privilege as $priv) {
				if(!(in_array($privilege, $this->privileges) || $this->isAdmin())) {
					$hasPriv=false;
				}
			}
			return $hasPriv;
		}
		return false;
	}

	/**
	 * Returns whether the role is an administrative role
	 *
	 * @return bool Whether or not the role is an administrative role
	 */
	public function isAdmin()
	{
		return $this->is_admin;
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
	 * Should return the privileges array as well as set it in $this->privileges
	 *
	 * @return array Array of privileges
	 */
	abstract public function getPrivileges();

	/**
	 * Should return if the role system is properly installed
	 *
	 * @return bool Whether or not the role system is installed
	 */
	abstract public function isInstalled();
}
?>
