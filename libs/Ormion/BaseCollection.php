<?php

namespace Ormion;

/**
 * Base collection
 *
 * @author David Grudl, Jan Marek
 * @license MIT
 */
abstract class BaseCollection extends \Nette\FreezableObject implements \ArrayAccess, \Countable, \IteratorAggregate
{
	/** @var array */
	private $list = array();

	/** @var string */
	private $type = null;



	/**
	 * Construct
	 * @param string item type
	 */
	public function __construct($type = null)
	{
		$this->type = $type;
	}



	/**
	 * Get item type
	 * @return string
	 */
	public function getItemType()
	{
		return $this->type;
	}



	/**
	 * Check item before add
	 * @param mixed item
	 */
	protected function beforeAdd($item)
	{
		$this->updating();

		if ($this->type !== null && !($item instanceof $this->type)) {
			throw new \InvalidArgumentException("Item must be $this->itemType type.");
		}
	}



	/**
	 * Set array
	 * @param array values
	 */
	protected function setArray($array)
	{
		$this->list = $array;
	}



	/**
	 * Get array
	 * @return array
	 */
	public function toArray()
	{
		return $this->list;
	}



	/**
	 * Returns an iterator over all items.
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->toArray());
	}



	/**
	 * Returns items count.
	 * @return int
	 */
	public function count()
	{
		return count($this->toArray());
	}



	/**
	 * Replaces or appends a item.
	 * @param  int
	 * @param  mixed
	 * @return void
	 * @throws \OutOfRangeException
	 */
	public function offsetSet($index, $value)
	{
		$this->beforeAdd($value);

		$list = $this->toArray();

		if ($index === NULL) {
			$list[] = $value;
		} elseif ($index < 0 || $index >= count($list)) {
			throw new \OutOfRangeException("Offset invalid or out of range");
		} else {
			$list[(int) $index] = $value;
		}

		$this->setArray($list);
	}



	/**
	 * Returns a item.
	 * @param  int
	 * @return mixed
	 * @throws \OutOfRangeException
	 */
	public function offsetGet($index)
	{
		$list = $this->toArray();

		if ($index < 0 || $index >= count($list)) {
			throw new \OutOfRangeException("Offset invalid or out of range");
		}

		return $list[(int) $index];
	}



	/**
	 * Determines whether a item exists.
	 * @param  int
	 * @return bool
	 */
	public function offsetExists($index)
	{
		return $index >= 0 && $index < count($this->toArray());
	}



	/**
	 * Removes the element at the specified position in this list.
	 * @param  int
	 * @return void
	 * @throws \OutOfRangeException
	 */
	public function offsetUnset($index)
	{
		$list = $this->toArray();

		if ($index < 0 || $index >= count($list)) {
			throw new \OutOfRangeException("Offset invalid or out of range");
		}

		array_splice($list, (int) $index, 1);

		$this->setArray($list);
	}

}
