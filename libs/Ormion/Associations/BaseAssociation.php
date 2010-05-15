<?php

namespace Ormion\Association;

/**
 * Base annotation
 *
 * @author Jan Marek
 * @license MIT
 */
abstract class BaseAssociation extends \Nette\Object implements IAssociation {

	protected $name;

    public function __construct(array $values) {
        foreach ($values as $k => $v) {
			$this->$k = $v;
		}
    }

	public function getName() {
        return $this->name;
    }

}