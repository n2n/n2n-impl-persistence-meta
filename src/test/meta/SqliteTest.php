<?php
namespace meta;

class SqliteTest extends DbTestCase {

	public function __construct($name = null, array $data = [], $dataName = '') {
		parent::__construct($name, $data, $dataName);
		$this->setPersistenceUnitName('sqlite');
	}
	
	public function testView() {
		$this->viewTest(true);
		$this->viewTest(false);
	}
	
	public function testTable() {
		$this->tableTest(true, true, true);
		$this->tableTest(false, true, true);
	}
}