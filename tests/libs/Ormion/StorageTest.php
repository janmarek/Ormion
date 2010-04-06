<?php

use Nette\String;

/**
 * Ormion\Storage test
 *
 * @author Jan Marek
 * @backupStaticAttributes disabled
 * @backupGlobals disabled
 */
class StorageTest extends PHPUnit_Framework_TestCase {

	/** @var Ormion\Storage */
	private $object;

	protected function setUp() {
		$this->object = new Ormion\Storage;
	}

	public function testRegisterSetter() {
		$this->object->registerSetter("name", array($this, "setLowercased"));
		$this->object->name = "Krásný NÁZEV";
		$this->assertEquals("krásný název", $this->object->name);
	}

	public function setLowercased($storage, $name, $value) {
		$rawData = $storage->getStorage();
		$rawData[$name] = String::lower($value);
	}

	public function testRegisterInvalidGetter() {
		$this->setExpectedException("InvalidArgumentException");
		$this->object->registerGetter("column", "nesmysl");
	}

	public function testRegisterInvalidSetter() {
		$this->setExpectedException("InvalidArgumentException");
		$this->object->registerSetter("column", "nesmysl");
	}

	public function getLowercased($storage, $name) {
		$rawData = $storage->getStorage();
		return String::lower($rawData[$name]);
	}

	public function testRegisterGetter() {
		$this->object->registerGetter("name", array($this, "getLowercased"));
		$this->object->name = "Krásný NÁZEV";
		$this->assertEquals("krásný název", $this->object->name);
	}

	public function testSet() {
		$this->object->id = "22blabla";
		$this->assertEquals("22blabla", $this->object->id);
	}

	public function testAlias() {
		$this->object->setAlias("popis", "description");
		$this->object->popis = "something";
		$this->assertEquals("something", $this->object->description);
		$this->object->description = "";
		$this->assertEquals("", $this->object->popis);
	}

	public function testDefaultValue() {
		
	}

	public function testGetValues() {
		$this->object->name = "Vosel";
		$this->object->text = "Utřinos";
		$this->object->allowed = true;

		$data = $this->object->getValues();
		$this->assertArrayHasKey("name", $data);
		$this->assertArrayHasKey("text", $data);
		$this->assertArrayHasKey("allowed", $data);

		$data = $this->object->getValues(array("allowed"));
		$this->assertArrayNotHasKey("name", $data);
		$this->assertArrayNotHasKey("text", $data);
		$this->assertArrayHasKey("allowed", $data);
	}

	public function testGetException() {
		$this->setExpectedException("MemberAccessException");
		$this->object->nesmysl;
	}

	public function testArrayAccess() {
		$this->object["value"] = 10;
		$this->assertTrue(isset($this->object["value"]));
		$this->assertEquals(10, $this->object["value"]);
		unset($this->object["value"]);
		$this->assertFalse(isset($this->object["value"]));
	}

	public function testIteratorAggregate() {
		$this->object->name = "nameVal";
		$this->object->desc = "descVal";
		$this->object->text = "textVal";

		foreach ($this->object as $key => $value) {
			$values[$key] = $value;
		}

		$expected = array(
			"name" => "nameVal",
			"desc" => "descVal",
			"text" => "textVal",
		);

		$this->assertSame($expected, $values);
	}

	public function testHasValue() {
		$this->assertFalse($this->object->hasValue("value"));
		$this->object->value = null;
		$this->assertTrue($this->object->hasValue("value"));
		$this->object->setDefaultValue("value2", 10);
		$this->assertFalse($this->object->hasValue("value2"));
	}

	public function testInputOutputFilter() {
		$this->object->addInputFilter("pass", "sha1");
		$this->object->pass = "heslo";
		$this->assertEquals(sha1("heslo"), $this->object->pass);

		$this->object->addOutputFilter("pass", "md5");
		$this->assertEquals(md5(sha1("heslo")), $this->object->pass);

	}
	
}