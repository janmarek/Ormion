<?php

/**
 * Interface IBehavior
 *
 * @author Jan Marek
 * @license MIT
 */
interface IBehavior {

	/**
	 * Setup behavior
	 * @param OrmionRecord $record 
	 */
	public function setUp(IRecord $record);

}