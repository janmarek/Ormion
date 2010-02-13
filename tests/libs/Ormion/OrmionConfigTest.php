<?php

/**
 * OrmionConfigTest
 *
 * @author Jan Marek
 * @backupStaticAttributes disabled
 * @backupGlobals disabled
 */
class OrmionConfigTest extends PHPUnit_Framework_TestCase {

	/** @var OrmionConfig */
	private $object;

	protected function setUp() {
		$this->object = new OrmionConfig(array(
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

			"form_jmeno" => array(
				"id" => array(
					"type" => "hidden",
				),
				"text" => array(
					"type" => "text",
					"label" => "Text",
				),
				"s" => array(
					"type" => "submit",
					"label" => "OK",
				),
			),
		));
	}

	private function generatedConfig($useFile, $generateForms) {
		OrmionConfig::$generateForms = $generateForms;

		$tableInfo = dibi::getConnection("ormion")->getDatabaseInfo()->getTable("pages");
		$cfg = OrmionConfig::fromTableInfo($tableInfo);

		if ($useFile) {
			$filePath = APP_DIR . "/temp/" . md5(uniqid() . time()) . ".ini";
			$cfg->save($filePath);
			$cfg = OrmionConfig::fromFile($filePath);
		}

		$this->assertEquals(
			array("id", "name", "description", "text", "created", "allowed"),
			$cfg->getColumns()
		);


		$this->assertEquals(dibi::INTEGER, $cfg->getType("id"));
		$this->assertEquals(dibi::TIME, $cfg->getType("created"));


		$this->assertTrue($cfg->isNullable("description"));
		$this->assertFalse($cfg->isNullable("text"));
		$this->assertFalse($cfg->isNullable("name"));
		
		$this->assertTrue($cfg->isPrimaryAutoIncrement());

		$this->assertEquals("id", $cfg->getPrimaryColumn());

		if ($generateForms) {
			$this->assertType("array", $cfg->getForm("default"));
		} else {
			try {
				$cfg->getForm("default");
				$this->fail();
				
			} catch (InvalidArgumentException $e) {

			} catch (Exception $e) {
				$this->fail();
			}
		}

		if ($useFile) {
			unlink($filePath);
		}
	}


	public function testFromTableInfo() {
		$this->generatedConfig(false, true);
		$this->generatedConfig(false, false);
	}


	public function testSaveAndFromFile() {
		$this->generatedConfig(true, true);
		$this->generatedConfig(true, false);
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
		$this->assertEquals(null, $this->object->getType("nesmysl"));
	}

	
	public function testIsNullable() {
		$this->assertTrue($this->object->isNullable("description"));
		$this->assertFalse($this->object->isNullable("text"));
		$this->assertFalse($this->object->isNullable("name"));
	}


	public function testIsPrimaryAutoIncrement() {
		$this->assertTrue($this->object->isPrimaryAutoIncrement());

		$cfg = new OrmionConfig(array(
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

		$cfg = new OrmionConfig(array(
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

		$cfg = new OrmionConfig(array(
			"key" => array(
				"rc" => array(
					"primary" => true,
					"autoIncrement" => false,
				)
			)
		));

		$this->assertEquals(array("rc"), $cfg->getPrimaryColumns());

		$cfg = new OrmionConfig(array(
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


	public function testGetForm() {
		$this->assertType("array", $this->object->getForm("jmeno"));

		$this->setExpectedException("InvalidArgumentException");

		$this->object->getForm("jmenoNeexistujicihoFormu");
	}

}