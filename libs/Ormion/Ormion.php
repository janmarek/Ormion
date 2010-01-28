<?php

/**
 * Ormion
 *
 * @author Jan Marek
 */
final class Ormion extends Object {

	const DEFAULT_CONNECTION_NAME = "ormion";

	/** @var bool */
	public static $logSql = false;

	/** @var ILogger */
	private static $logger;

	/** @var callback */
	public static $loggerFactory = array(__CLASS__, "createLogger");

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

	/**
	 * Create logger object
	 * @return FileLogger
	 */
	public static function createLogger() {
		return new FileLogger("ormionsql-%Y-%m-%d.log");
	}

	/**
	 * Log message
	 * @param mixed $message
	 */
	public static function log($message) {
		if (!self::$logSql) {
			return;
		}

		if (empty(self::$logger)) {
			self::$logger = call_user_func(self::$loggerFactory);
		}

		self::$logger->logMessage(ILogger::INFO, (string) $message);
	}

}