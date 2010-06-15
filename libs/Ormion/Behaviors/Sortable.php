<?php

namespace Ormion\Behavior;

use Ormion\IRecord;

/**
 * Sortable behavior
 *
 * @author Jan Marek
 */
class Sortable extends Nette\Object implements IBehavior
{
	/** @var string */
	private $orderColumn;

	/** @var string */
	private $groupColumn;



	/**
	 * Constructor
	 * @param string order column name
	 * @param string group column name
	 */
	public function __construct($orderColumn = "order", $groupColumn = null)
	{
		$this->orderColumn = $orderColumn;
		$this->groupColumn = $groupColumn;
	}



	/**
	 * Setup behavior
	 * @param IRecord record
	 */
	public function setUp(IRecord $record)
	{
		$record->onBeforeInsert[] = array($this, "setOrderBeforeInsert");
		$record->onBeforeDelete[] = array($this, "fixOrderBeforeDelete");
		$record->onBeforeUpdate[] = array($this, "fixOrderBeforeUpdate");
	}



	/**
	 * Set order before insert
	 * @param IRecord record
	 */
	public function setOrderBeforeInsert(IRecord $record)
	{
		$collection = $record->findAll();
		if (isset($this->groupColumn)) {
			$type = $record->getConfig()->getType($this->groupColumn);
			$collection->where("%n = %$type", $this->groupColumn, $record->{$this->groupColumn});
		}
		$record->{$this->orderColumn} = count($collection) + 1;
	}



	/**
	 * Fix order before delete
	 * @param IRecord record
	 */
	public function fixOrderBeforeDelete(IRecord $record)
	{
		$columns[] = $this->orderColumn;
		if (isset($this->groupColumn)) {
			$columns[] = $this->groupColumn;
		}

		$record->lazyLoadValues($columns);

		$fluent = $record->getMapper()->getDb()
			->update($record->getMapper()->getTable(), array(
				$this->orderColumn . "%sql" => array("%n - 1", $this->orderColumn),
			))
			->where("%n > %i", $this->orderColumn, $record->{$this->orderColumn});

		if (isset($this->groupColumn)) {
			$type = $record->getConfig()->getType($this->groupColumn);
			$fluent->where("%n = %$type", $this->groupColumn, $record->{$this->groupColumn});
		}

		$fluent->execute();
	}



	/**
	 * Fix order before update
	 * @param IRecord record
	 */
	public function fixOrderBeforeUpdate(IRecord $record)
	{
		if ($record->isValueModified($this->orderColumn) || (isset($this->groupColumn) && $record->isValueModified($this->groupColumn))) {
			$original = $record->find($record->getPrimary());

			$columns[] = $this->orderColumn;
			if (isset($this->groupColumn)) {
				$columns[] = $this->groupColumn;
			}

			$original->loadValues($columns);

			$db = $record->getMapper()->getDb();

			$fluent = $db
				->update($record->getMapper()->getTable(), array(
					$this->orderColumn . "%sql" => array("%n - 1", $this->orderColumn)
				))
				->where("%n > %i", $this->orderColumn, $original->{$this->orderColumn});

			if (isset($this->groupColumn)) {
				$type = $record->getConfig()->getType($this->groupColumn);
				$fluent->where("%n = %$type", $this->groupColumn, $original->{$this->groupColumn});
			}

			$fluent->execute();

			$fluent = $db
				->update($record->getMapper()->getTable(), array(
					$this->orderColumn . "%sql" => array("%n + 1", $this->orderColumn)
				))
				->where("%n >= %i", $this->orderColumn, $record->{$this->orderColumn});

			if (isset($this->groupColumn)) {
				$fluent->where("%n = %" . $record->getConfig()->getType($this->groupColumn), $this->groupColumn, $record->{$this->groupColumn});
			}

			$fluent->execute();
		}
	}

}