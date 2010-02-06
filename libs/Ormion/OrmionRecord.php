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
		$this->init();

		if ($data !== null) {
			if (is_scalar($data)) {
				$pk = static::getMapper()->getConfig()->getPrimaryColumn();
				$this->$pk = $data;
			} else {
				$this->setData($data);
			}
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
	 * User initialization, can be overriden
	 */
	protected function init() {

	}

	public static function getTable() {
		if (empty(static::$table)) {
			throw new InvalidStateException("Table name is not set.");
		}

		return static::$table;
	}

	public static function getMapperClass() {
		return static::$mapperClass;
	}

	/**
	 * Get mapper
	 * @return IMapper
	 */
	public static function getMapper() {
		// TODO: non static?
		// TODO: method getConfig?
		return Ormion::getMapper(get_called_class());
	}

	/**
	 * Add record behavior
	 * @param IOrmionBehavior $behavior
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

		return static::getMapper()->detectState($this);
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
		return OrmionForm::create(static::getMapper()->getConfig()->getForm($name));
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
	 * @return OrmionCollection
	 */
	public static function findAll($conditions = array()) {
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


	public function loadValues($values = null) {
		static::getMapper()->loadValues($this, $values);
		return $this;
	}

	public function lazyLoadValues($values = null) {
		static::getMapper()->lazyLoadValues($this, $values);
		return $this;
	}

	/**
	 * Convert value
	 * @param mixed $value
	 * @param string $type
	 * @return mixed
	 */
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

	public function __set($name, $value) {
		$name = $this->fixColumnName($name);
		$config = static::getMapper()->getConfig();
		$type = $config->getColumnType($name);

		if ($type && !($value === null && $config->isColumnNullable($name))) {
			$value = $this->convertValue($value, $type);
		}
		
		parent::__set($name, $value);
	}

}