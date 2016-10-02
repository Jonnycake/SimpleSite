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
	 * Get the name of the role
	 *
	 * @return string The name of the role
	 *
	 */
	public function getName();


	/**
	 * Get the description of the role
	 *
	 * @return string The description of the role
	 */
	public function getDescription();

	/**
	 * Retrieve a role by name
	 *
	 * @return simpleRoleI An object implementing this interface
	 */
	public static function getByName($name);

	/**
	 * Retrieve a role by id
	 *
	 * @return simpleRoleI An object implementing this interface
	 */
	public static function getById($id);

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function save($new=false);

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function delete();
}
?>
