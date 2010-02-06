<?php

/**
 * Ormion storage
 *
 * @author Jan Marek
 */
abstract class OrmionStorage extends FreezableObject implements ArrayAccess {

	/** @var ArrayObject */
	private $data;

	/** @var array */
	private $defaults = array();

	/** @var array */
	private $aliases = array();

	/** @var array */
	private $modified = array();

	/** @var array */
	private $setters;

	/** @var array */
	private $getters;

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
	 * Get data storage
	 * @return ArrayObject
	 */
	public function getStorage() {
		if (empty($this->data)) {
			$this->data = new ArrayObject(array());
		}

		return $this->data;
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
	 * Is value modified
	 * @param string $name
	 * @return bool
	 */
	public function isValueModified($name) {
		return isset($this->modified[$name]);
	}

	/**
	 * Set default value
	 * @param string $name
	 * @param mixed $value
	 * @return OrmionStorage
	 */
	public function setDefaultValue($name, $value) {
		$this->defaults[$name] = $value;
		return $this;
	}

	/**
	 * Set alias
	 * @param string $alias
	 * @param string $name
	 * @return OrmionStorage
	 */
	public function setAlias($alias, $name) {
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
					array_keys($this->getStorage()->getArrayCopy()),
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
	 * @return OrmionStorage
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

		$data = $this->getStorage();

		if (isset($this->getters[$name])) {
			$ret = call_user_func($this->getters[$name], $this, $name);
			return $ret;
		} else {
			if (!array_key_exists($name, $data) && $this->getState() === self::STATE_EXISTING) {
				// TODO: prevence proti znovunačítání (při neúspěchu) - stačí MemberAccess?
				$this->lazyLoadValues();
			}

			if (array_key_exists($name, $data)) {
				$ret = $data[$name];
				return $ret;
			} elseif (array_key_exists($name, $this->defaults)) {
				return $this->defaults[$name];
			} else {
				throw new MemberAccessException("Value '$name' was not set.");
			}
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
		$data = $this->getStorage();

		if (isset($this->setters[$name])) {
			// TODO modified i v tomto případě? asi jo..
			call_user_func($this->setters[$name], $this, $name, $value);
		} else {
			$data[$name] = $value;
			$this->modified[$name] = true;
		}
	}

	/**
	 * Magic isset, do not call directly
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name) {
		// TODO: lazy loading?
		// callbackované záležitostě? !

		$data = $this->getStorage();
		return isset($data[$this->fixColumnName($name)]);
	}

	/**
	 * Magic unset, do not call directly
	 * @param string $name
	 */
	public function __unset($name) {
		$this->updating();
		$data = $this->getStorage();
		unset($data[$this->fixColumnName($name)]);
	}

	/**
	 * Is value set?
	 * @param string $name
	 * @return bool
	 */
	public function hasValue($name) {
		// TODO: lazy loading?
		// TODO: brát defaults?
		// TODO: přidělat isValueLoaded nebo tak něco?

		$data = $this->getStorage()->getArrayCopy();
		return array_key_exists($this->fixColumnName($name), $data) || isset($this->defaults[$name]);
	}

	// ArrayAccess

	public function offsetExists($offset) {
		return $this->__isset($offset);
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