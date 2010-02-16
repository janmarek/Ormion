<?php

/**
 * Mapper
 *
 * @author Jan Marek
 * @license MIT
 */
class OrmionMapper extends Object implements IMapper {

	/** @var string */
	protected $dibiConnectionName = Ormion::DEFAULT_CONNECTION_NAME;

	/** @var string */
	protected $table;

	/** @var string */
	private $rowClass;

	/** @var Config */
	private $config;

	
	/**
	 * Construct mapper
	 * @param string $table table name
	 * @param string $rowClass ormion record class name
	 */
	public function __construct($table, $rowClass) {
		$this->table = $table;
		$this->rowClass = $rowClass;
	}


	/**
	 * Get table name
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}


	/**
	 * Get row class name
	 * @return string
	 */
	public function getRowClass() {
		return $this->rowClass;
	}


	/**
	 * Get dibi connection
	 * @return DibiConnection
	 */
	public function getDb() {
		return dibi::getConnection($this->dibiConnectionName);
	}


	/**
	 * Get table config
	 * @return OrmionConfig
	 */
	public function getConfig() {
		if (empty($this->config)) {
			// file path
			$dir = Environment::getVariable("ormionConfigDir", APP_DIR . "/models/config");
			$filePath = $dir . "/" . $this->table . ".ini";

			// existing file
			if (file_exists($filePath)) {
				$this->config = OrmionConfig::fromFile($filePath);

			// create config
			} else {
				$tableInfo = $this->getDb()->getDatabaseInfo()->getTable($this->table);
				$this->config = OrmionConfig::fromTableInfo($tableInfo);
				$this->config->save($filePath);
			}
		}

		return $this->config;
	}


	/**
	 * Detect record state
	 * @param IRecord $record
	 * @return int
	 */
	public function detectState(IRecord $record) {
		$config = $this->getConfig();

		if ($config->isPrimaryAutoincrement()) {
			if (isset($record[$config->getPrimaryColumn()])) {
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
	 * Create base DibiFluent for find
	 * @return DibiFluent
	 */
	protected function createFindFluent() {
		return $this->getDb()->select("*")->from($this->table);
	}


	/**
	 * Find all results
	 * @param array $conditions
	 * @return OrmionCollection
	 */
	public function findAll($conditions = array()) {
		$fluent = $this->createFindFluent()->where($conditions);
		return new OrmionCollection($fluent, $this->rowClass);
	}


	/**
	 * Find one result
	 * @param array|int $conditions
	 * @return IRecord|false
	 */
	public function find($conditions = array()) {
		if (!is_array($conditions)) {
			$conditions = array(
				$this->getConfig()->getPrimaryColumn() => $conditions,
			);
		}

		try {
			$fluent = $this->createFindFluent()->where($conditions)->limit(1);
			$res = $fluent->execute()->setRowClass($this->rowClass)->fetch();
		} catch (Exception $e) {
			throw new ModelException("Find query failed. " . $e->getMessage(), $e->getCode(), $e);
		}

		if ($res) {
			$res->setState(IRecord::STATE_EXISTING)->clearModified();
		}

		return $res;
	}

	
	/**
	 * Load values into record
	 * @param IRecord $record
	 * @param array $values value names
	 */
	public function loadValues(IRecord $record, $values = null) {
		// TODO: ModelException místo DibiDriverException

		try {
			$key = $record->getValues($this->getConfig()->getPrimaryColumns());
		} catch (MemberAccessException $e) {
			throw new InvalidStateException("Key was not set.", null, $e);
		}

		$fluent = $this->createFindFluent();

		if ($values !== null) {
			$fluent->select(false)->select($values);
		}

		$fluent->where($key);

		foreach ($fluent->fetch() as $key => $val) {
			$record->$key = $val;
		}
	}


	/**
	 * Inser record into database
	 * @param IRecord $record
	 */
	public function insert(IRecord $record) {
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

			$record->onAfterInsert($record);

		} catch (Exception $e) {
			throw new ModelException("Insert failed. " . $e->getMessage(), $e->getCode(), $e);
		}
	}


	/**
	 * Update record
	 * @param IRecord $record
	 */
	public function update(IRecord $record) {
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

			$record->onAfterUpdate($record);

		} catch (Exception $e) {
			throw new ModelException("Update failed. " . $e->getMessage(), $e->getCode(), $e);
		}
	}


	/**
	 * Delete record
	 * @param IRecord $record
	 */
	public function delete(IRecord $record) {
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
			throw new ModelException("Delete failed. " . $e->getMessage(), $e->getCode(), $e);
		}
	}

}