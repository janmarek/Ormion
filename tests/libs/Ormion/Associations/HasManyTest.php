<?php

require_once __DIR__ . "/../../../../document_root/index.php";

/**
 * HasMany test
 *
 * @backupStaticAttributes disabled
 * @backupGlobals disabled
 */
class HasManyTest extends PHPUnit_Framework_TestCase {

	protected $backupGlobals = false;
	protected $backupStaticAttributes = false;

  	protected function setUp() {
		$this->db = dibi::getConnection("ormion");
		$this->db->delete("comments")->execute();
		$this->db->delete("pages")->execute();

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
	}

	protected function tearDown() {
		$this->db->delete("comments")->execute();
		$this->db->delete("pages")->execute();
	}

	public function testEmpty() {
		$comments = Page::findByName("Clanek")->Comments;
		$this->assertType("Ormion\Collection", $comments);
		$this->assertEquals(0, count($comments));
	}

	public function testSetGet() {
		$page = Page::findByName("Clanek");
		$page->Comments = array(
			Comment::create(array(
				"text" => "muj názor",
				"name" => "Honza",
				"mail" => "muj@mail.cz",
			)),
			Comment::create(array(
				"text" => "muj jiný názor",
				"name" => "Honza",
				"mail" => "muj@mail.cz",
			)),
			Comment::create(array(
				"text" => "cizí názor",
				"name" => "Jirka",
				"mail" => "jeho@mail.cz",
			)),
		);
		$page->save();

		$this->assertSame(3, count($page->Comments));
	}

	public function testAdd() {
		$this->testSetGet();
		$page = Page::findByName("Clanek");
		$page->Comments[] = Comment::create(array(
			"text" => "cizí názor",
			"name" => "Jirka",
			"mail" => "jeho@mail.cz",
		));

		$page->save();

		$this->assertEquals(4, count(Page::findByName("Clanek")->Comments));
	}

	public function testRemoveAll() {
		$this->testSetGet();
		$page = Page::findByName("Clanek");
		$page->Comments = array();
		$page->save();

		$this->assertEquals(0, count(Page::findByName("Clanek")->Comments));
	}

	public function testNewRecordWithNewReferenced() {
		$page = Page::create(array(
			"name" => "English article",
			"description" => "Description",
			"text" => "Text in english.",
			"allowed" => true,
		));

		$page->Comments[] = Comment::create(array(
			"text" => "muj názor",
			"name" => "Honza",
			"mail" => "muj@mail.cz",
		));

		$page->Comments[] = Comment::create(array(
			"text" => "muj jiný názor",
			"name" => "Honza",
			"mail" => "muj@mail.cz",
		));

		$page->save();

		$this->assertEquals(2, count(Page::findByName("English article")->Comments));
	}

}