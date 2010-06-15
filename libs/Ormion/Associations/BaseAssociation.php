<?php

namespace Ormion\Association;

/**
 * Base annotation
 *
 * @author Jan Marek
 * @license MIT
 */
abstract class BaseAssociation extends \Nette\Object implements IAssociation
{
	/** @var string */
	protected $name;



	/**
	 * Construct
	 * @param array values
	 */
	public function __construct(array $values)
	{
		foreach ($values as $k => $v) {
			$this->$k = $v;
		}
	}



	/**
	 * Get name
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

}