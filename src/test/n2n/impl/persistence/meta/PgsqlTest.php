<?php
namespace n2n\impl\persistence\meta;

class PgsqlTest extends DbTestCase {
	public function __construct($name = null, array $data = [], $dataName = '') {
		parent::__construct($name, $data, $dataName);
		$this->setPersistenceUnitName('pgsql');
	}
	
	public function testView() {
		$this->markTestSkipped('requires mysql db');
		$this->viewTest(true);
		$this->viewTest(false);
	}
	
	public function testTable() {
		$this->markTestSkipped('requires mysql db');
		$this->tableTest(true);
		$this->tableTest(false);
	}
	
	public function isEnumAvailable() {
		return true;
	}
	
	public function isMediumAvailable() {
		return true;
	}
	
	public function isTextAvailable() {
		return true;
	}
	
	function isColumnDetailAvailable() {
		return true;
	}
	
	function areForeignKeysAvailable() {
		return true;
	}
	
	function isCharsetAvailable() {
		return false;
	}
}