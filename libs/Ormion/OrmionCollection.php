<?php

/**
 * Record set
 *
 * @author Jan Marek
 * @license MIT
 */
class OrmionCollection extends LazyArrayList {

	/** @var DibiFluent */
	private $fluent;

	
	/**
	 * Construct
	 * @param DibiFluent $fluent
	 * @param string $rowClass
	 */
	public function __construct(DibiFluent $fluent, $rowClass) {
		parent::__construct(null, $rowClass);
		$this->fluent = $fluent;
	}


	/**
	 * Load items
	 */
	protected function load() {
		$this->import($this->fetchAll());
	}


	/**
	 * Change DibiFluent
	 * @param string $name
	 * @param array $args
	 * @return OrmionCollection
	 */
	public function __call($name, $args) {
		call_user_func_array(array($this->fluent, $name), $args);
		$this->setLoaded(false);
		return $this;
	}


	/**
	 * Get DibiDataSource object
	 * @return DibiDataSource
	 */
	public function toDataSource() {
		return $this->fluent->toDataSource();
	}


	/**
	 * Get DibiFluent object
	 * @return DibiDataSource
	 */
	public function toFluent() {
		return $this->fluent;
	}


	/**
	 * Count items in collection
	 * @return int
	 */
	public function count() {
		return $this->isLoaded() ? parent::count() : $this->fluent->count();
	}


	/**
	 * Freeze collection
	 */
	public function freeze() {
		foreach ($this as &$item) {
			$item->freeze();
		}
		
		parent::freeze();
	}


	/**
	 * Execute fluent
	 * @param DibiFluent $fluent
	 * @return DibiResult
	 * @throws ModelException
	 */
	private function runQuery(DibiFluent $fluent) {
		try {
			return $fluent->execute();
		} catch (DibiDriverException $e) {
			throw new ModelException("Query failed. " . $e->getMessage(), $e->getCode(), $e);
		}
	}


	/**
	 * Fetches all records from table.
	 * @param int $limit offset
	 * @param int $offset limit
	 * @return array
	 */
	public function fetchAll($limit = null, $offset = null) {
		$fluent = clone $this->fluent;

		if ($limit) {
			$fluent->limit($limit);
			if ($offset) $fluent->offset($offset);
		}

		$res = $this->runQuery($fluent)
			->setRowClass($this->getItemType())
			->fetchAll();

		foreach ($res as &$row) {
			$row->setState(IRecord::STATE_EXISTING)->clearModified();
		}

		return $res;
	}


	/**
	 * Fetches all records from table and returns associative tree.
	 * @param string $assoc associative descriptor
	 * @return array
	 */
	public function fetchAssoc($assoc) {
		$arr = $this->runQuery($this->fluent)
			->setRowClass($this->getItemType())
			->fetchAssoc($assoc);

		array_walk_recursive($arr, function ($item) {
			$item->setState(IRecord::STATE_EXISTING)->clearModified();
		});
		
		return $arr;
	}

	/**
	 * Fetches all records like $key => $value pairs.
	 * @param string $key associative key
	 * @param string $value value
	 * @return array
	 */
	public function fetchPairs($key, $value) {
		$fluent = clone $this->fluent;

		$res = $this->runQuery(
			$fluent->removeClause("select")->select("[$key], [$value]")
		);

		$class = $this->getItemType();
		$config = $class::getConfig();

		$res->setType($key, $config->getType($key));
		$res->setType($value, $config->getType($value));

		return $res->fetchPairs($key, $value);
	}


	/**
	 * Fetches one column in all records
	 * @param string $column column name
	 * @return array
	 */
	public function fetchColumn($column) {
		$fluent = clone $this->fluent;

		$res = $this->runQuery($fluent->removeClause("select")->select("[$column]"));

		$class = $this->getItemType();
		$res->setType($column, $class::getConfig()->getType($column));

		return $res->fetchPairs();
	}


	public function fetchSingle() {
		throw new NotImplementedException;
	}


	public function fetch() {
		throw new NotImplementedException;
	}

}