<?php

namespace Ormion\Association;

use Ormion\IMapper;
use Ormion\IRecord;

/**
 * Has many association
 *
 * @author Jan Marek
 * @license MIT
 */
class HasMany extends \Nette\Object implements IAssociation {

	/** @var string */
	private $referencedEntity;

	/** @var string */
	private $column;

	/** @var IMapper */
	private $mapper;


	/**
	 * Construct
	 * @param string referenced entity
	 * @param string column
	 */
	public function __construct($referencedEntity, $column) {
		$this->referencedEntity = $referencedEntity;
		$this->column = $column;
	}


	public function setMapper(IMapper $mapper) {
		$this->mapper = $mapper;
	}


	public function setReferenced(IRecord $record, $data) {
		if ($record->getState() === IRecord::STATE_EXISTING) {
			foreach ($data as $item) {
				$item[$this->column] = $record->getPrimary();
			}
		}
	}


	public function retrieveReferenced(IRecord $record) {
		if ($record->getState() === IRecord::STATE_NEW) {
			return array();
		}

		$cls = $this->referencedEntity;
		return $cls::findAll(array(
			$this->column => $record->getPrimary()
		));
	}


	public function saveReferenced(IRecord $record, $data) {
		$this->setReferenced($record, $data);

		$pks = array();

		foreach ($data as $item) {
			$item->save();
			$pks[] = $item->getPrimary();
		}

		$cls = $this->referencedEntity;
		$q = $this->mapper->getDb()
			->delete($cls::getMapper()->getTable())
			->where(array(
				$this->column => $record->getPrimary()
			));

		if (!empty($pks)) {
			$q->and("%n not in %in", $cls::getConfig()->getPrimaryColumn(), $pks);
		}
		
		$q->execute();
	}
	
}