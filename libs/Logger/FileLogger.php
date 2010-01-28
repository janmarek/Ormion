<?php

/*namespace Nette\Logger;*/

require_once dirname(__FILE__) . '/ILogger.php';

/**
 * Filesystem-based implementation of ILogger.
 *
 * @version    0.5
 * @package    Nette\Logger
 * 
 * @author     Jan Smitka <jan@smitka.org>
 * @author     Martin Pecka <martin.pecka@clevis.cz>
 * @copyright  Copyright (c) 2009-2010 Jan Smitka
 * @copyright  Copyright (c) 2009-2010 Martin Pecka
 *
 * @property int $filenameMask mask of the log filename, it can contain strftime specifiers
 * @property string $logDir the directory in which log files will reside
 * @property int|bool $minimumLogLevel minimum priority to be logged, FALSE disables the logging
 * @property int $defaultLogLevel default log priority
 * @property int $granularity log files granularity in seconds, see setGranularity() for more information
 * @property int $dateFormat log date format as in date()
 * @property-read string file path to the current log file
 */
class FileLogger extends /*Nette\*/Object implements ILogger
{
	/** @var string */
	private $filenameMask = 'log-%Y-%m-%d.log';

	/** @var string */
	private $logDir = '%logDir%';

	/** @var int|bool */
	private $minimumLogLevel;

	/** @var int */
	private $defaultLogLevel = ILogger::WARNING;

	/** @var int seconds */
	private $granularity = 0;

	/** @var string */
	private $dateFormat = 'r';

	/** @var array of function(FileLogger $logger, int $level, string $message); Occurs after the message has been written */
	public $onMessage = array();

	/** @var array of function(FileLogger $logger, string $fullName); Occurs before the new log file is created */
	public $onLogFileCreated = array();

	/** @var string path to the current log file */
	private $file;


	public static function createFileLogger($options = array())
	{
		$logger = new FileLogger();

		if (isset($options['filenameMask']))
			$logger->setFilenameMask($options['filenameMask']);
		if (isset($options['minimumLogLevel']))
			$logger->setMinimumLogLevel(self::parseLevel($options['minimumLogLevel']));
		if (isset($options['defaultLogLevel']))
			$logger->setDefaultLogLevel(self::parseLevel($options['defaultLogLevel']));
		if (isset($options['logDir']))
			$logger->setLogDir($options['logDir']);
		if (isset($options['granularity']))
			$logger->setGranularity((int) $options['granularity']);
		if (isset($options['dateFormat']))
			$logger->setDateFormat($options['dateFormat']);

		return $logger;
	}


	private static function parseLevel($level)
	{
		if (is_numeric($level))
			return (int) $level;
		else {
			$loggerInterface = 'Nette\Logger\ILogger';
			fixNamespace($loggerInterface);
			$reflection = new ReflectionClass($loggerInterface);
			if ($reflection->hasConstant((string) $level))
				return $reflection->getConstant((string) $level);
			else
				throw new InvalidArgumentException('Unknown priority level: ' . $level);
		}
	}



	/**
	 * @param string $filenameMask mask of the log filename, it can contain strftime specifiers
	 * @param int $minimumLogLevel one of the ILogger's priority constants, specifies minimum level of priority to be logged
	 * @param mixed $logDir directory in which log files will reside
	 * @param int $granularity If > 0, defines a time span used for one log
	 *
	 * @return void
	 */
	public function __construct($filenameMask = NULL, $minimumLogLevel = NULL, $defaultLogLevel = NULL, $logDir = NULL, $granularity = 0)
	{
		if ($filenameMask !== NULL)
			$this->filenameMask = $filenameMask;

		if ($minimumLogLevel === NULL)
			$minimumLogLevel = Environment::isProduction() ? ILogger::INFO : ILogger::DEBUG;
		$this->setMinimumLogLevel($minimumLogLevel);

		if ($defaultLogLevel !== NULL)
			$this->defaultLogLevel = $defaultLogLevel;

		if ($logDir !== NULL)
			$this->logDir = $logDir;

		$this->granularity = $granularity;
	}



	/**
	 * Returns the logger verbosity.
	 * @return int
	 */
	public function getMinimumLogLevel()
	{
		return $this->minimumLogLevel;
	}


	/**
	 * Sets the logger verbosity. FALSE disables the logger.
	 * @param int $level one of the ILogger's priority constants
	 * @return void
	 * @throws InvalidArgumentException if the given level is not one of the ILogger's priority constants
	 */
	public function setMinimumLogLevel($level)
	{
		if ($level !== FALSE && ($level > ILogger::DEBUG || $level < ILogger::EMERGENCY))
			throw new InvalidArgumentException('Log level must be one of the ILogger\'s priority constants.');
		$this->minimumLogLevel = $level;
	}


	/**
	 * Gets the current default level of logged messages.
	 * @return int currently set default level
	 */
	public function getDefaultLogLevel()
	{
		return $this->defaultLogLevel;
	}


	/**
	 * Sets the defalut level of logged messages.
	 * @param int $level one of the ILogger's priority constants
	 */
	public function setDefaultLogLevel($level)
	{
		if ($level > ILogger::DEBUG || $level < ILogger::EMERGENCY)
			throw new InvalidArgumentException('Log level must be one of the ILogger\'s priority constants.');
		$this->defaultLogLevel = $level;
	}


	/**
	 * Returns the filename mask of log file.
	 * @return string
	 */
	public function getFilenameMask()
	{
		return $this->filenameMask;
	}


	/**
	 * Sets the filename mask for log files.
	 * You can use the strftime specifiers.
	 *
	 * @param string $filenameMask
	 * @return void
	 * @see strftime()
	 */
	public function setFilenameMask($filenameMask)
	{
		$this->filenameMask = $filenameMask;
		$this->file = NULL;
	}


	/**
	 * Returns the directory path where log files reside.
	 *
	 * @return string with untranslated environment variables
	 */
	public function getLogDir()
	{
		return $this->logDir;
	}


	/**
	 * Sets the directory path where log files reside.
	 * You can use environment variables.
	 *
	 * @param string $logDir
	 * @return void
	 * @see Environment::expand()
	 */
	public function setLogDir($logDir)
	{
		$this->logDir = $logDir;
		$this->file = NULL;
	}


	/**
	 * Returns log files granularity.
	 * @return int in seconds
	 */
	public function getGranularity()
	{
		return $this->granularity;
	}


	/**
	 * Sets log files granularity.
	 * Please note that real granularity is also determined by filename mask.
	 *
	 * When greater than 0, it defines a time span used for one log file.
	 * Eg. if you want to create two log files per day, you can define mask
	 * "%Y-%m-%d-%H" and set this to 43200 seconds (1/2 day), and the logs
	 * won't be created each hour, but each file will contain logs from
	 * 43200 seconds, resulting in a two files per day.
	 *
	 * @param int $granularity
	 * @return void
	 * @throws InvalidArgumentException if the grannularity is not a non-negative number
	 */
	public function setGranularity($granularity)
	{
		if ($granularity < 0)
			throw new InvalidArgumentException('Granularity must be greater than or equal to 0.');

		$this->granularity = $granularity;
		$this->file = NULL;
	}


	/**
	 * Returns the date format used inside log files.
	 * @return string
	 */
	public function getDateFormat()
	{
		return $this->dateFormat;
	}


	/**
	 * Sets the date format used inside log files.
	 * Format is the same as used by date() function.
	 *
	 * @param string $dateFormat
	 * @return void
	 * @see date()
	 */
	public function setDateFormat($dateFormat)
	{
		$this->dateFormat = $dateFormat;
	}


	/**
	 * Returns the full path to the current log file.
	 * @return string
	 * @throws InvalidStateException
	 */
	public function getFile()
	{
		if ($this->file === NULL) {
			// granularity calculations
			if ($this->granularity > 1) {
				$offset = 345600 - (int) date('Z');
				$timestamp = $offset + floor((time() - $offset) / $this->granularity) * $this->granularity;
			} else
				$timestamp = time();

			$this->file = ($path = Environment::expand($this->logDir))
				    . (String::endsWith($path, '/') ? '' : '/')
				    . strftime($this->filenameMask, $timestamp);
		}

		return $this->file;
	}


	/**
	 * Log a message.
	 *
	 * The message can be formatted as in sprintf.
	 *
	 * @see sprintf
	 * @param int|string $level priority of the message or the message itself (when no level is required)
	 * @param string $message the message to log, or the first printf param
	 * @throws InvalidArgumentException if the given level is not one of the ILogger's priority constants, or the message is not specified
	 * @throws IOException if the file operation fails
	 */
	public function logMessage($level, $message = NULL)
	{
		if ($this->minimumLogLevel === FALSE)
			return;

		$args = func_get_args();

		if (is_string($level)) {
			$message = $level;
			$level = $this->defaultLogLevel;
			array_shift($args);
		} else {
			if ($message === NULL)
				throw new InvalidArgumentException('The message has to be specified.');
			array_shift($args); array_shift($args);
		}

		if ($level > ILogger::DEBUG || $level < ILogger::EMERGENCY)
			throw new InvalidArgumentException('Log level must be one of the ILogger\'s priority constants.');

		if ($level <= $this->minimumLogLevel) {
			if (!empty($args)) {
				$message = vsprintf($message, $args);
			}

			if (!file_exists($this->getFile()))
				$this->onLogFileCreated($this, $this->getFile());

			// Please note that FILE_APPEND operation is atomic (tested):
			// http://us2.php.net/manual/en/function.file-put-contents.php
			if (!file_put_contents($this->getFile(), sprintf("%s %s: %s\r\n", date($this->dateFormat), $this->logLevelToString($level), $message), FILE_APPEND))
				throw new IOException('Write operation failed.');

			$this->onMessage($this, $level, $message);
		}
	}


	/**
	 * Translate log severity level into a human-readable string.
	 * @param int $level one of ILogger's priority constants
	 * @return void
	 * @throws InvalidArgumentException if the level is unknown
	 */
	protected function logLevelToString($level)
	{
		switch ($level) {
		case ILogger::EMERGENCY:
			return 'EMERGENCY';

		case ILogger::ALERT:
			return 'ALERT';

		case ILogger::CRITICAL:
			return 'CRITICAL';

		case ILogger::ERROR:
			return 'ERROR';

		case ILogger::WARNING:
			return 'WARNING';

		case ILogger::NOTICE:
			return 'NOTICE';

		case ILogger::INFO:
			return 'INFO';

		case ILogger::DEBUG:
			return 'DEBUG';

		default:
			throw new InvalidArgumentException('Unknown priority level.');
		}
	}
}
