<?php

require __DIR__ . "/../../BaseTest.php";

/**
 * Test class for OrmionRecord
 *
 * @backupStaticAttributes disabled
 * @backupGlobals disabled
 */
class OrmionRecordTest extends BaseTest {

	/** @var DibiConnection */
	private $db;

	/** @var Page */
	private $object;

	protected function setUp() {
		OrmionMapper::$logSql = true;

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

		Environment::setVariable("ormionConfigDir", "%tempDir%/testOrmionConfig");
		@unlink(Environment::getVariable("ormionConfigDir") . "/pages.ini");
		$this->object = new Page;
	}

	protected function tearDown() {
		$this->db->delete("pages")->execute();
	}

	public function testRegisterInvalidSetter() {
		$this->setExpectedException("InvalidArgumentException");
		$this->object->registerSetter("column", "nesmysl");
	}

	public function setLowercased($data, $name, $value) {
		$data[$name] = String::lower($value);
	}

	public function testRegisterSetter() {
		$this->object->registerSetter("name", array($this, "setLowercased"));
		$this->object->name = "Krásný NÁZEV";
		$this->assertEquals("krásný název", $this->object->name);
	}

	public function testRegisterInvalidGetter() {
		$this->setExpectedException("InvalidArgumentException");
		$this->object->registerGetter("column", "nesmysl");
	}

	public function getLowercased($data, $name) {
		return String::lower($data[$name]);
	}

	public function testRegisterGetter() {
		$this->object->registerGetter("name", array($this, "getLowercased"));
		$this->object->name = "Krásný NÁZEV";
		$this->assertEquals("krásný název", $this->object->name);
	}

	public function testFind() {
		$o = Page::find(array(
			"name" => "Clanek",
		));

		$this->assertType("Page", $o);
		$this->assertEquals("Clanek", $o->name);
	}

	public function testFindAll() {
		$set = Page::findAll();
		$this->assertType("OrmionRecordSet", $set);
		$this->assertType("Page", $set[0]);

		$set = Page::findAll(array(
			"allowed" => true,
		));
		$this->assertType("OrmionRecordSet", $set);
		$this->assertEquals(2, count($set));
	}

	public function testSet() {
		$this->object->id = "22blabla";
		$this->assertEquals(22, $this->object->id);
	}

	public function testAlias() {
		$this->object->setAlias("popis", "description");
		$this->object->popis = "something";
		$this->assertEquals("something", $this->object->description);
		$this->object->description = "";
		$this->assertEquals("", $this->object->popis);
	}

	public function testDefaultValue() {
		$this->object->setDefaultValue("name", "Artikl");
		$this->assertSame(array("name" => "Artikl"), $this->object->getData());

		$this->object->name = "Nejm";
		$this->assertSame(array("name" => "Nejm"), $this->object->getData());
	}

	public function testGetData() {
		$this->object->name = "Vosel";
		$this->object->setDefaultValue("text", "Utřinos");
		$this->object->allowed = true;

		$data = $this->object->getData();
		$this->assertArrayHasKey("name", $data);
		$this->assertArrayHasKey("text", $data);
		$this->assertArrayHasKey("allowed", $data);

		$data = $this->object->getData(array("allowed"));
		$this->assertArrayNotHasKey("name", $data);
		$this->assertArrayNotHasKey("text", $data);
		$this->assertArrayHasKey("allowed", $data);
	}

	public function testGetException() {
		$this->setExpectedException("MemberAccessException");
		$this->object->nesmysl;
	}

}