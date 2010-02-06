<?php

/**
 * Ormion config
 *
 * @author Jan Marek
 */
class OrmionConfig extends Object {

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
	 * Save config to ini file
	 * @param string $file
	 * @return OrmionConfig
	 */
	public function save($file) {
		ConfigAdapterIni::save($this->data, $file);
		return $this;
	}

	/**
	 * Create config from ini file
	 * @param string $file
	 * @return OrmionConfig
	 */
	public static function fromFile($file) {
		return new self(ConfigAdapterIni::load($file));
	}

	/**
	 * Create OrmionConfig from database table info
	 * @return OrmionConfig
	 */
	public static function fromTableInfo(DibiTableInfo $tableInfo) {
		// TODO form jen nějaký defaultní, stejnak se musí před použitím upravit
		// TODO formuláře generovat volitelně
		// TODO generovat required

		$arr = array();

		foreach ($tableInfo->getColumns() as $column) {
			$name = $column->getName();
			$arr["column"][$name]["isColumn"] = true;
			$arr["column"][$name]["type"] = $column->getType();
			$arr["column"][$name]["nullable"] = $column->isNullable();

			// form
			$arr["form_modify"][$name]["type"] = "text";
			$arr["form_modify"][$name]["label"] = $name;
		}

		foreach ($tableInfo->getPrimaryKey()->getColumns() as $column) {
			$name = $column->getName();
			$arr["key"][$name]["primary"] = true;
			$arr["key"][$name]["autoIncrement"] = $column->isAutoIncrement();

			// form
			$arr["form_modify"][$name]["type"] = "hidden";
			unset($arr["form_modify"][$name]["label"]);
		}

		if (isset($arr["form_modify"])) {
			$arr["form_modify"]["s"] = array(
				"type" => "submit",
				"label" => "OK",
			);

			$arr["form_create"] = $arr["form_modify"];

			foreach ($arr["key"] as $name => $item) {
				unset($arr["form_create"][$name]);
			}
		}

		return new self($arr);
	}

	/**
	 * Get column names
	 * @return array
	 */
	public function getColumnNames() {
		$arr = array();

		foreach ($this->data["column"] as $name => $column) {
			if ($column["isColumn"]) {
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
		$column = $this->data["column"][$name];

		if (empty($column)) {
			return null;
		}

		return $column["type"];
	}

	/**
	 * Is column nullable
	 * @param string $name
	 * @return bool
	 */
	public function isColumnNullable($name) {
		return empty($this->data["column"][$name]) ? true : $this->data["column"][$name]["nullable"];
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

	public function getForm($name) {
		// TODO: exception

		return $this->data["form_$name"];
	}

}