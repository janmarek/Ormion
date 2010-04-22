<?php

namespace Ormion\Association;

use Ormion\IRecord;
use Ormion\IMapper;

/**
 * HasOne
 *
 * @author Jan Marek
 */
class HasOne extends \Nette\Object implements IAssociation {
	
	private $referencedEntity;
	
	private $column;

	public function __construct($referencedEntity, $column) {
		$this->referencedEntity = $referencedEntity;
		$this->column = $column;
	}

	public function setMapper(IMapper $mapper) {
		
	}

	public function setReferenced(IRecord $record, $data) {
		if ($data->getState() == IRecord::STATE_NEW) {
			throw new \NotImplementedException;
		}

		$record[$this->column] = $data->getPrimary();
	}

	public function retrieveReferenced(IRecord $record) {
		$class = $this->referencedEntity;
		return $class::find($record[$this->column]);
	}

	public function saveReferenced(IRecord $record, $data) {
		$data->save();
	}

}