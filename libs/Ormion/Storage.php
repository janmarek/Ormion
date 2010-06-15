<?php

namespace Ormion;

use ArrayIterator, ArrayObject;

/**
 * Ormion storage
 *
 * @author Jan Marek
 */
class Storage extends \Nette\FreezableObject implements \ArrayAccess, \IteratorAggregate
{
	// <editor-fold defaultstate="collapsed" desc="variables">

	/** @var ArrayObject */
	private $values;

	/** @var array */
	private $defaults = array();

	/** @var array */
	private $aliases = array();

	/** @var array */
	private $modified = array();

	/** @var array */
	private $setters = array();

	/** @var array */
	private $getters = array();

	/** @var array */
	private $inputFilters = array();

	/** @var array */
	private $outputFilters = array();

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="construct">

	/**
	 * Constructor
	 * @param array|int data
	 */
	public function __construct($data = null)
	{
		$this->init();

		$this->values = new ArrayObject(array());

		if ($data !== null) {
			$this->setValues($data);
		}
	}



	/**
	 * User initialization, can be overriden
	 */
	protected function init()
	{

	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getters, setters, filters">

	/**
	 * Set callback for setting values
	 * @param string name
	 * @param callback php callback
	 * @return OrmionStorage
	 */
	public function registerSetter($name, $callback)
	{
		if (!is_callable($callback)) {
			throw new \InvalidArgumentException("Argument is not callable.");
		}

		$this->setters[$this->fixName($name)] = $callback;
		return $this;
	}



	/**
	 * Set callback for getting values
	 * @param string name
	 * @param callback php callback
	 * @return OrmionStorage
	 */
	public function registerGetter($name, $callback)
	{
		if (!is_callable($callback)) {
			throw new \InvalidArgumentException("Argument is not callable.");
		}

		$this->getters[$this->fixName($name)] = $callback;
		return $this;
	}



	/**
	 * Add input filter
	 * @param string name
	 * @param callback php callback
	 * @return OrmionStorage
	 */
	public function addInputFilter($name, $callback)
	{
		if (!is_callable($callback)) {
			throw new \InvalidArgumentException("Argument is not callable.");
		}

		$this->inputFilters[$this->fixName($name)][] = $callback;
		return $this;
	}



	/**
	 * Add output filter
	 * @param string name
	 * @param callback php callback
	 * @return OrmionStorage
	 */
	public function addOutputFilter($name, $callback)
	{
		if (!is_callable($callback)) {
			throw new \InvalidArgumentException("Argument is not callable.");
		}

		$this->outputFilters[$this->fixName($name)][] = $callback;
		return $this;
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="modified values">

	/**
	 * Set all values as unmodified
	 */
	public function clearModified()
	{
		$this->modified = array();
	}



	/**
	 * Get modified column names
	 * @return array
	 */
	public function getModified()
	{
		return array_keys($this->modified);
	}



	/**
	 * Is value modified
	 * @param string name
	 * @return bool
	 */
	public function isValueModified($name)
	{
		return isset($this->modified[$name]);
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="aliases">

	/**
	 * Set alias
	 * @param string alias
	 * @param string name
	 * @return OrmionStorage
	 */
	public function setAlias($alias, $name)
	{
		$this->aliases[$alias] = $name;
		return $this;
	}



	/**
	 * Normalize column name (replace alias)
	 * @param string name
	 * @return string
	 */
	protected function fixName($name)
	{
		if (isset($this->aliases[$name])) {
			return $this->aliases[$name];
		}

		return $name;
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="data">

	/**
	 * Get data storage
	 * @return ArrayObject
	 * @deprecated
	 */
	public function getStorage()
	{
		return $this->values;
	}



	/**
	 * Set default value
	 * @param string name
	 * @param mixed value
	 * @return OrmionStorage
	 */
	public function setDefaultValue($name, $value)
	{
		// todo vyhazovat výjimku když je pozdě
		$this->defaults[$this->fixName($name)] = $value;
		return $this;
	}



	/**
	 * Get default values
	 * @return array default values
	 */
	public function getDefaultValues()
	{
		return $this->defaults;
	}



	/**
	 * Multiple getter
	 * @param array columns
	 * @return array
	 */
	public function getValues($columns = null)
	{
		if ($columns === null) {
			$columns = array_unique(
				array_merge(
					array_keys((array) $this->values),
					array_keys($this->getters)
				)
			);
		}

		$arr = array();

		foreach ($columns as $column) {
			$arr[$column] = $this->__get($column);
		}

		return $arr;
	}



	/**
	 * Multiple setter
	 * @param array data
	 * @return OrmionStorage
	 */
	public function setValues($data)
	{
		foreach ($data as $key => $value) {
			$this->__set($key, $value);
		}

		return $this;
	}



	/**
	 * Magic getter for field value, do not call directly
	 * @param string name
	 * @return mixed
	 */
	public function & __get($name)
	{
		$name = $this->fixName($name);

		if (isset($this->getters[$name])) {
			$ret = call_user_func($this->getters[$name], $this, $name);
		} else {
			if (array_key_exists($name, $this->values)) {
				$ret = $this->values[$name];
			} else {
				throw new \MemberAccessException("Value '$name' was not set.");
			}
		}

		// output filters
		if (isset($this->outputFilters[$name])) {
			foreach ($this->outputFilters[$name] as $filter) {
				$ret = call_user_func($filter, $ret);
			}
		}

		return $ret;
	}



	/**
	 * Magic setter for field value, do not call directly
	 * @param string name
	 * @param mixed value
	 */
	public function __set($name, $value)
	{
		$this->updating();

		$name = $this->fixName($name);

		// input filters
		if (isset($this->inputFilters[$name])) {
			foreach ($this->inputFilters[$name] as $filter) {
				$value = call_user_func($filter, $value);
			}
		}

		if (isset($this->setters[$name])) {
			call_user_func($this->setters[$name], $this, $name, $value);
		} else {
			$this->values[$name] = $value;
		}

		$this->modified[$name] = true;
	}



	/**
	 * Magic isset, do not call directly
	 * @param string name
	 * @return bool
	 */
	public function __isset($name)
	{
		return isset($this->values[$this->fixName($name)]);
	}



	/**
	 * Magic unset, do not call directly
	 * @param string name
	 */
	public function __unset($name)
	{
		$this->updating();
		unset($this->values[$this->fixName($name)]);
	}



	/**
	 * Is value set?
	 * @param string name
	 * @return bool
	 */
	public function hasValue($name)
	{
		return array_key_exists($this->fixName($name), $this->values);
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="ArrayAccess">

	public function offsetExists($offset)
	{
		return $this->__isset($offset);
	}



	public function offsetGet($offset)
	{
		return $this->__get($offset);
	}



	public function offsetSet($offset, $value)
	{
		$this->__set($offset, $value);
	}



	public function offsetUnset($offset)
	{
		$this->__unset($offset);
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="IteratorAggregate">

	public function getIterator()
	{
		return new ArrayIterator($this->getValues());
	}

	// </editor-fold>
	
}