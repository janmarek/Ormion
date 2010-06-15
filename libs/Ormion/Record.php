<?php

namespace Ormion;

use dibi;
use Ormion\Behavior\IBehavior;
use DateTime;
use Nette\Forms\Form;

/**
 * Ormion record
 *
 * @author Jan Marek
 * @license MIT
 */
abstract class Record extends Storage implements IRecord
{
	// <editor-fold defaultstate="collapsed" desc="variables">

	/** @var string */
	protected static $mapperClass = 'Ormion\Mapper';

	/** @var array */
	private static $mappers;

	/** @var string */
	protected static $table;

	/** @var int */
	private $state;

	/** @var array */
	private $associationData = array();

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

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="construct">

	/**
	 * Constructor
	 * @param array|int data
	 */
	public function __construct($data = null)
	{
		if (is_scalar($data)) {
			parent::__construct();
			$this->{$this->getConfig()->getPrimaryColumn()} = $data;
		} else {
			parent::__construct(array_merge($this->getDefaultValues(), (array) $data));
		}
	}



	/**
	 * Create instance
	 * @param array data
	 * @return Record
	 */
	public static function create($data = null)
	{
		return new static($data);
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="mapper">

	/**
	 * Get mapper
	 * @return IMapper
	 */
	public static function getMapper()
	{
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
	public static function createMapper()
	{
		if (isset(static::$table)) {
			$table = static::$table;
		} elseif (static::getReflection()->getAnnotation("table") != null) {
			$table = static::getReflection()->getAnnotation("table");
		} else {
			throw new \InvalidStateException("Table name is not set.");
		}

		$cls = static::$mapperClass;
		return new $cls($table, get_called_class());
	}



	/**
	 * Get config
	 * @return Config
	 */
	public static function getConfig()
	{
		return static::getMapper()->getConfig();
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="behaviors">

	/**
	 * Add record behavior
	 * @param IBehavior behavior
	 * @return Record
	 */
	public function addBehavior(IBehavior $behavior)
	{
		$behavior->setUp($this);
		return $this;
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="validation">

	/**
	 * Is record valid?
	 * @return bool
	 */
	public function isValid()
	{
		return count($this->getRuleViolations()) === 0;
	}



	/**
	 * Get rule violations
	 * @return array
	 */
	public function getRuleViolations()
	{
		return array();
	}



	/**
	 * Add errors to form (form helper)
	 * @param Form form
	 */
	public function addErrorsToForm(Form $form)
	{
		foreach ($this->getRuleViolations() as $issue) {
			$name = $issue->getName();

			if ($name !== null && isset($form[$name])) {
				$form[$name]->addError($issue->getMessage());
			} else {
				$form->addError($issue->getMessage());
			}
		}
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="finders">

	/**
	 * Find record
	 * @param mixed conditions
	 * @return Record
	 */
	public static function find($conditions = null)
	{
		return static::getMapper()->find($conditions);
	}



	/**
	 * Find all records
	 * @param array conditions
	 * @return Collection
	 */
	public static function findAll($conditions = null)
	{
		return static::getMapper()->findAll($conditions);
	}



	/**
	 * Magic fetch.
	 * - $row = $model->fetchByUrl('about-us');
	 * - $arr = $model->fetchAllByCategoryIdAndVisibility(5, TRUE);
	 *
	 * @param  string
	 * @param  array
	 * @return Record|false|Collection
	 */
	public static function __callStatic($name, $args)
	{
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
			throw new \InvalidArgumentException("Magic fetch expects " . count($parts) . " parameters, but " . count($args) . " was given.");
		}

		$conditions = array_combine($parts, $args);

		return $single ? static::find($conditions) : static::findAll($conditions);
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="record manipulation">

	/**
	 * Save record
	 * @return Record
	 */
	public function save()
	{
		$this->updating();

		if (!$this->isValid()) {
			throw new \ModelException("Record is not valid and cannot be saved.");
		}

		switch ($this->getState()) {
			case self::STATE_DELETED:
				throw new \ModelException("You can't save deleted object.");
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
	 * @return Record
	 */
	public function delete()
	{
		$this->getMapper()->delete($this);
		return $this;
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="values loading">

	/**
	 * Load specified values into this record
	 * @param array value names, null means all values
	 * @return Record
	 */
	public function loadValues($values = null)
	{
		$this->getMapper()->loadValues($this, $values);
		return $this;
	}



	/**
	 * Load not loaded values
	 * @param array value names, null means all values
	 * @return Record
	 */
	public function lazyLoadValues($values = null)
	{
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

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="association">

	/**
	 * Is association loaded?
	 * @param string name
	 * @return bool
	 */
	public function isAssociationLoaded($name)
	{
		return array_key_exists($name, $this->associationData);
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="state">

	/**
	 * Get state
	 * @return int
	 */
	public function getState()
	{
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
	 * @param int state
	 * @return Record
	 */
	public function setState($state)
	{
		$this->state = $state;
		return $this;
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="values">

	/**
	 * Get primary key value
	 * @return mixed
	 */
	public function getPrimary()
	{
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
	 * Multiple getter
	 * @param array columns
	 * @return array
	 */
	public function getValues($columns = null)
	{
		$this->lazyLoadValues($columns);
		return parent::getValues($columns);
	}



	/**
	 * Convert value
	 * @param mixed value
	 * @param string type
	 * @param bool is nullable
	 * @return mixed
	 */
	protected function convertValue($value, $type, $nullable = true)
	{
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
	 * @param string name
	 * @param mixed value
	 */
	public function __set($name, $value)
	{
		$name = $this->fixName($name);

		$mapper = $this->getMapper();

		if ($mapper->hasAssociation($name)) {
			$mapper->getAssociation($name)->setReferenced($this, $value);
			$this->associationData[$name] = $value;
			return;
		}

		$config = $mapper->getConfig();
		$value = $this->convertValue($value, $config->getType($name), $config->isNullable($name));
		parent::__set($name, $value);
	}



	/**
	 * Magic getter (with lazy loading)
	 * @param string name
	 * @return mixed
	 */
	public function & __get($name)
	{
		$name = $this->fixName($name);

		if ($this->getMapper()->hasAssociation($name)) {
			if (!$this->isAssociationLoaded($name)) {
				$this->associationData[$name] = $this->getMapper()->getAssociation($name)->retrieveReferenced($this);
			}

			return $this->associationData[$name];
		}

		if ($this->getState() === self::STATE_EXISTING && !parent::hasValue($name) && $this->getConfig()->isColumn($name)) {
			$this->lazyLoadValues();
		}

		return parent::__get($name);
	}



	/**
	 * Magic isset with lazy loading
	 * @param string name
	 * @return bool
	 */
	public function __isset($name)
	{
		$name = $this->fixName($name);

		if ($this->getState() === self::STATE_EXISTING && !parent::hasValue($name) && $this->getConfig()->isColumn($name)) {
			$this->lazyLoadValues();
		}

		return parent::__isset($name);
	}



	/**
	 * Has value with lazy loading
	 * @param string name
	 * @return bool
	 */
	public function hasValue($name)
	{
		$name = $this->fixName($name);

		if ($this->getState() === self::STATE_EXISTING && !parent::hasValue($name) && $this->getConfig()->isColumn($name)) {
			$this->lazyLoadValues();
		}

		return parent::hasValue($name);
	}

	// </editor-fold>
	
}