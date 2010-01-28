<?php

/*namespace Nette\Logger;*/


/**
 * Message logger.
 *
 * @version    0.5
 * @package    Nette\Logger
 *
 * @author     Jan Smitka <jan@smitka.org>
 * @author     Martin Pecka <martin.pecka@clevis.cz>
 * @copyright  Copyright (c) 2009-2010 Jan Smitka
 * @copyright  Copyright (c) 2009-2010 Martin Pecka
 */
interface ILogger
{
	/**#@+ syslog-compatibile priority levels */

	/** system is unusable */
	const EMERGENCY = 0;
	/** an alias for EMERGENCY */
	const EMERG = 0;

	/** action must be taken immediately */
	const ALERT = 1;

	/** critical conditions */
	const CRITICAL = 2;
	/** an alias for CRITICAL */
	const CRIT = 2;

	/** error conditions */
	const ERROR = 3;
	/** an alias for ERROR */
	const ERR = 3;

	/** warning conditions */
	const WARNING = 4;

	/** normal but significant condition */
	const NOTICE = 5;

	/** informational */
	const INFO = 6;

	/** debug-level messages */
	const DEBUG = 7;
	/**#@-*/

	/**
	 * Sets the log verbosity.
	 * @param int $level minimum priority level to be logged
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function setMinimumLogLevel($level);

	/**
	 * Logs a message.
	 * @param int|string $level priority of the message or the message itself (when no level is required)
	 * @param string $message the message to log
	 */
	public function logMessage($level, $message = NULL);
}