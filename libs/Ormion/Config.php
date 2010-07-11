<?php

namespace Ormion;

use dibi, DibiTableInfo;

/**
 * Ormion config
 *
 * @author Jan Marek
 * @license MIT
 */
class Config extends \Nette\Object
{
	/** @var array */
	private $data;



	/**
	 * Constructor
	 * @param array data
	 */
	public function __construct($data)
	{
		$this->data = $data;
	}



	/**
	 * Create Config from database table info
	 * @return Config
	 */
	public static function fromTableInfo(DibiTableInfo $tableInfo)
	{
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
	 * @param string name
	 * @return array
	 */
	private function getColumn($name)
	{
		return isset($this->data["column"][$name]) ? $this->data["column"][$name] : null;
	}



	/**
	 * Get column names
	 * @return array
	 */
	public function getColumns()
	{
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
	 * @param string column name
	 * @return bool
	 */
	public function isColumn($name)
	{
		return!(isset($this->data["column"][$name]["column"]) && $this->data["column"][$name]["column"] == false);
	}



	/**
	 * Get dibi type
	 * @param string column name
	 * @return string
	 */
	public function getType($name)
	{
		$column = $this->getColumn($name);
		return $column ? $column["type"] : null;
	}



	/**
	 * Get size
	 * @param string column name
	 * @return int|null
	 */
	public function getSize($name)
	{
		$column = $this->getColumn($name);
		return isset($column["size"]) ? $column["size"] : null;
	}



	/**
	 * Is column nullable
	 * @param string column name
	 * @return bool
	 */
	public function isNullable($name)
	{
		$column = $this->getColumn($name);
		return isset($column["nullable"]) ? (bool) $column["nullable"] : false;
	}



	/**
	 * Is primary key auto increment
	 * @return bool
	 */
	public function isPrimaryAutoIncrement()
	{
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
	public function getPrimaryColumns()
	{
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
	public function getPrimaryColumn()
	{
		foreach ($this->data["key"] as $name => $key) {
			if ($key["primary"]) {
				return $name;
			}
		}

		return null;
	}

}