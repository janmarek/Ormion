<?php

namespace Ormion\Validation;

/**
 * Validators
 *
 * @author Jan Marek
 * @license MIT
 */
class Validators extends \Nette\Object
{
	/**
	 * Validate presence of required value
	 * @param \Ormion\Record record
	 * @param string field name
	 * @return bool
	 */
	public static function validatePresence($record, $name)
	{
		return isset($record->$name) && $record->$name !== "";
	}



	/**
	 * Is value valid email address?
	 * @param \Ormion\Record record
	 * @param string field name
	 * @return bool
	 */
	public static function validateEmail($record, $name)
	{
		if (!self::validatePresence($record, $name))
			return false;

		$atom = "[-a-z0-9!#$%&'*+/=?^_`{|}~]"; // RFC 5322 unquoted characters in local-part
		$localPart = "(\"([ !\\x23-\\x5B\\x5D-\\x7E]*|\\\\[ -~])+\"|$atom+(\\.$atom+)*)"; // quoted or unquoted
		$chars = "a-z0-9\x80-\xFF"; // superset of IDN
		$domain = "[$chars]([-$chars]{0,61}[$chars])"; // RFC 1034 one domain component
		return (bool) preg_match("(^$localPart@($domain?\\.)+[-$chars]{2,19}\\z)i", $record->$name);
	}

}