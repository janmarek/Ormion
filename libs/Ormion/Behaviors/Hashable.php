<?php

namespace Ormion\Behavior;

use Ormion\IRecord;

/**
 * Hashable behavior
 *
 * @author Jan Marek
 * @license MIT
 */
class Hashable extends Nette\Object implements IBehavior
{

	/** @var string */
	private $column;

	/** @var callback */
	private $hashFunction;



	/**
	 * Constructor
	 * @param string $column
	 * @param callback $hashFunction
	 */
	public function __construct($column = "password", $hashFunction = "sha1")
	{
		$this->column = $column;
		$this->hashFunction = $hashFunction;
	}



	/**
	 * Set up behavior
	 * @param IRecord $record
	 */
	public function setUp(IRecord $record)
	{
		$record->onBeforeUpdate[] = array($this, "hashColumn");
		$record->onBeforeInsert[] = array($this, "hashColumn");
	}



	/**
	 * Hash specified column with specified hash function
	 * @param IRecord $record
	 */
	public function hashColumn(IRecord $record)
	{
		if ($record->isValueModified($this->column)) {
			$record->{$this->column} = call_user_func($this->hashFunction, $record->{$this->column});
		}
	}

}