<?php
/**
 * Singleton creational design pattern
 *
 * @author Jon Stockton
 * @version 1.0
 */

/**
 * Create a singleton object
 *  - Derived classes must implement create()
 *  - Derived classes aimed for PHP < 5.3.0, must implement instance()
 *     as: "parent::instance(__CLASS__);"
 */
abstract class Singleton extends Factory
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
	public static final function instance($class = null)
	{
		// Only works in PHP >= 5.3.0
		if(is_null($class)) {
			$class = get_called_class();
		}

		if(is_null($class::$instance)) {
			$class::$instance = $class::create();
		}

		return $class::$instance;
	}

	/**
	 * Should return a new object for derived class
	 *
	 * @return object An object created by derived class
	 */
	abstract protected static function create();
}
