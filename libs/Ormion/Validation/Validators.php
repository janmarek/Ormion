<?php

namespace Ormion\Validation;

/**
 * Validators
 *
 * @author Jan Marek
 * @license MIT
 */
class Validators extends \Nette\Object {

	/**
	 * Validate presence of required value
	 * @param \Ormion\Record record
	 * @param string field name
	 * @return bool
	 */
	public static function validatePresence($record, $name) {
		return isset($record->$name) && $record->name !== "";
    }

}