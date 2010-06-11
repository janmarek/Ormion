<?php

require_once __DIR__ . "/../../../document_root/index.php";

use Ormion\Record;

/**
 * Test class for Collection
 *
 * @backupStaticAttributes disabled
 * @backupGlobals disabled
 */
class CollectionTest extends PHPUnit_Framework_TestCase {

	/** @var DibiConnection */
	private $db;

	/** @var Collection */
	private $object;

	protected function setUp() {
		$this->db = dibi::getConnection("ormion");
		$this->db->delete("pages")->execute();
		// id, name, description, text, created, allowed
		$this->db->query("insert into [pages]", array(
			"name" => "Clanek",
			"description" => "Popis",
			"text" => "Text",
			"visits" => 0,
			"allowed" => true,
		), array(
			"name" => "Article",
			"description" => "Description",
			"text" => "Text emericky.",
			"visits" => 5,
			"allowed" => false,
		), array(
			"name" => "Nepovolený článek",
			"description" => "Popis nepovoleného článku",
			"text" => "Dlouhý text. By byl delší než tento.",
			"visits" => 3,
			"allowed" => false,
		), array(
			"name" => "Jinačí článek",
			"description" => "Ryze alternativní popis",
			"text" => "Duchaplný text.",
			"visits" => 8,
			"allowed" => true,
		));

		$fluent = $this->db->select("*")->from("pages");
		$this->object = new Ormion\Collection($fluent, "Page");
	}

	protected function tearDown() {
		$this->db->delete("pages")->execute();
	}

	public function testGetSum() {
		$res = $this->object->getSum("visits");
		$this->assertType("float", $res);
		$this->assertEquals(16, $res);
		$this->setExpectedException("\ModelException");
		$this->object->getSum("nesmysl");
	}

	public function testGetAvg() {
		$res = $this->object->getAvg("visits");
		$this->assertType("float", $res);
		$this->assertEquals(4, $res);
		$this->setExpectedException("\ModelException");
		$this->object->getAvg("nesmysl");
	}

	public function testGetMin() {
		$res = $this->object->getMin("visits");
		$this->assertType("int", $res);
		$this->assertEquals(0, $res);
		$this->setExpectedException("\ModelException");
		$this->object->getMin("nesmysl");
	}

	public function testGetMax() {
		$res = $this->object->getMax("visits");
		$this->assertType("int", $res);
		$this->assertEquals(8, $res);
		$this->setExpectedException("\ModelException");
		$this->object->getMax("nesmysl");
	}

	public function testFetchColumn() {
		$expected = array("Clanek", "Article", "Nepovolený článek", "Jinačí článek");
		$this->assertEquals($expected, $this->object->fetchColumn("name"));
		$res = $this->object->fetchColumn("id");
		$this->assertType("int", $res[0]);
	}

	public function testFetchAll() {
		$this->assertType("array", $this->object->fetchAll());
		$this->assertEquals(2, count($this->object->fetchAll(2)));
		$res = $this->object->fetchAll(2, 1);
		$this->assertEquals(2, count($res));
		$this->assertEquals("Article", $res[0]->name);
		$this->assertType("Page", $res[0]);
	}

	public function testFetchAssoc() {
		$res = $this->object->fetchAssoc("name,#");
		$this->assertType("Page", $res["Clanek"][0]);
	}

	public function testFetchPairs() {
		foreach ($this->object->fetchPairs("id", "name") as $k => $v) {
			$this->assertType("int", $k);
			$this->assertType("string", $v);
		}

		foreach ($this->object->fetchPairs("name", "text") as $k => $v) {
			$this->assertType("string", $k);
			$this->assertType("string", $v);
		}

		$this->setExpectedException("\ModelException");
		$this->object->fetchPairs("nesmysl", "nesmysl");
	}

	public function testFetch() {
		$res = $this->object->fetch();
		$this->assertType("Page", $res);
		$this->assertEquals($res->name, "Clanek");
	}

	public function testFetchSingle() {
		$res = $this->object->fetchSingle("name");
		$this->assertEquals($res, "Clanek");
	}

	public function testCount() {
		$this->assertEquals(4, count($this->object));
		$this->assertFalse($this->object->isLoaded());

		$this->object[0]; // initialize collection
		$this->assertEquals(4, count($this->object));
		$this->assertTrue($this->object->isLoaded());

		$this->object->where("[allowed] = %b", false); // change conditions
		$this->assertEquals(2, count($this->object));
		$this->assertFalse($this->object->isLoaded());
	}

	public function testWhere() {
		$this->object[0]; // initialize collection
		$this->assertTrue($this->object->isLoaded());

		// change conditions
		$this->object->where("[allowed] = %b", false);
		$this->assertFalse($this->object->isLoaded());

		// test result
		$i = 0;
		foreach ($this->object as $item) {
			$this->assertEquals(false, $item->allowed);
			$i++;
		}

		$this->assertEquals(2, $i);

		// change conditions
		$this->object->where("[name] = %s", "Article");
		$this->assertEquals(1, count($this->object));
	}

	public function testToDataSource() {
		$this->assertType("DibiDataSource", $this->object->toDataSource());
	}

	public function testState() {
		$o = $this->object[0];
		$this->assertEquals(Record::STATE_EXISTING, $o->getState());
	}

	public function testFreeze() {
		$this->object->freeze();
		$this->assertTrue($this->object[0]->isFrozen());
	}

	public function testFreeze2() {
		$this->object[0]; // init
		$this->object->freeze();
		$this->assertTrue($this->object[0]->isFrozen());
	}

	public function testQueryException() {
		$this->setExpectedException("\ModelException");
		$this->object->orderBy("nesmysl");
		$this->object[0]; // init
	}

}