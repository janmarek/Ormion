<?php

require_once __DIR__ . "/../../../document_root/index.php";

use Nette\Environment;

/**
 * Test class for Ormion\Record
 *
 * @backupStaticAttributes disabled
 * @backupGlobals disabled
 */
class RecordTest extends PHPUnit_Framework_TestCase {

	/** @var DibiConnection */
	private $db;

	/** @var Page */
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
		$this->object = new Page;
	}

	protected function tearDown() {
		$this->db->delete("pages")->execute();
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
		$this->assertType("Ormion\Collection", $set);
		$this->assertType("Page", $set[0]);

		$set = Page::findAll(array(
			"allowed" => true,
		));
		$this->assertType("Ormion\Collection", $set);
		$this->assertEquals(2, count($set));
	}

	public function testLazyLoad() {
		$id = $this->db->select("max(id)")->from("pages")->fetchSingle();

		$page = new Page($id);
		$this->assertEquals("Jinačí článek", $page->name);

		$page = new Page($id);
		$values = $page->getValues();
		$this->assertEquals("Jinačí článek", $values["name"]);

		$page2 = new Page;
		$this->setExpectedException("MemberAccessException");
		$page2->text;
	}

	public function testLazyIsset() {
		$id = $this->db->select("max(id)")->from("pages")->fetchSingle();

		$page = new Page($id);
		$this->assertTrue(isset($page->name));
		$this->assertFalse(isset($page->nesmysl));
	}

	public function testLazyHasValue() {
		$id = $this->db->select("max(id)")->from("pages")->fetchSingle();

		$page = new Page($id);
		$this->assertTrue($page->hasValue("name"));
		$this->assertFalse($page->hasValue("nesmysl"));
	}

}