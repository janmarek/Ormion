<?php

namespace Ormion\Behavior;

/**
 * Interface Behavior
 *
 * @author Jan Marek
 * @license MIT
 */
interface IBehavior
{
	/**
	 * Setup behavior
	 * @param IRecord record
	 */
	public function setUp(\Ormion\IRecord $record);
}