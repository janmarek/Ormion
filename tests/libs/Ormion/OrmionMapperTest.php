<?php

require __DIR__ . "/../../BaseTest.php";

/**
 * Test class for OrmionMapper
 *
 * @backupStaticAttributes disabled
 * @backupGlobals disabled
 */
class OrmionMapperTest extends BaseTest {

	/** @var DibiConnection */
	private $db;

	/** @var OrmionMapper */
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
		unlink(Environment::getVariable("ormionConfigDir") . "/pages.ini");
		$this->object = new OrmionMapper("pages", "Page");
	}

	protected function tearDown() {
		$this->db->delete("pages")->execute();
	}

	public function testGetTable() {
		$this->assertEquals("pages", $this->object->getTable());
	}

	public function testGetConfig() {
		$this->assertType("Config", $this->object->getConfig());
	}

	public function testConfigAutoDetect() {
		$cfg = $this->object->getConfig();

		// columns

		$columns = $cfg->get("column");
		$this->assertType("Config", $columns);

		$this->assertEquals(6, count($columns));

		$id = $columns->get("id");
		$this->assertType("Config", $id);

		$this->assertTrue($id->get("isColumn"));
		$this->assertFalse($id->get("nullable"));
		$this->assertEquals(dibi::INTEGER, $id->get("type"));

		$this->assertTrue($columns->get("description")->get("nullable"));
		$this->assertEquals(dibi::TEXT, $columns->get("description")->get("type"));

		$this->assertEquals(dibi::TIME, $columns->get("created")->get("type"));

		// keys

		$keys = $cfg->get("key");
		$this->assertType("Config", $keys);

		$id = $keys->get("id");
		$this->assertType("Config", $id);

		$this->assertTrue($id->get("primary"));
		$this->assertTrue($id->get("autoIncrement"));
	}

	public function testGetColumnNames() {
		$expected = array("id", "name", "description", "text", "created", "allowed");
		$this->assertEquals($expected, $this->object->getColumnNames());
	}

	public function testGetColumnType() {
		$this->assertEquals(dibi::INTEGER, $this->object->getColumnType("id"));
		$this->assertEquals(dibi::TEXT, $this->object->getColumnType("text"));
		$this->assertEquals(null, $this->object->getColumnType("nesmysl"));
	}

	public function testIsPrimaryAutoIncrement() {
		$this->assertTrue($this->object->isPrimaryAutoIncrement());
		$mapper = new OrmionMapper("connections", null);
		$this->assertFalse($mapper->isPrimaryAutoIncrement());
	}

	public function testFind() {
		$o = $this->object->find(array(
			"name" => "Clanek",
		));

		$this->assertType("Page", $o);
		$this->assertEquals("Clanek", $o->name);
		$this->assertEquals(OrmionRecord::STATE_EXISTING, $o->getState());
	}

	public function testFindByPrimary() {
		// find by primary

		// insert new article
		$this->db->insert("pages", array(
			"name" => "Find test",
			"description" => "Find by primary",
			"text" => "Nějaký text.",
			"allowed" => true,
		))->execute();

		$id = $this->db->getInsertId();

		$o = $this->object->find($id);
		
		$this->assertType("Page", $o);
		$this->assertEquals("Find test", $o->name);
	}

	public function testFindFail() {
		$res = $this->object->find(array(
			"name" => "Nesmysl",
		));

		$this->assertFalse($res);
	}

	public function testFindException() {
		$this->setExpectedException("ModelException");
		$this->object->find(array(
			"nesmysl" => true,
		));
	}

	public function testFindAll() {
		$set = $this->object->findAll($conditions);
		$this->assertType("OrmionRecordSet", $set);
		$this->assertFalse($set->isLoaded());
		$this->assertEquals("Page", $set->getItemType());
	}

	public function testInsert() {
		$page = new Page(array(
			"name" => "Insert test",
			"description" => "Insert record",
			"text" => "Insert record into database",
		));

		$page->allowed = true;

		$this->assertEquals(OrmionRecord::STATE_NEW, $page->getState());

		$this->object->insert($page);

		$this->assertEquals(OrmionRecord::STATE_EXISTING, $page->getState());

		$this->assertType("int", $page->id);

		$res = $this->db->select("*")->from("pages")->where("id = %i", $page->id)->fetch();

		$this->assertEquals("Insert test", $res->name);
		$this->assertEquals("Insert record", $res->description);
		$this->assertEquals(array(), $page->getModified());
	}

	public function testUpdate() {
		$record = $this->object->find(array(
			"name" => "Clanek",
		));

		$record->text = "nothing";

		$this->assertEquals(array("text"), $record->getModified());

		$this->object->update($record);

		$this->assertEquals(array(), $record->getModified());

		$record = $this->object->find(array(
			"name" => "Clanek",
		));

		$this->assertEquals("nothing", $record->text);

		$record->text = "something";
		$record->clearModified();

		$this->object->update($record);

		$record = $this->object->find(array(
			"name" => "Clanek",
		));

		$this->assertEquals("nothing", $record->text);
	}

	public function testDelete() {
		$record = $this->object->find(array(
			"name" => "Clanek",
		));

		$this->object->delete($record);

		$this->assertEquals(OrmionRecord::STATE_DELETED, $record->getState());

		$res = $this->object->find(array(
			"name" => "Clanek",
		));

		$this->assertFalse($res);
	}

}