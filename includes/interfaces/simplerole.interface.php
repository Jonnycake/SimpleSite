<?php
interface simpleRoleI
{
	public function __construct($role);
	public function hasPrivilege($privilege);
	public function getPrivileges();
	public function getName();
	public function isAdmin();
}
?>
