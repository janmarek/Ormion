<?php


/**
 * ArrayList with lazy loading.
 *
 * @author     Jan Marek, Roman Sklenář
 * @copyright  Copyright (c) 2009 Jan Marek (http://www.janmarek.net)
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    MIT, New BSD License
 * @example    http://addons.nettephp.com/LazyArrayList
 */
abstract class LazyArrayList extends ArrayList {

	/** @var bool */
	private $loaded = FALSE;


	/**
	 * Is collection loaded? Public property getter.
	 * @return bool
	 */
	public function isLoaded() {
		return $this->loaded;
	}


	/**
	 * Public property setter.
	 * @param bool $loaded
	 */
	protected function setLoaded($loaded) {
		$this->loaded = (bool) $loaded;
	}


	/**
	 * Loads items into collection.
	 * @return void
	 */
	abstract protected function load();


	/**
	 * Ensure that collection is loaded.
	 * @return void
	 */
	protected function loadCheck() {
		if (!$this->loaded) {
			$this->load();
		}
	}


	/**
	 * Forces collection to reload.
	 * @return void
	 */
	public function invalidate() {
		$this->loaded = FALSE;
	}



	/********************* ArrayList method modifications *********************/



	/**
	 * Removes all of the elements from this collection.
	 * @return void
	 * @throws NotSupportedException
	 */
	public function clear() {
		parent::clear();
		$this->loaded = TRUE;
	}


	/**
	 * Import from array or any traversable object.
	 * @param  array|Traversable
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function import($arr) {
		parent::import($arr);
		$this->loaded = TRUE;
	}

	/**
	 * Exports the ArrayObject to an array.
	 * @return array
	 */
	public function getArrayCopy() {
		$this->loadCheck();
		return parent::getArrayCopy();
	}



	/********************* interface ArrayAccess ********************/



	/**
	 * Returns item (ArrayAccess implementation).
	 * @param  int index
	 * @return mixed
	 * @throws ArgumentOutOfRangeException
	 */
	public function offsetGet($index) {
		$this->loadCheck();
		return parent::offsetGet($index);
	}


	/**
	 * Replaces (or appends) the item (ArrayAccess implementation).
	 * @param  int index
	 * @param  object
	 * @return void
	 * @throws InvalidArgumentException, NotSupportedException, ArgumentOutOfRangeException
	 */
	public function offsetSet($index, $item) {
		$this->loadCheck();
		parent::offsetSet($index, $item);
	}


	/**
	 * Exists item? (ArrayAccess implementation).
	 * @param  int index
	 * @return bool
	 */
	public function offsetExists($index) {
		$this->loadCheck();
		return parent::offsetExists($index);
	}


	/**
	 * Removes the element at the specified position in this list.
	 * @param  int index
	 * @return void
	 * @throws NotSupportedException, ArgumentOutOfRangeException
	 */
	public function offsetUnset($index) {
		$this->loadCheck();
		parent::offsetUnset($index);
	}



	/********************* interface Countable *********************/



	/**
	 * Get the number of public properties in the ArrayObject
	 * @return int
	 */
	public function count() {
		$this->loadCheck();
		return parent::count();
	}

}