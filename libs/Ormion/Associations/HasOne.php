<?php

use Ormion\IRecord;
use Ormion\IMapper;

/**
 * Has one association
 *
 * @author Jan Marek
 * @license MIT
 */
class HasOneAnnotation extends Ormion\Association\BaseAssociation
{
	/** @var string */
	protected $referencedEntity;

	/** @var string */
	protected $column;

	

	public function setMapper(IMapper $mapper)
	{
		
	}



	public function setReferenced(IRecord $record, $data)
	{
		if ($data->getState() == IRecord::STATE_NEW) {
			throw new \NotImplementedException;
		}

		$record[$this->column] = $data->getPrimary();
	}



	public function retrieveReferenced(IRecord $record)
	{
		$class = $this->referencedEntity;
		return $class::find($record[$this->column]);
	}



	public function saveReferenced(IRecord $record, $data)
	{
		$data->save();
	}

}