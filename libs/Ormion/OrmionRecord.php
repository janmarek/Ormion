<?php

/**
 * Ormion record
 *
 * @author Jan Marek
 * @license MIT
 */
abstract class OrmionRecord extends OrmionStorage implements IRecord {

	/** @var string */
	protected static $mapperClass = "OrmionMapper";

	/** @var array */
	private static $mappers;

	/** @var string */
	protected static $table;

	/** @var int */
	private $state;

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


	/**
	 * Constructor
	 * @param array|int $data
	 */
	public function __construct($data = null) {
		if (is_scalar($data)) {
			parent::__construct();
			$this->{$this->getConfig()->getPrimaryColumn()} = $data;
		} else {
			parent::__construct($data);
		}
	}


	/**
	 * Create instance
	 * @param array $data
	 * @return OrmionRecord
	 */
	public static function create($data = null) {
		return new static($data);
	}


	/**
	 * Get mapper
	 * @return IMapper
	 */
	public static function getMapper() {
		$class = get_called_class();

		if (empty(self::$mappers[$class])) {
			self::$mappers[$class] = static::createMapper();
		}

		return self::$mappers[$class];
	}


	/**
	 * Mapper factory
	 * @return IMapper
	 */
	public static function createMapper() {
		if (empty(static::$table)) {
			throw new InvalidStateException("Table name is not set.");
		}

		$cls = static::$mapperClass;
		return new $cls(static::$table, get_called_class());
	}


	/**
	 * Get config
	 * @return OrmionConfig
	 */
	public static function getConfig() {
		return static::getMapper()->getConfig();
	}
	

	/**
	 * Add record behavior
	 * @param IBehavior $behavior
	 * @return OrmionRecord
	 */
	public function addBehavior(IBehavior $behavior) {
		$behavior->setUp($this);
		return $this;
	}


	/**
	 * Get state
	 * @return int
	 */
	public function getState() {
		if (isset($this->state)) {
			return $this->state;
		}

		$config = $this->getConfig();

		if ($config->isPrimaryAutoincrement()) {
			if (parent::hasValue($config->getPrimaryColumn())) {
				return IRecord::STATE_EXISTING;
			} else {
				return IRecord::STATE_NEW;
			}

		} else {
			// TODO check in db
			return IRecord::STATE_NEW;
		}
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
	 * Create form from config
	 * @param string $name
	 * @return Form
	 */
	public static function createForm($name) {
		return OrmionForm::create(static::getConfig()->getForm($name));
	}


	/**
	 * Get primary key value
	 * @return mixed
	 */
	public function getPrimary() {
		$primaryColumns = $this->getConfig()->getPrimaryColumns();
		if (count($primaryColumns) == 1) {
			return $this->{$primaryColumns[0]};
		} else {
			$arr = array();
			foreach ($primaryColumns as $column) {
				$arr[$column] = $this->$column;
			}
			return $arr;
		}
	}


	/**
	 * Find record
	 * @param mixed $conditions
	 * @return OrmionRecord
	 */
	public static function find($conditions = null) {
		return static::getMapper()->find($conditions);
	}


	/**
	 * Find all records
	 * @param array $conditions
	 * @return OrmionCollection
	 */
	public static function findAll($conditions = null) {
		return static::getMapper()->findAll($conditions);
	}


	/**
	 * Magic fetch.
	 * - $row = $model->fetchByUrl('about-us');
	 * - $arr = $model->fetchAllByCategoryIdAndVisibility(5, TRUE);
	 *
	 * @param  string
	 * @param  array
	 * @return OrmionRecord|false|OrmionCollection
	 */
	public static function __callStatic($name, $args) {
		if (strncmp($name, 'findBy', 6) === 0) { // single row
			$single = true;
			$name = substr($name, 6);

		} elseif (strncmp($name, 'findAllBy', 9) === 0) { // multi row
			$single = false;
			$name = substr($name, 9);

		} else {
			return parent::__callStatic($name, $args);
		}

		// ProductIdAndTitle -> array('product', 'title')
		$parts = explode('_and_', strtolower(preg_replace('#(.)(?=[A-Z])#', '$1_', $name)));

		if (count($parts) !== count($args)) {
			throw new InvalidArgumentException("Magic fetch expects " . count($parts) . " parameters, but " . count($args) . " was given.");
		}

		$conditions = array_combine($parts, $args);

		return $single ? static::find($conditions) : static::findAll($conditions);
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
				$this->getMapper()->update($this);
				break;

			case self::STATE_NEW:
				$this->getMapper()->insert($this);
				break;
		}

		return $this;
	}


	/**
	 * Delete record
	 * @return OrmionRecord
	 */
	public function delete() {
		$this->getMapper()->delete($this);
		return $this;
	}


	/**
	 * Load specified values into this record
	 * @param array $values value names, null means all values
	 * @return OrmionRecord
	 */
	public function loadValues($values = null) {
		$this->getMapper()->loadValues($this, $values);
		return $this;
	}


	/**
	 * Load not loaded values
	 * @param array $values
	 * @return OrmionRecord
	 */
	public function lazyLoadValues($values = null) {
		if ($values === null) {
			$values = $this->getConfig()->getColumns();
		}

		foreach ($values as $value) {
			if (!parent::hasValue($value)) {
				$missing[] = $value;
			}
		}

		if (isset($missing)) {
			$this->loadValues($missing);
		}

		return $this;
	}


	/**
	 * Multiple getter
	 * @param array $columns
	 * @return array
	 */
	public function getValues($columns = null) {
		$this->lazyLoadValues($columns);
		return parent::getValues($columns);
	}

	
	/**
	 * Convert value
	 * @param mixed $value
	 * @param string $type
	 * @param bool $nullable
	 * @return mixed
	 */
	protected function convertValue($value, $type, $nullable = true) {
		if ($nullable && $value === null) {
			return null;
		}

		switch ($type) {
			case dibi::TEXT:
				return (string) $value;

			case dibi::INTEGER:
				return (int) $value;

			case dibi::FLOAT:
				return (float) $value;

			case dibi::DATE:
			case dibi::DATETIME:
				if ($value instanceof DateTime) {
					return $value;
				} elseif ((int) $value === 0) { // '', NULL, FALSE, '0000-00-00', ...
					return null;
				} else {
					return new DateTime(is_numeric($value) ? date('Y-m-d H:i:s', $value) : $value);
				}

			case dibi::BOOL:
				return ((bool) $value) && $value !== 'f' && $value !== 'F';

			default:
				return $value;
		}
	}


	/**
	 * Magic setter (with converting values)
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value) {
		$name = $this->fixName($name);
		$config = $this->getConfig();
		$value = $this->convertValue($value, $config->getType($name), $config->isNullable($name));
		parent::__set($name, $value);
	}


	/**
	 * Magic getter (with lazy loading)
	 * @param string $name
	 * @return mixed
	 */
	public function & __get($name) {
		$name = $this->fixName($name);

		if ($this->getState() === self::STATE_EXISTING && !parent::hasValue($name) && $this->getConfig()->isColumn($name)) {
			$this->lazyLoadValues();
		}

		return parent::__get($name);
	}


	/**
	 * Magic isset with lazy loading
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name) {
		$name = $this->fixName($name);

		if ($this->getState() === self::STATE_EXISTING && !parent::hasValue($name) && $this->getConfig()->isColumn($name)) {
			$this->lazyLoadValues();
		}

		return parent::__isset($name);
	}


	/**
	 * Has value with lazy loading
	 * @param string $name
	 * @return bool
	 */
	public function hasValue($name) {
		$name = $this->fixName($name);

		if ($this->getState() === self::STATE_EXISTING && !parent::hasValue($name) && $this->getConfig()->isColumn($name)) {
			$this->lazyLoadValues();
		}

		return parent::hasValue($name);
	}

}