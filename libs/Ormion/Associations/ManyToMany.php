<?php

use Ormion\IMapper;
use Ormion\IRecord;

/**
 * Many to many association
 *
 * @author Jan Marek
 */
class ManyToManyAnnotation extends Ormion\Association\BaseAssociation
{
	/** @var string */
	protected $referencedEntity;

	/** @var string */
	protected $connectingTable;

	/** @var string */
	protected $localKey;

	/** @var string */
	protected $referencedKey;

	/** @var IMapper */
	protected $mapper;



	public function setMapper(IMapper $mapper)
	{
		$this->mapper = $mapper;
	}



	public function setReferenced(IRecord $record, $data)
	{
		
	}



	public function retrieveReferenced(IRecord $record)
	{
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

		$class = $this->referencedEntity;
		return $class::findAll()->where("%n in %in", $class::getMapper()->getConfig()->getPrimaryColumn(), $ids);
	}



	public function saveReferenced(IRecord $record, $data)
	{
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
