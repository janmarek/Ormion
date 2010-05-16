<?php

use Ormion\Validation\RuleViolation;
use Ormion\Validation\Validators;

/**
 * Pages model
 *
 * @author Jan Marek
 * @license MIT
 *
 * @table pages
 * @manyToMany(name = Tags, referencedEntity = Tag, connectingTable = connections, localKey = pageId, referencedKey = tagId)
 * @hasMany(name = Comments, referencedEntity = Comment, column = page)
 */
class Page extends Ormion\Record {

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