<?php

/**
 * Mapper
 *
 * @author Jan Marek
 * @license MIT
 */
class OrmionMapper extends Object {

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
	 * @return Config
	 */
	public function getConfig() {
		if (empty($this->config)) {
			$dir = Environment::getVariable("ormionConfigDir", APP_DIR . "/models/config");
			$filePath = $dir . "/" . $this->table . ".ini";
			
			if (file_exists($filePath)) {
				$this->config = Config::fromFile($filePath);
			} else {
				$this->config = $this->createConfig();
				$this->config->save($filePath);
			}
		}

		return $this->config;
	}

	/**
	 * Detects data types and keys from database
	 * @return Config
	 */
	protected function createConfig() {
		$tableInfo = $this->getDb()->getDatabaseInfo()->getTable($this->table);

		$arr = array();

		foreach ($tableInfo->getColumns() as $column) {
			$name = $column->getName();
			$arr["column"][$name]["isColumn"] = true;
			$arr["column"][$name]["type"] = $column->getType();
			$arr["column"][$name]["nullable"] = $column->isNullable();

			// form
//			$arr["form"][$name]["element"] = "text";
//			$arr["form"][$name]["label"] = $name;
		}

		foreach ($tableInfo->getPrimaryKey()->getColumns() as $column) {
			$name = $column->getName();
			$arr["key"][$name]["primary"] = true;
			$arr["key"][$name]["autoIncrement"] = $column->isAutoIncrement();
			
			// form
//			$arr["form"][$name]["element"] = "hidden";
//			unset($arr["form"][$name]["label"]);
		}

		return new Config($arr);
	}

	// Reflection

	/**
	 * Get column names
	 * @return array
	 */
	public function getColumnNames() {
		$arr = array();

		$columns = $this->getConfig()->get("column");
		foreach ($columns as $name => $column) {
			if ($column->isColumn) {
				$arr[] = $name;
			}
		}

		return $arr;
	}

	/**
	 * Get dibi type
	 * @param string $name column name
	 * @return string
	 */
	public function getColumnType($name) {
		$column = $this->getConfig()->get("column")->get($name);
		
		if (empty($column)) {
			return null;
		}
		
		return $column->get("type");
	}

	/**
	 * Is column nullable
	 * @param string $name
	 * @return bool
	 */
	public function isColumnNullable($name) {
		$cfg = $this->getConfig();
		$cols = $cfg->get("column");
		$col = $cols->get($name);
		if (empty($col)) return true;
		return $col->get("nullable");
	}

	/**
	 * Is primary key auto increment
	 * @return bool
	 */
	public function isPrimaryAutoIncrement() {
		foreach ($this->getConfig()->get("key") as $key) {
			if ($key->primary) {
				return (bool) $key->autoIncrement;
			}
		}

		return false;
	}

	/**
	 * Get primary column names
	 * @return array
	 */
	public function getPrimaryColumns() {
		$arr = array();

		foreach ($this->getConfig()->get("key") as $name => $key) {
			if ($key->primary) {
				$arr[] = $name;
			}
		}

		return $arr;
	}

	/**
	 * Get first primary column name
	 * @return string
	 */
	public function getPrimaryColumn() {
		foreach ($this->getConfig()->get("key") as $name => $key) {
			if ($key->primary) {
				return $name;
			}
		}

		return null;
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
	 * @return OrmionRecordSet
	 */
	public function findAll($conditions = array()) {
		$fluent = $this->createFindFluent()->where($conditions);
		return new OrmionRecordSet($fluent, $this->rowClass);
	}

	/**
	 * Find one result
	 * @param array|int $conditions
	 * @return OrmionRecord|false
	 */
	public function find($conditions = array()) {
		if (!is_array($conditions)) {
			$conditions = array(
				$this->getPrimaryColumn() => $conditions,
			);
		}

		try {
			$fluent = $this->createFindFluent()->where($conditions)->limit(1);
			Ormion::log($fluent);
			$res = $fluent->execute()->setRowClass($this->rowClass)->fetch();
		} catch (Exception $e) {
			throw new ModelException("Find query failed. " . $e->getMessage(), $e->getCode(), $e);
		}

		if ($res) {
			$res->setState(OrmionRecord::STATE_EXISTING)->clearModified();
		}

		return $res;
	}


	/**
	 * Inser record into database
	 * @param OrmionRecord $record
	 */
	public function insert(OrmionRecord $record) {
		try {
			$record->onBeforeInsert($record);

			$values = array();

			foreach ($this->getColumnNames() as $column) {
				if ($record->hasValue($column)) {
					$values[$column . "%" . $this->getColumnType($column)] = $record->$column;
				}
			}

			// do query
			$fluent = $this->getDb()->insert($this->table, $values);
			Ormion::log($fluent);
			$fluent->execute();

			// fill auto increment primary key
			if ($this->isPrimaryAutoIncrement()) {
				$record[$this->getPrimaryColumn()] = $this->getDb()->getInsertId();
			}

			// set state
			$record->setState(OrmionRecord::STATE_EXISTING);
			$record->clearModified();

			$record->onAfterInsert($record);

		} catch (Exception $e) {
			throw new ModelException("Insert failed. " . $e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Update record
	 * @param OrmionRecord $record
	 */
	public function update(OrmionRecord $record) {
		try {
			$record->onBeforeUpdate($record);

			$columns = array_intersect($this->getColumnNames(), $record->getModified());

			foreach ($columns as $column) {
				$values[$column . "%" . $this->getColumnType($column)] = $record->$column;
			}

			if (isset($values)) {
				$fluent = $this->getDb()
					->update($this->table, $values)
					->where($record->getData($this->getPrimaryColumns()));

				Ormion::log($fluent);

				$fluent->execute();

				$record->clearModified();
			}

			$record->onAfterUpdate($record);

		} catch (Exception $e) {
			throw new ModelException("Update failed. " . $e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Delete record
	 * @param OrmionRecord $record
	 */
	public function delete(OrmionRecord $record) {
		try {
			$record->onBeforeDelete($record);

			$fluent = $this->getDb()
				->delete($this->table)
				->where($record->getData($this->getPrimaryColumns()));

			Ormion::log($fluent);

			$fluent->execute();

			// set state
			$record->setState(OrmionRecord::STATE_DELETED);

			$record->onAfterDelete($this);

		} catch (Exception $e) {
			throw new ModelException("Delete failed. " . $e->getMessage(), $e->getCode(), $e);
		}
	}

}