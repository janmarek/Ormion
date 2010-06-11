<?php

require_once __DIR__ . "/../../../document_root/index.php";

use Nette\Environment;

/**
 * Test class for Ormion\Mapper
 *
 * @backupStaticAttributes disabled
 * @backupGlobals disabled
 */
class MapperTest extends PHPUnit_Framework_TestCase {

	/** @var DibiConnection */
	private $db;

	/** @var Ormion\Mapper */
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

		$this->object = new Ormion\Mapper("pages", "Page");
	}

	protected function tearDown() {
		$this->db->delete("pages")->execute();
	}

	public function testGetTable() {
		$this->assertEquals("pages", $this->object->getTable());
	}

	public function testGetConfig() {
		$this->assertType("Ormion\Config", $this->object->getConfig());
	}

	public function testFind() {
		$o = $this->object->find(array(
			"name" => "Clanek",
		));

		$this->assertType("Page", $o);
		$this->assertEquals("Clanek", $o->name);
		$this->assertEquals(Ormion\Record::STATE_EXISTING, $o->getState());
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
		$this->setExpectedException("\ModelException");
		$this->object->find(array(
			"nesmysl" => true,
		));
	}

	public function testFindAll() {
		$set = $this->object->findAll();
		$this->assertType("Ormion\Collection", $set);
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

		$this->assertEquals(Ormion\Record::STATE_NEW, $page->getState());

		$this->object->insert($page);

		$this->assertEquals(Ormion\Record::STATE_EXISTING, $page->getState());

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

		$this->assertEquals(Ormion\Record::STATE_DELETED, $record->getState());

		$res = $this->object->find(array(
			"name" => "Clanek",
		));

		$this->assertFalse($res);
	}

}