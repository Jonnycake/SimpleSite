<?php
/**
 * SimpleUser interface
 *
 * @package SimpleUser
 */

/**
 *
 *
 *
 *
 *
 */
interface simpleUserI
{
	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function isAdmin();

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function isGuest();

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function login($username, $password);

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function logout();

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function getRoles();

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

	public function hashPassword($password=null);
}
?>
