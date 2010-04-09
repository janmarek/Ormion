<?php

namespace Ormion\Association;

use Ormion\IRecord;
use Ormion\IMapper;

/**
 * Association interface
 * @author Jan Marek
 * @license MIT
 */
interface IAssociation {

	public function setMapper(IMapper $mapper);
	
	public function setReferenced(IRecord $record, $data);

	public function retrieveReferenced(IRecord $record);

	public function saveReferenced(IRecord $record, $data);

}