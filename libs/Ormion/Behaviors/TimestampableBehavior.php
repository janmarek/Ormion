<?php

/**
 * Timestampable behavior
 *
 * @author Jan Marek
 * @license MIT
 */
class TimestampableBehavior extends Object implements IOrmionBehavior {

	/** @var string|null */
	private $created;

	/** @var string|null */
	private $updated;

	/**
	 * Constructor
	 * @param string|null $created
	 * @param string|null $updated
	 */
	public function __construct($created = "created", $updated = "updated") {
		$this->created = $created;
		$this->updated = $updated;
	}

	/**
	 * Set up behavior
	 * @param OrmionRecord $record
	 */
	public function setUp(OrmionRecord $record) {
		if (isset($this->created)) {
			$record->onBeforeInsert[] = array($this, "updateCreated");
		}

		if (isset($this->updated)) {
			$record->onBeforeUpdate[] = array($this, "updateUpdated");
		}
	}

	public function updateCreated(OrmionRecord $record) {
		$record->{$this->created} = time();
	}

	public function updateUpdated(OrmionRecord $record) {
		$record->{$this->updated} = time();
	}
}