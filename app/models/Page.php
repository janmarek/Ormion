<?php

use Ormion\Validation\RuleViolation;
use Ormion\Validation\Validators;

/**
 * Pages model
 *
 * @author Jan Marek
 * @license MIT
 */
class Page extends Ormion\Record {

	/** @var string */
	protected static $table = "pages";


	/**
	 * Validate record
	 * @return array
	 */
	public function getRuleViolations() {
        $violations = array();

		if (!Validators::validatePresence($this, "name")) {
			$violations[] = new RuleViolation("Fill name.", "name");
		}
		
		if (!Validators::validatePresence($this, "text")) {
			$violations[] = new RuleViolation("Fill text.", "text");
		}

		return $violations;
    }


}