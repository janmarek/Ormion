<?php

namespace Ormion;

use Nette\Object;
use Nette\Environment;
use LogicException;
use DibiProfiler;
use dibi;

/**
 * Ormion main class
 *
 * @author Jan Marek
 * @license MIT
 */
final class Ormion extends Object {

	const DEFAULT_CONNECTION_NAME = "ormion";

	
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
	public static function connect($config, $name = self::DEFAULT_CONNECTION_NAME) {
		dibi::connect($config, $name);
	}


	/**
	 * Enable SQL logger
	 * @param string $path path to log file
	 */
	public static function enableProfiler($filePath = null, $connectionName = self::DEFAULT_CONNECTION_NAME) {
		$profiler = new DibiProfiler;

		if ($filePath !== false) {
			if ($filePath === null) {
				$filePath = Environment::getVariable("logDir") . "/ormion-" . date("Y-m-d") . ".sql";
			}
			
			$profiler->setFile($filePath);
			$profiler->useFirebug = false;
		}

		dibi::getConnection($connectionName)->setProfiler($profiler);
	}

}