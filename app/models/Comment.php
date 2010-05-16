<?php

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

}