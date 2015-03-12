<?php
/**
 * Interface for SimpleRole
 *
 * @package SimpleRole
 * @author Jonathan Stockton <jonathan@simplesite.ddns.net>
 */

/**
 * simpleRoleI interface
 */
interface simpleRoleI
{

	/**
	 * Just a simple constructor to standardize call signatures
	 *
	 * @param array|string $role The name(s) of the role(s) to use for privileges
	 * @return null
	 */
	public function __construct($role);


	/**
	 * Check if the role has a specific privilege
	 *
	 * @param string $privilege The name of the privilege to be checked.
	 * @return bool Whether or not the user has the specified privilege
	 */
	public function hasPrivilege($privilege);


	/**
	 * Return the list of privileges for the role
	 *
	 * @return array List of privileges
	 */
	public function getPrivileges();


	/**
	 * Get the name of the role(s)
	 *
	 * @return string The name of the role(s)
	 *
	 */
	public function getName();


	/**
	 * Check if the user has privilege to do anything
	 *
	 * @return bool Whether or not the user can do anything regardless of privilege list
	 */
	public function isAdmin();
}
?>
