<?php

use Ormion\Validation\RuleViolation;
use Ormion\Validation\Validators;

/**
 * Comment model
 *
 * @author Jan Marek
 * @license MIT
 *
 * @table comments
 * @hasOne(name = Page, referencedEntity = Page, column = page)
 */
class Comment extends Ormion\Record {

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

		if (!Validators::validatePresence($this, "mail")) {
			$violations[] = new RuleViolation("Fill e-mail.", "text");
		}

		if (!Validators::validateEmail($this, "mail")) {
			$violations[] = new RuleViolation("Fill correct e-mail.", "text");
		}

		return $violations;
    }

}