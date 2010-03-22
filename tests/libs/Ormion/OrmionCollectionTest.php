<?php

/**
 * Test class for OrmionCollection
 *
 * @backupStaticAttributes disabled
 * @backupGlobals disabled
 */
class OrmionCollectionTest extends PHPUnit_Framework_TestCase {

	/** @var DibiConnection */
	private $db;

	/** @var OrmionCollection */
	private $object;

	protected function setUp() {
		$this->db = dibi::getConnection("ormion");
		$this->db->delete("pages")->execute();
		// id, name, description, text, created, allowed
		$this->db->query("insert into [pages]", array(
			"name" => "Clanek",
			"description" => "Popis",
			"text" => "Text",
			"allowed" => true,
		), array(
			"name" => "Article",
			"description" => "Description",
			"text" => "Text emericky.",
			"allowed" => false,
		), array(
			"name" => "Nepovolený článek",
			"description" => "Popis nepovoleného článku",
			"text" => "Dlouhý text. By byl delší než tento.",
			"allowed" => false,
		), array(
			"name" => "Jinačí článek",
			"description" => "Ryze alternativní popis",
			"text" => "Duchaplný text.",
			"allowed" => true,
		));

		$fluent = $this->db->select("*")->from("pages");
		$this->object = new OrmionCollection($fluent, "Page");
	}

	protected function tearDown() {
		$this->db->delete("pages")->execute();
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
			$this->assertEquals(0, $item->allowed);
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
		$this->assertEquals(OrmionRecord::STATE_EXISTING, $o->getState());
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
		$this->setExpectedException("ModelException");
		$this->object->orderBy("nesmysl");
		$this->object[0]; // init
	}

}