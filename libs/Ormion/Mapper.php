<?php

namespace Ormion;

use Nette\Environment, Nette\Reflection\ClassReflection;
use dibi;
use Ormion\Association\IAssociation;

/**
 * Mapper
 *
 * @author Jan Marek
 * @license MIT
 */
class Mapper extends \Nette\Object implements IMapper
{
	// <editor-fold defaultstate="collapsed" desc="variables">

	/** @var string */
	protected $dibiConnectionName = Ormion::DEFAULT_CONNECTION_NAME;

	/** @var string */
	protected $table;

	/** @var string */
	private $rowClass;

	/** @var Config */
	private $config;

	/** @var array */
	private $associations = array();

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="construct">

	/**
	 * Construct mapper
	 * @param string table name
	 * @param string ormion record class name
	 */
	public function __construct($table, $rowClass)
	{
		$this->table = $table;
		$this->rowClass = $rowClass;
		$this->loadAssociations();
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getters">

	/**
	 * Get table name
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}



	/**
	 * Get row class name
	 * @return string
	 */
	public function getRowClass()
	{
		return $this->rowClass;
	}



	/**
	 * Get dibi connection
	 * @return DibiConnection
	 */
	public function getDb()
	{
		return dibi::getConnection($this->dibiConnectionName);
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="config">

	/**
	 * Get table config
	 * @return Config
	 */
	public function getConfig()
	{
		if (empty($this->config)) {
			$cacheKey = get_class($this) . "-" . $this->table . "-" . $this->rowClass;
			$cache = Environment::getCache("Ormion");

			if (isset($cache[$cacheKey])) {
				$this->config = $cache[$cacheKey];
			} else {
				$tableInfo = $this->getDb()->getDatabaseInfo()->getTable($this->table);
				$this->config = Config::fromTableInfo($tableInfo);
				$cache[$cacheKey] = $this->config;
			}
		}

		return $this->config;
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="associations">

	/**
	 * Load associations
	 */
	protected function loadAssociations()
	{
		$annotations = ClassReflection::from($this->rowClass)->getAnnotations();

		foreach ($annotations as $k => $v) {
			if ($v[0] instanceof \Ormion\Association\IAssociation) {
				foreach ($v as $association) {
					$association->setMapper($this);
					$this->associations[$association->getName()] = $association;
				}
			}
		}
	}



	/**
	 * Has association?
	 * @param string name
	 * @return bool
	 */
	public function hasAssociation($name)
	{
		return isset($this->associations[$name]);
	}



	/**
	 * Get association
	 * @param string name
	 * @return IAssociation
	 */
	public function getAssociation($name)
	{
		return $this->associations[$name];
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="finders">

	/**
	 * Create base DibiFluent for find
	 * @return DibiFluent
	 */
	protected function createFindFluent()
	{
		return $this->getDb()->select("*")->from($this->table);
	}



	/**
	 * Find all results
	 * @param array conditions
	 * @return Collection
	 */
	public function findAll($conditions = null)
	{
		$fluent = $this->createFindFluent();
		if ($conditions)
			$fluent->where($conditions);
		return new Collection($fluent, $this->rowClass);
	}



	/**
	 * Find one result
	 * @param array|int conditions
	 * @return IRecord|false
	 */
	public function find($conditions = null)
	{
		$fluent = $this->createFindFluent();

		if (is_scalar($conditions)) {
			$fluent->where(array(
				$this->getConfig()->getPrimaryColumn() => $conditions,
			));
		} elseif (is_array($conditions)) {
			$fluent->where($conditions);
		}

		try {
			$res = $fluent->limit(1)->execute()->setRowClass($this->rowClass)->fetch();
		} catch (\Exception $e) {
			throw new \ModelException("Find query failed. " . $e->getMessage(), $e->getCode(), $e);
		}

		if ($res) {
			$res->setState(IRecord::STATE_EXISTING)->clearModified();
		}

		return $res;
	}



	/**
	 * Load values into record
	 * @param IRecord record
	 * @param array value names
	 */
	public function loadValues(IRecord $record, $values = null)
	{
		try {
			$key = $record->getValues($this->getConfig()->getPrimaryColumns());
		} catch (\MemberAccessException $e) {
			throw new \InvalidStateException("Key was not set.", null, $e);
		}

		$fluent = $this->createFindFluent();

		if ($values !== null) {
			$fluent->select(false)->select($values);
		}

		$fluent->where($key);

		try {
			$row = $fluent->fetch();
		} catch (\DibiDriverException $e) {
			throw new \ModelException("Unknown columns. " . $e->getMessage(), $e->getCode(), $e);
		}

		foreach ($row as $key => $val) {
			$record->$key = $val;
		}
	}

	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="record manipulation">

	/**
	 * Inser record into database
	 * @param IRecord record
	 */
	public function insert(IRecord $record)
	{
		try {
			$record->onBeforeInsert($record);

			$values = array();

			$config = $this->getConfig();

			foreach ($config->getColumns() as $column) {
				if ($record->hasValue($column)) {
					$values[$column . "%" . $config->getType($column)] = $record->$column;
				}
			}

			// do query
			$this->getDb()->insert($this->table, $values)->execute();

			// fill auto increment primary key
			if ($config->isPrimaryAutoIncrement()) {
				$record[$config->getPrimaryColumn()] = $this->getDb()->getInsertId();
			}

			// set state
			$record->setState(IRecord::STATE_EXISTING);
			$record->clearModified();

			foreach ($this->associations as $name => $association) {
				if ($record->isAssociationLoaded($name)) {
					$association->saveReferenced($record, $record->$name);
				}
			}

			$record->onAfterInsert($record);
		} catch (\Exception $e) {
			throw new \ModelException("Insert failed. " . $e->getMessage(), $e->getCode(), $e);
		}
	}



	/**
	 * Update record
	 * @param IRecord record
	 */
	public function update(IRecord $record)
	{
		try {
			$record->onBeforeUpdate($record);

			$config = $this->getConfig();
			$columns = array_intersect($config->getColumns(), $record->getModified());

			foreach ($columns as $column) {
				$values[$column . "%" . $config->getType($column)] = $record->$column;
			}

			if (isset($values)) {
				$this->getDb()
					->update($this->table, $values)
					->where($record->getValues($config->getPrimaryColumns()))
					->execute();

				$record->clearModified();
			}

			foreach ($this->associations as $name => $association) {
				if ($record->isAssociationLoaded($name)) {
					$association->saveReferenced($record, $record->$name);
				}
			}

			$record->onAfterUpdate($record);
		} catch (\Exception $e) {
			throw new \ModelException("Update failed. " . $e->getMessage(), $e->getCode(), $e);
		}
	}



	/**
	 * Delete record
	 * @param IRecord record
	 */
	public function delete(IRecord $record)
	{
		try {
			$record->onBeforeDelete($record);

			$this->getDb()
				->delete($this->table)
				->where($record->getValues($this->getConfig()->getPrimaryColumns()))
				->execute();

			// set state
			$record->setState(IRecord::STATE_DELETED);

			$record->onAfterDelete($record);
		} catch (Exception $e) {
			throw new \ModelException("Delete failed. " . $e->getMessage(), $e->getCode(), $e);
		}
	}

	// </editor-fold>

}