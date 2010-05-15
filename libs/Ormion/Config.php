<?php

namespace Ormion;

use Nette\Config\ConfigAdapterIni;
use DibiTableInfo;
use dibi;

/**
 * Ormion config
 *
 * @author Jan Marek
 * @license MIT
 */
class Config extends \Nette\Object {

	/** @var array */
	private $data;

	
	/**
	 * Constructor
	 * @param array $data
	 */
	public function __construct($data) {
		$this->data = $data;
	}


	/**
	 * Create Config from database table info
	 * @return Config
	 */
	public static function fromTableInfo(DibiTableInfo $tableInfo) {
		// columns
		foreach ($tableInfo->getColumns() as $column) {
			$name = $column->getName();
			$type = $column->getType();

          	if ($type === dibi::INTEGER && $column->getSize() === 1) {
				$type = dibi::BOOL;
			}

			$arr["column"][$name]["type"] = $type;

			if ($type === dibi::TEXT && $column->getNativeType() === "VARCHAR") {
				$arr["column"][$name]["size"] = $column->getSize();
           	}

			if ($column->isNullable()) {
				$arr["column"][$name]["nullable"] = true;
			}
		}

		// keys
		foreach ($tableInfo->getPrimaryKey()->getColumns() as $column) {
			$name = $column->getName();
			$arr["key"][$name]["primary"] = true;
			$arr["key"][$name]["autoIncrement"] = $column->isAutoIncrement();
		}

		return new self($arr);
	}


	/**
	 * Get column
	 * @param string $name
	 * @return array
	 */
	private function getColumn($name) {
		return isset($this->data["column"][$name]) ? $this->data["column"][$name] : null;
	}


	/**
	 * Get column names
	 * @return array
	 */
	public function getColumns() { // getColumns ?
		$arr = array();

		foreach ($this->data["column"] as $name => $column) {
			if ($this->isColumn($name)) {
				$arr[] = $name;
			}
		}

		return $arr;
	}


	/**
	 * Is real column
	 * @param string $name column name
	 * @return bool
	 */
	public function isColumn($name) {
		return !(isset($this->data["column"][$name]["column"]) && $this->data["column"][$name]["column"] == false);
	}


	/**
	 * Get dibi type
	 * @param string $name column name
	 * @return string
	 */
	public function getType($name) {
		$column = $this->getColumn($name);
		return $column ? $column["type"] : null;
	}


	/**
	 * Is column nullable
	 * @param string $name
	 * @return bool
	 */
	public function isNullable($name) {
		$column = $this->getColumn($name);
		return isset($column["nullable"]) ? (bool) $column["nullable"] : false;
	}


	/**
	 * Is primary key auto increment
	 * @return bool
	 */
	public function isPrimaryAutoIncrement() {
		foreach ($this->data["key"] as $key) {
			if ($key["primary"]) {
				return (bool) $key["autoIncrement"];
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

		foreach ($this->data["key"] as $name => $key) {
			if ($key["primary"]) {
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
		foreach ($this->data["key"] as $name => $key) {
			if ($key["primary"]) {
				return $name;
			}
		}

		return null;
	}


	/**
	 * Get association config
	 * @return array
	 */
	public function getAssociations() {
		return isset($this->data["association"]) ? $this->data["association"] : array();
	}

}