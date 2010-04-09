<?php

namespace Ormion\Association;

use Ormion\IRecord;

/**
 * AssociatiedCollection
 *
 * @author Jan Marek
 * @license MIT
 */
class AssociatiedCollection extends \Nette\Collections\ArrayList {

	private $condition = array();

	private function updateRecord(IRecord $record) {
		foreach ($this->condition as $key => $value) {
			if ($record->$key !== $value) {
				$record->$key = $value;
			}
		}
	}

	public function getCondition() {
		return $this->condition;
	}

	public function setCondition($condition) {
		$this->updating();

		$this->condition = $condition;
		foreach ($this as $record) {
			$this->updateRecord($record);
		}
	}

	protected function beforeAdd($item) {
		parent::beforeAdd($item);
		$this->updateRecord($item);
	}

	
}