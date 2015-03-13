<?php
/**
 *
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
}
?>
