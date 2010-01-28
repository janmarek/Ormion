<?php


require __DIR__ . "/../../BaseTest.php";

/**
 * Test class for OrmionRecordSet
 *
 * @backupStaticAttributes disabled
 * @backupGlobals disabled
 */
class OrmionMapperTest extends BaseTest {

	/** @var DibiConnection */
	private $db;

	/** @var OrmionRecordSet */
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
		$this->object = new OrmionRecordSet($fluent, "MockRecord");
	}

	protected function tearDown() {
		$this->db->delete("pages")->execute();
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
			$this->assertEquals("0", $item->allowed);
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

class MockRecord extends FreezableObject {

	private $state = OrmionRecord::STATE_NEW;

	private $data;

	public function __construct($data) {
		$this->data = $data;
	}

	public function getState() {
		return $this->state;
	}

	public function setState($state) {
		$this->state = $state;
		return $this;
	}

	public function & __get($name) {
		return $this->data[$name];
	}

	public function clearModified() {
		return $this;
	}

}