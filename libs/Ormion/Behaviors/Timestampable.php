<?php

namespace Ormion\Behavior;

use Nette\Object;
use Ormion\IRecord;

/**
 * Timestampable behavior
 *
 * @author Jan Marek
 * @license MIT
 */
class Timestampable extends Object implements IBehavior
{
	/** @var string|null */
	private $created;

	/** @var string|null */
	private $updated;



	/**
	 * Constructor
	 * @param string|null created
	 * @param string|null updated
	 */
	public function __construct($created = "created", $updated = "updated")
	{
		$this->created = $created;
		$this->updated = $updated;
	}



	/**
	 * Setup behavior
	 * @param IRecord record
	 */
	public function setUp(IRecord $record)
	{
		if (isset($this->created)) {
			$record->onBeforeInsert[] = array($this, "updateCreated");
		}

		if (isset($this->updated)) {
			$record->onBeforeUpdate[] = array($this, "updateUpdated");
		}
	}



	public function updateCreated(IRecord $record)
	{
		$record->{$this->created} = time();
	}



	public function updateUpdated(IRecord $record)
	{
		$record->{$this->updated} = time();
	}

}