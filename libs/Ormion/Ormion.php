<?php

/**
 * Ormion
 *
 * @author Jan Marek
 * @license MIT
 */
final class Ormion extends Object {

	const DEFAULT_CONNECTION_NAME = "ormion";

	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct() {
		throw new LogicException("Cannot instantiate static class " . get_class($this));
	}

	/**
	 * Add connection
	 * @param array|string|ArrayObject $config connection parameters
	 * @param string $name connection name
	 */
	public static function addConnection($config, $name = self::DEFAULT_CONNECTION_NAME) {
		dibi::connect($config, $name);
	}

}