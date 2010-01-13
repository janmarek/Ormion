<?php

/**
 * OrmionRecord
 *
 * @author Jan Marek
 */
class OrmionRecord extends FreezableObject implements ArrayAccess {

	const STATE_NEW = 1;
	const STATE_EXISTING = 2;
	const STATE_DELETED = 3;

	// class variables

	/** @var string */
	protected static $mapperClass = "OrmionMapper";

	/** @var string */
	protected static $tableName;

	/** @var array */
	protected static $mappers;

	// events

	/** @var array */
	public $onBeforeInsert;

	/** @var array */
	public $onAfterInsert;

	/** @var array */
	public $onBeforeUpdate;

	/** @var array */
	public $onAfterUpdate;

	/** @var array */
	public $onBeforeDelete;

	/** @var array */
	public $onAfterDelete;

	// callbacks

	/** @var array */
	private $setters;

	/** @var array */
	private $getters;

	// data

	/** @var string */
	private $data;

	/** @var array */
	private $defaults;

	/** @var array */
	private $aliases;

	/** @var array */
	private $modified = array();

	// state

	/** @var int */
	private $state = self::STATE_NEW;

	/**
	 * Constructor
	 * @param array $data
	 */
	public function __construct($data = null) {
		$this->init();

		if ($data !== null) {
			$this->setData($data);
			$this->clearModified();
		}
	}

	/**
	 * Create instance
	 * @param array $data
	 * @return Ormion
	 */
	public static function create($data = null) {
		return new static($data);
	}

	/**
	 * User initialization, can be overriden
	 */
	protected function init() {

	}

	/**
	 * Get mapper
	 * @return OrmionMapper
	 */
	protected static function getMapper() {
		if (empty(static::$table)) {
			throw new InvalidStateException("Unable to create mapper, table name is not set.");
		}

		if (empty(self::$mappers[static::$table])) {
			$class = static::$mapperClass;
			self::$mappers[static::$table] = new $class(static::$table, get_called_class());
		}

		return self::$mappers[static::$table];
	}

	/**
	 * Set callback for setting values
	 * @param string $name
	 * @param callback $callback php callback
	 * @return Ormion
	 */
	public function registerSetter($name, $callback) {
		if (!is_callable($callback)) {
			throw new InvalidArgumentException("Argument is not callable.");
		}

		$this->setters[$name] = $callback;
		return $this;
	}

	/**
	 * Set callback for getting values
	 * @param string $name
	 * @param callback $callback php callback
	 * @return Ormion
	 */
	public function registerGetter($name, $callback) {
		if (!is_callable($callback)) {
			throw new InvalidArgumentException("Argument is not callable.");
		}

		$this->getters[$name] = $callback;
		return $this;
	}

	/**
	 * Find record
	 * @param mixed $conditions
	 * @return OrmionRecord
	 */
	public static function find($conditions = array()) {
		return static::getMapper()->find($conditions);
	}

	/**
	 * Find all records
	 * @param array $conditions
	 * @return OrmionDataSet
	 */
	public static function findAll($conditions = array()) {
		return static::getMapper()->findAll($conditions);
	}

	/**
	 * Save record
	 * @return OrmionRecord
	 */
	public function save() {
		$this->updating();

		switch ($this->getState()) {
			case self::STATE_DELETED:
				throw new ModelException("You can't save deleted object.");
				break;

			case self::STATE_EXISTING:
				static::getMapper()->update($this);
				break;

			case self::STATE_NEW:
				static::getMapper()->insert($this);
				break;
		}

		return $this;
	}

	/**
	 * Delete record
	 * @return OrmionRecord
	 */
	public function delete() {
		static::getMapper()->delete($this);
		return $this;
	}

	/**
	 * Set all values as unmodified
	 */
	public function clearModified() {
		$this->modified = array();
	}

	/**
	 * Get modified column names
	 * @return array
	 */
	public function getModified() {
		return array_keys($this->modified);
	}

	/**
	 * Get state
	 * @return int
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * Set state
	 * @param int $state
	 * @return OrmionRecord
	 */
	public function setState($state) {
		$this->state = $state;
		return $this;
	}

	/**
	 * Set default value
	 * @param string $name
	 * @param mixed $value
	 * @return OrmionRecord
	 */
	protected function setDefaultValue($name, $value) {
		$this->defaults[$name] = $value;
		return $this;
	}

	/**
	 * Set alias
	 * @param string $alias
	 * @param string $name
	 * @return OrmionRecord
	 */
	protected function setAlias($alias, $name) {
		$this->aliases[$alias] = $name;
		return $this;
	}

	/**
	 * Multiple getter
	 * @param array $columns
	 * @return array
	 */
	public function getData($columns = null) {
		if ($columns === null) {
			$columns = array_unique(
				array_merge(
					array_keys($this->data),
					array_keys($this->defaults)
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
	 * @param array $data
	 * @return OrmionRecord
	 */
	public function setData($data) {
		foreach ($data as $key => $value) {
			$this->__set($key, $value);
		}

		return $this;
	}

	/**
	 * Normalize column name (replace alias)
	 * @param string $name
	 * @return string
	 */
	protected function fixColumnName($name) {
		if (isset($this->aliases[$name])) {
			return $this->aliases[$name];
		}

		return $name;
	}

	/**
	 * Magic getter for field value, do not call directly
	 * @param string $name
	 * @return mixed
	 */
	public function & __get($name) {
		$name = $this->fixColumnName($name);

		if (isset($this->getters[$name])) {
			return call_user_func($this->getters[$name], $data, $name);
		} else {
			if (array_key_exists($name, $this->data)) {
				return $this->data[$name];
			} elseif (array_key_exists($name, $this->defaults)) {
				return $this->defaults[$name];
			} else {
				throw new MemberAccessException("Value '$name' was not set.");
			}
		}
	}

	protected function convertValue($value, $type) {
		switch ($type) {
			case dibi::TEXT:
				return (string) $value;

			case dibi::INTEGER:
				return (int) $value;

			case dibi::FLOAT:
				return (float) $value;

			case dibi::DATE:
			case dibi::DATETIME:
				// '', NULL, FALSE, '0000-00-00', ...
				if ((int) $value === 0) {
					return null;

				// return timestamp
				} else {
					// TODO Datetime
					return is_numeric($value) ? (int) $value : strtotime($value);
				}

			case dibi::BOOL:
				return ((bool) $value) && $value !== 'f' && $value !== 'F';

			default:
				return $value;
		}
	}

	/**
	 * Magic setter for field value, do not call directly
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value) {
		$this->updating();

		$name = $this->fixColumnName($name);

		if (isset($this->setters[$name])) {
			call_user_func($this->setters[$name], $data, $name, $value);
		} else {
			$this->data[$name] = $this->convertValue($value, static::getMapper()->getColumnType($name));
			$this->modified[$name] = true;
		}
	}

	/**
	 * Magic isset, do not call directly
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name) {
		return isset($this->data[$this->fixColumnName($name)]);
	}

	/**
	 * Magic unset, do not call directly
	 * @param string $name
	 */
	public function __unset($name) {
		$this->updating();
		unset($this->data[$this->fixColumnName($name)]);
	}

	/**
	 * Is value set?
	 * @param string $name
	 * @return bool
	 */
	public function hasValue($name) {
		return array_key_exists($this->fixColumnName($name), $this->data);
	}

	// ArrayAccess

	public function offsetExists($offset) {
		$this->__isset($offset);
	}

	public function offsetGet($offset) {
		return $this->__get($offset);
	}

	public function offsetSet($offset, $value) {
		$this->__set($offset, $value);
	}

	public function offsetUnset($offset) {
		$this->__unset($offset);
	}

}