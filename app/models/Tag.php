<?php

use Ormion\Validation\RuleViolation;
use Ormion\Validation\Validators;

/**
 * Tag
 *
 * @author Jan Marek
 * @license MIT
 *
 * @table tags
 * @manyToMany(name = Pages, referencedEntity = Page, connectingTable = connections, referencedKey = pageId, localKey = tagId)
 */
class Tag extends Ormion\Record {

	/**
	 * Validate record
	 * @return array
	 */
	public function getRuleViolations() {
        $violations = array();

		if (!Validators::validatePresence($this, "name")) {
			$violations[] = new RuleViolation("Fill name.", "name");
		}

		if (!Validators::validatePresence($this, "url")) {
			$violations[] = new RuleViolation("Url is empty.", "url");
		}

		return $violations;
    }

}