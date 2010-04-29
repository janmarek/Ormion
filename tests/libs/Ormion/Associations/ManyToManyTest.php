<?php

/**
 * ManyToMany test
 *
 * @backupStaticAttributes disabled
 * @backupGlobals disabled
 */
class ManyToManyTest extends PHPUnit_Framework_TestCase {

	protected $backupGlobals = false;
	protected $backupStaticAttributes = false;

	protected function setUp() {
		$this->db = dibi::getConnection("ormion");
		$this->db->delete("pages")->execute();
		$this->db->delete("connections")->execute();
		$this->db->delete("tags")->execute();

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

		$this->db->query("insert into [tags]", array(
			"name" => "Osobní",
			"url" => "osobni",
		), array(
			"name" => "Technologie",
			"url" => "technologie",
		), array(
			"name" => "Společnost",
			"url" => "spolecnost",
		));
	}

	protected function tearDown() {
		$this->db->delete("pages")->execute();
		$this->db->delete("connections")->execute();
		$this->db->delete("tags")->execute();
	}

	public function testEmpty() {
		$tags = Page::findByName("Clanek")->Tags;
		$this->assertType("Ormion\Collection", $tags);
		$this->assertEquals(0, count($tags));
	}

	public function testSet() {
		$page = Page::findByName("Clanek");
		$tags = Tag::findAll();
		$page->Tags = $tags;
		$page->save();

		$tagIds = array_map(function ($record) { return $record->id; }, $tags->toArray());

		$q = $this->db->select("*")->from("connections")->execute();
		$q->detectTypes();
		$conn = $q->fetchAll();

		$this->assertSame(3, count($conn));

		foreach ($conn as $row) {
			$this->assertSame($page->id, $row->pageId);
			$this->assertTrue(in_array($row->tagId, $tagIds));
		}
	}

	public function testGet() {
		$this->testSet();
		$page = Page::findByName("Clanek");
		$this->assertEquals(3, count($page->Tags));
	}

	public function testAdd() {
		$this->testSet();
		$page = Page::findByName("Clanek");
		$page->Tags[] = Tag::create(array(
			"name" => "Luxusní zboží",
			"url" => "luxusni-zbozi",
		));

		$page->save();

		$this->assertEquals(4, count(Page::findByName("Clanek")->Tags));
	}

	public function testRemoveAll() {
		$this->testSet();
		$page = Page::findByName("Clanek");
		$page->Tags = array();
		$page->save();

		$this->assertEquals(0, count(Page::findByName("Clanek")->Tags));
	}

	public function testNewRecordWithNewReferenced() {
		$page = Page::create(array(
			"name" => "English article",
			"description" => "Description",
			"text" => "Text in english.",
			"allowed" => true,
		));

		$page->Tags[] = Tag::create(array(
			"name" => "Society",
			"url" => "society",
		));

		$page->Tags[] = Tag::create(array(
			"name" => "Previte",
			"url" => "previte",
		));

		$page->save();

		$this->assertEquals(2, count(Page::findByName("English article")->Tags));
	}
	
}