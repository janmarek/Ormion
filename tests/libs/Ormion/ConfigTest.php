<?php

/**
 * ConfigTest
 *
 * @author Jan Marek
 * @backupStaticAttributes disabled
 * @backupGlobals disabled
 */
class ConfigTest extends PHPUnit_Framework_TestCase {

	/** @var Ormion\Config */
	private $object;

	protected function setUp() {
		$this->object = new Ormion\Config(array(
			"column" => array(
				"id" => array(
					"type" => dibi::INTEGER,
				),
				"name" => array(
					"type" => dibi::TEXT,
					"column" => true,
					"nullable" => false,
				),
				"description" => array(
					"type" => dibi::TEXT,
					"nullable" => true,
				),
				"text" => array(
					"type" => dibi::TEXT,
				),
				"created" => array(
					"type" => dibi::TIME,
				),
				"allowed" => array(
					"type" => dibi::BOOL,
				),
				"number" => array(
					"type" => dibi::INTEGER,
					"column" => false,
				)
			),
			
			"key" => array(
				"id" => array(
					"primary" => true,
					"autoIncrement" => true,
				),
			),
		));
	}

	private function generatedConfig($useFile) {
		$tableInfo = dibi::getConnection("ormion")->getDatabaseInfo()->getTable("pages");
		$cfg = Ormion\Config::fromTableInfo($tableInfo);

		if ($useFile) {
			$filePath = APP_DIR . "/temp/" . md5(uniqid() . time()) . ".ini";
			$cfg->save($filePath);
			$cfg = Ormion\Config::fromFile($filePath);
		}

		$this->assertEquals(
			array("id", "name", "description", "text", "visits", "created", "allowed"),
			$cfg->getColumns()
		);


		$this->assertEquals(dibi::TEXT, $cfg->getType("name"));
		$this->assertEquals(dibi::INTEGER, $cfg->getType("id"));
		$this->assertEquals(dibi::TIME, $cfg->getType("created"));
		$this->assertEquals(dibi::BOOL, $cfg->getType("allowed"));


		$this->assertTrue($cfg->isNullable("description"));
		$this->assertFalse($cfg->isNullable("text"));
		$this->assertFalse($cfg->isNullable("name"));
		
		$this->assertTrue($cfg->isPrimaryAutoIncrement());

		$this->assertEquals("id", $cfg->getPrimaryColumn());

		if ($useFile) {
			unlink($filePath);
		}
	}


	public function testFromTableInfo() {
		$this->generatedConfig(false);
	}


	public function testSaveAndFromFile() {
		$this->generatedConfig(true);
	}


	public function testGetColumns() {
		$this->assertEquals(
			array("id", "name", "description", "text", "created", "allowed"),
			$this->object->getColumns()
		);
	}


	public function testGetType() {
		$this->assertEquals(dibi::INTEGER, $this->object->getType("id"));
		$this->assertEquals(dibi::INTEGER, $this->object->getType("number"));
		$this->assertEquals(dibi::TIME, $this->object->getType("created"));
		$this->assertEquals(dibi::BOOL, $this->object->getType("allowed"));
		$this->assertEquals(null, $this->object->getType("nesmysl"));
	}

	
	public function testIsNullable() {
		$this->assertTrue($this->object->isNullable("description"));
		$this->assertFalse($this->object->isNullable("text"));
		$this->assertFalse($this->object->isNullable("name"));
	}


	public function testIsPrimaryAutoIncrement() {
		$this->assertTrue($this->object->isPrimaryAutoIncrement());

		$cfg = new Ormion\Config(array(
			"key" => array(
				"rc" => array(
					"primary" => true,
					"autoIncrement" => false,
				)
			)
		));

		$this->assertFalse($cfg->isPrimaryAutoIncrement());
	}


	public function testGetPrimaryColumn() {
		$this->assertEquals("id", $this->object->getPrimaryColumn());

		$cfg = new Ormion\Config(array(
			"key" => array(
				"articleId" => array(
					"primary" => true,
					"autoIncrement" => false,
				),
				"tagId" => array(
					"primary" => true,
					"autoIncrement" => false,
				)
			)
		));

		$this->assertEquals("articleId", $cfg->getPrimaryColumn());
	}


	public function testGetPrimaryColumns() {
		$this->assertEquals(array("id"), $this->object->getPrimaryColumns());

		$cfg = new Ormion\Config(array(
			"key" => array(
				"rc" => array(
					"primary" => true,
					"autoIncrement" => false,
				)
			)
		));

		$this->assertEquals(array("rc"), $cfg->getPrimaryColumns());

		$cfg = new Ormion\Config(array(
			"key" => array(
				"articleId" => array(
					"primary" => true,
					"autoIncrement" => false,
				),
				"tagId" => array(
					"primary" => true,
					"autoIncrement" => false,
				)
			)
		));

		$this->assertEquals(array("articleId", "tagId"), $cfg->getPrimaryColumns());
	}

}