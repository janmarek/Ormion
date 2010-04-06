<?php

namespace Ormion\Behavior;

use Ormion\IRecord;

/**
 * Interface Behavior
 *
 * @author Jan Marek
 * @license MIT
 */
interface IBehavior {

	/**
	 * Setup behavior
	 * @param IRecord $record
	 * @todo rename?    initialize, initializeBehavior
	 */
	public function setUp(IRecord $record);

}