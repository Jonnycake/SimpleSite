<?php
/**
 * Factory creational design pattern
 *
 * @author Jon Stockton
 * @version 1.0
 */

/**
 * Create an object while hiding constructor implementation
 */
abstract class Factory
{
	/**
	 * Should return a new object
	 *
	 * @return object An object created for use
	 */
	abstract public static function create();
}
