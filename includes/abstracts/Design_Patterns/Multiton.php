<?php
/**
 * Multiton creational design pattern
 *
 * @author Jon Stockton
 * @version 1.0
 */

/**
 * Create a Multiton object
 *  - Derived classes must implement create()
 *  - Derived classes aimed for PHP < 5.3.0, must implement instance()
 *     as: "parent::instance($instanceName, __CLASS__);"
 */
abstract class Multiton extends Factory
{
	/**
	 * Contains the object created to be reused
	 * @var object $instance
	 */
	protected static $instance = null;

	/**
	 * Returns a newly created (or already created instance) of $class
	 *   Must pass the $class parameter in PHP < 5.3.0
	 *
	 * @param string $class The name of the class to create an instance of
	 * @return object An object created using $class
	 */
	public static function instance($instanceName, $class = null)
	{
		// Only works in PHP >= 5.3.0
		if(is_null($class)) {
			$class = get_called_class();
		}

		if(is_null($class::$instance)) {
			$class::$instance = array();
		}

		if(!isset($class::$instance[$instanceName])) {
			$class::$instance[$instanceName] = $class::create();
		}

		return $class::$instance[$instanceName];
	}

	/**
	 * Should return a new object for derived class
	 *  Inherited from Factory
	 * @return object An object created by derived class
	 */
	// abstract public static function create();
}
