<?php

namespace Ormion\Association;

use Ormion\IRecord;
use Ormion\IMapper;

/**
 * Association interface
 * 
 * @author Jan Marek
 * @license MIT
 */
interface IAssociation extends \Nette\Reflection\IAnnotation
{
	/**
	 * Get name
	 * @return string
	 */
	public function getName();


  	/**
	 * Set mapper
	 * @param IMapper mapper
	 */
	public function setMapper(IMapper $mapper);


	/**
	 * Set referenced
	 * @param IRecord record
	 * @param mixed data
	 */
	public function setReferenced(IRecord $record, $data);


	/**
	 * Retrieve referenced
	 * @param IRecord record
	 * @return mixed
	 */
	public function retrieveReferenced(IRecord $record);


	/**
	 * Save referenced
	 * @param IRecord record
	 * @param mixed data
	 */
	public function saveReferenced(IRecord $record, $data);

}