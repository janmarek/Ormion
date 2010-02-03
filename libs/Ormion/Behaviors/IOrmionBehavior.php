<?php

/**
 * Interface IOrmionBehavior
 *
 * @author Jan Marek
 * @license MIT
 */
interface IOrmionBehavior {

	/**
	 * Set up behavior
	 * @param OrmionRecord $record 
	 */
	public function setUp(OrmionRecord $record);

}