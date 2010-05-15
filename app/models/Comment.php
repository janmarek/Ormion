<?php

/**
 * Comment model
 *
 * @author Jan Marek
 * @license MIT
 *
 * @hasOne(name = Page, referencedEntity = Page, column = page)
 */
class Comment extends Ormion\Record {

	/** @var string */
	protected static $table = "comments";

}