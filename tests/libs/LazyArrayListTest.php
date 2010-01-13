<?php

require __DIR__ . "/../../BaseTest.php";

/**
 * Test class for LazyArrayList.
 *
 * @backupStaticAttributes disabled
 * @backupGlobals disabled
 */
class LazyArrayListTest extends BaseTest {

	/** @var MockList */
	private $object;

	protected function setUp() {
		$this->object = new MockList;
	}

	public function testOffsetGet() {
		$this->assertSame(2, $this->object[1]);
	}

	public function testGetArrayCopy() {
		$this->assertSame(array(1,2,3), $this->object->getArrayCopy());
	}

	public function testCount() {
		$this->assertSame(3, $this->object->count());
		$this->assertSame(3, count($this->object));
	}

	public function testOffsetExists() {
		$this->assertTrue(isset($this->object[1]));
		$this->assertFalse(isset($this->object[4]));
	}

	public function testIsLoaded() {
		$this->assertFalse($this->object->isLoaded());
		$this->object[0];
		$this->assertTrue($this->object->isLoaded());
	}

	public function testForeach() {
		foreach ($this->object as $value) {
			$arr[] = $value;
		}

		$this->assertSame(array(1,2,3), $arr);
	}

	public function testUnset() {
		unset($this->object[2]);
		$this->assertSame(2, count($this->object));
	}

	public function testAppend() {
		$this->object->append(4);
		$this->assertTrue($this->object->isLoaded());
		$this->assertEquals(4, count($this->object));
	}

}


class MockList extends LazyArrayList {

	protected function load() {
		$this->import(array(1,2,3));
	}
}