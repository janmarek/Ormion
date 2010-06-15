<?php

namespace Ormion\Validation;

/**
 * Rule violation
 *
 * @author Jan Marek
 * @license MIT
 */
class RuleViolation extends \Nette\Object
{
	/** @var string */
	private $message;

	/** @var string */
	private $name;



	/**
	 * Construct.
	 * @param string error message
	 * @param string field name
	 */
	function __construct($message, $name = null)
	{
		$this->message = $message;
		$this->name = $name;
	}



	/**
	 * Get message
	 * @return string
	 */
	public function getMessage()
	{
		return $this->message;
	}



	/**
	 * Get name
	 * @return string|null
	 */
	public function getName()
	{
		return $this->name;
	}

}

