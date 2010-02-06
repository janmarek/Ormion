<?php

/**
 * Ormion main class
 *
 * @author Jan Marek
 * @license MIT
 */
final class Ormion extends Object {

	const DEFAULT_CONNECTION_NAME = "ormion";

	/** @var array */
	private static $mappers;

	
	/**
	 * Static class - cannot be instantiated.
	 */
	public function __construct() {
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


	/**
	 * Enable SQL logger
	 * @param string $path path to log file
	 */
	public static function enableSqlLogger($path = null) {
		if ($path === null) {
			$path = Environment::getVariable("%logDir%") . "/ormion-sql-" . date("Y-m-d") . ".sql";
		}

		dibi::getProfiler()->setFile($path);
	}


	/**
	 * Get mapper
	 * @return IMapper
	 */
	public static function getMapper($recordClass) {
		if (empty(self::$mappers[$recordClass])) {
			$mapperClass = call_user_func(array($recordClass, "getMapperClass"));
			$table = call_user_func(array($recordClass, "getTable"));
			self::$mappers[$recordClass] = new $mapperClass($table, $recordClass);
		}

		return self::$mappers[$recordClass];
	}

}