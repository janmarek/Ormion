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
		try {
			$res = $this->fluent->execute()->setRowClass($this->getItemType())->fetchAll();
		} catch (Exception $e) {
			throw new ModelException("Find query failed. " . $e->getMessage(), $e->getCode(), $e);
		}

		foreach ($res as &$row) {
			$row->setState(IRecord::STATE_EXISTING)->clearModified();
		}

		$this->import($res);
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
		if ($this->isLoaded()) {
			return parent::count();
		} else {
			$fluent = clone $this->fluent;
			$fluent->removeClause("select")->select("count(*)");
			return (int) $fluent->fetchSingle();
		}
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
	
}