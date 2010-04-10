<?php

namespace Ormion\Association;

use Ormion\IMapper;
use Ormion\IRecord;

/**
 * Many to many association
 *
 * @author Jan Marek
 */
class ManyToMany extends \Nette\Object implements IAssociation {

	private $entity;

	private $connectingTable;

	private $localKey;

	private $referencedKey;

	/** @var IMapper */
	private $mapper;

	public function __construct($entity, $connectingTable, $localKey, $referencedKey) {
		$this->entity = $entity;
		$this->connectingTable = $connectingTable;
		$this->localKey = $localKey;
		$this->referencedKey = $referencedKey;
	}

	public function setMapper(IMapper $mapper) {
		$this->mapper = $mapper;
	}

	public function setReferenced(IRecord $record, $data) {
		
	}

	public function retrieveReferenced(IRecord $record) {
		if ($record->getState() == IRecord::STATE_NEW) {
			return array();
		}

		/* @var $db \DibiConnection */
		$db = $this->mapper->getDb();
		$ids = $db
			->select("%n", $this->referencedKey)
			->from("%n", $this->connectingTable)
			->where("%n = %i", $this->localKey, $record->getPrimary())
			->fetchPairs();

		$class = $this->entity;
		return $class::findAll()->where("%n in %in", $class::getMapper()->getConfig()->getPrimaryColumn(), $ids);
	}

	public function saveReferenced(IRecord $record, $data) {
		$db = $this->mapper->getDb();
		$ids = $db
			->delete($this->connectingTable)
			->where("%n = %i", $this->localKey, $record->getPrimary())
			->execute();

		if (count($data)) {
			$q[] = "insert into [$this->connectingTable]";

			foreach ($data as $referencedRecord) {
				$referencedRecord->save();

				$q[] = array(
					$this->localKey => $record->getPrimary(),
					$this->referencedKey => $referencedRecord->getPrimary(),
				);
			}

			$db->query($q);
		}
	}

}
