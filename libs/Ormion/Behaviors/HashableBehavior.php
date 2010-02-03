<?php

/**
 * Hashable behavior
 *
 * @author Jan Marek
 * @license MIT
 */
class HashableBehavior extends Object implements IOrmionBehavior {

	/** @var string */
	private $column;

	/** @var callback */
	private $hashFunction;

	/**
	 * Constructor
	 * @param string $column
	 * @param callback $hashFunction
	 */
	public function __construct($column = "password", $hashFunction = "sha1") {
		$this->column = $column;
		$this->hashFunction = $hashFunction;
	}

	/**
	 * Set up behavior
	 * @param OrmionRecord $record
	 */
	public function setUp(OrmionRecord $record) {
		$record->onBeforeUpdate[] = array($this, "hashColumn");
		$record->onBeforeInsert[] = array($this, "hashColumn");
	}

	/**
	 * Hash specified column with specified hash function
	 * @param OrmionRecord $record
	 */
	public function hashColumn(OrmionRecord $record) {
		if ($record->isValueModified($this->column)) {
			$record->{$this->column} = call_user_func($this->hashFunction, $record->{$this->column});
		}
	}
}