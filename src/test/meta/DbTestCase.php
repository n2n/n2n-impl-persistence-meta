<?php
namespace meta;

use PHPUnit\Framework\TestCase;
use n2n\test\TestEnv;
use n2n\persistence\meta\structure\Table;
use n2n\persistence\meta\structure\Size;
use n2n\persistence\meta\structure\IndexType;
use n2n\impl\persistence\meta\mysql\MysqlSize;
use n2n\persistence\meta\structure\UnavailableTypeException;
use n2n\persistence\meta\structure\IntegerColumn;
use n2n\persistence\meta\structure\EnumColumn;
use n2n\persistence\meta\structure\BinaryColumn;
use n2n\persistence\meta\structure\TextColumn;
use n2n\persistence\meta\structure\FixedPointColumn;
use n2n\persistence\meta\structure\DateTimeColumn;
use n2n\persistence\meta\structure\FloatingPointColumn;
use n2n\persistence\meta\structure\StringColumn;
use Other;

class DbTestCase extends TestCase {
	
	private $pdo;
	private $metaData;
	private $database;
	private $metaManager;
	private $dialect;
	private $persistenceUnitName;
	
	public function setPersistenceUnitName(string $persistenceUnitName = null) {
		$this->persistenceUnitName = $persistenceUnitName;
		$this->pdo = null;
		$this->metaData = null;
		$this->database = null;
	}
	
	public function getPdo() {
		if (null === $this->pdo) {
			$this->pdo = TestEnv::db()->pdo($this->persistenceUnitName);
		}
		
		return $this->pdo; 
	}
	
	public function getMetaData() {
		if (null === $this->metaData) {
			$this->metaData = $this->getPdo()->getMetaData();
		}
		
		return $this->metaData;
	}
	
	public function getMetaManager() {
		if (null === $this->metaManager) {
			$this->metaManager = $this->getMetaData()->getMetaManager();
		}
		
		return $this->metaManager;
	}
	
	public function reloadDatabase() {
		$this->database = $this->metaManager->createDatabase();
	}
	
	public function getDatabase() {
		if (null === $this->database) {
			$this->database = $this->getMetaData()->getMetaManager()->createDatabase();
		}
		
		return $this->database;
	}
	
	public function createTable(string $name, bool $createId = false) {
		$table = $this->getDatabase()->createMetaEntityFactory()->createTable($name);
		if ($createId) {
			$this->generateId($table);
		}
		
		return $table;
	}
	
	public function createView(string $name, string $query) {
		return $this->getDatabase()->createMetaEntityFactory()->createView($name, $query);
	}
	
	public function generateId(Table $table) {
		$idColumn = $table->createColumnFactory()->createIntegerColumn('id', Size::INTEGER);
		$table->createIndex(IndexType::PRIMARY, ['id']);
	}
	
	public function flush(bool $reload = false) {
		$this->getMetaManager()->flush();
		if ($reload) {
			$this->reloadDatabase();
		}
	}
	
	public function viewTest(bool $reload) {
		if ($this->containsMetaEntityName('hello2')) {
			$this->removeMetaEntityByName('hello2');
		}
		
		if ($this->containsMetaEntityName('hello3')) {
			$this->removeMetaEntityByName('hello3');
		}
		
		$query = 'SELECT `TABLE_CATALOG` FROM information_schema.`TABLES`';
		$view = $this->createView('hello2', $query);
		
		$this->flush($reload);
		
		$view = $this->getDatabase()->getMetaEntityByName('hello2');
		
		$view->setName('hello3');
		$this->flush($reload);
		$this->assertTrue($this->getDatabase()->containsMetaEntityName('hello3'));
	}
	
	public function tableTest(bool $reload, bool $enumAvailable, bool $mediumAvailable) {
		
		$database = $this->getDatabase();
		if ($database->containsMetaEntityName('table')) {
			$database->removeMetaEntityByName('table');
		}
		
		if ($this->containsMetaEntityName('other')) {
			$this->removeMetaEntityByName('other');
		}
		
		if ($this->containsMetaEntityName('comptusch')) {
			$this->removeMetaEntityByName('comptusch');
		}
		
		$table = $this->createTable('table', true);
		$this->flush(true);
		$table = $this->getMetaEntityByName('table');
		
		$this->assertTrue($table instanceof Table);
		$this->assertTrue($table->getPrimaryKey() !== null);
		
		$this->createComptusch($enumAvailable, $mediumAvailable);
		$this->flush($reload);
		$this->checkComptusch($enumAvailable, $mediumAvailable);
		
		$this->createOther($reload, $enumAvailable, $mediumAvailable);
		$this->flush($reload);
//		$this->checkOther($enumAvailable, $mediumAvailable);
	}
	
	public function createComptusch(bool $enumAvailable, bool $mediumAvailable) {
		$table = $this->createTable('comptusch', true);
		
		$this->assertTrue($table instanceof Table);
		$columnFactory = $table->createColumnFactory();
		
		$columnFactory->createIntegerColumn('long', Size::LONG);
		$columnFactory->createIntegerColumn('medium', Size::MEDIUM);
		$columnFactory->createIntegerColumn('short', Size::SHORT);
		try {
			$enumColumn = $columnFactory->createEnumColumn('enum', array('a', 'b'));
			$enumColumn->setDefaultValue('a');
			$enumColumn->setNullAllowed(true);
			$this->assertTrue($enumAvailable);
		} catch (UnavailableTypeException $e) {
			$this->assertTrue(!$enumAvailable);
		}
		
		$columnFactory->createBinaryColumn('binary', 408);
		$columnFactory->createDateTimeColumn('dateTime', true, true);
		$columnFactory->createTextColumn('text', 5000, 'utf8');
		
		try {
			$columnFactory->createTextColumn('medium_text', MysqlSize::SIZE_MEDIUM_TEXT);
			$this->assertTrue($mediumAvailable);
		} catch (UnavailableTypeException $e) {
			$this->assertTrue(!$mediumAvailable);
		}
		
		$columnFactory->createFixedPointColumn('fixed_point', 10, 3);
		$columnFactory->createFloatingPointColumn('float', Size::DOUBLE);
		$columnFactory->createStringColumn('hello', 100);
		
		$table->createIndex(IndexType::INDEX, array('long', 'medium'), 'bums');
		$table->createIndex(IndexType::UNIQUE, array('long'), 'hello_index');
	}
	
	public function checkComptusch(bool $enumAvailable, bool $mediumAvailable) {
		$comptusch = $this->getMetaEntityByName('comptusch');
		$this->assertTrue($comptusch instanceof Table);
		
		$longColumn = $comptusch->getColumnByName('long');
		$this->assertTrue($longColumn instanceof IntegerColumn);
		$this->assertTrue($longColumn->getSize() === Size::LONG);
		$this->assertTrue($longColumn->isSigned());
		
		$mediumColumn = $comptusch->getColumnByName('medium');
		$this->assertTrue($mediumColumn instanceof IntegerColumn);
		$this->assertTrue($mediumColumn->getSize() === Size::MEDIUM);
		$this->assertTrue($mediumColumn->isSigned());
		
		$shortColumn = $comptusch->getColumnByName('short');
		$this->assertTrue($shortColumn instanceof IntegerColumn);
		$this->assertTrue($shortColumn->getSize() === Size::SHORT);
		$this->assertTrue($shortColumn->isSigned());
		
		if ($enumAvailable) {
			$enumColumn = $comptusch->getColumnByName('enum');
			$this->assertTrue($enumColumn instanceof EnumColumn);
			
			$this->assertTrue($enumColumn->getValues() == ['a', 'b']);
			$this->assertTrue($enumColumn->getDefaultValue() == 'a');
			$this->assertTrue($enumColumn->isNullAllowed());
		}

		$binaryColumn = $comptusch->getColumnByName('binary');
		$this->assertTrue($binaryColumn instanceof BinaryColumn);
		$this->assertTrue($binaryColumn->getSize() === 408);
		
		$dateTimeColumn = $comptusch->getColumnByName('dateTime');
		$this->assertTrue($dateTimeColumn instanceof DateTimeColumn);
		$this->assertTrue($dateTimeColumn->isDateAvailable());
		$this->assertTrue($dateTimeColumn->isTimeAvailable());
		
		$textColumn = $comptusch->getColumnByName('text');
		$this->assertTrue($textColumn instanceof TextColumn);
		$this->assertTrue($textColumn->getSize() >= 5000);
		$this->assertTrue($textColumn->getCharset() === 'utf8');
		
		if ($mediumAvailable) {
			$mediumColumn = $comptusch->getColumnByName('medium_text');
			$this->assertTrue($mediumColumn instanceof TextColumn);
			$this->assertTrue($mediumColumn->getSize() >= MysqlSize::SIZE_MEDIUM_TEXT);
		}
		
		$fixedPointColumn = $comptusch->getColumnByName('fixed_point');
		$this->assertTrue($fixedPointColumn instanceof FixedPointColumn);
		$this->assertTrue($fixedPointColumn->getNumIntegerDigits() === 10);
		$this->assertTrue($fixedPointColumn->getNumDecimalDigits() === 3);
		
		$floatColumn = $comptusch->getColumnByName('float');
		$this->assertTrue($floatColumn instanceof FloatingPointColumn);
		$this->assertTrue($floatColumn->getSize() >= Size::DOUBLE);
		
		$stringColumn = $comptusch->getColumnByName('hello');
		$this->assertTrue($stringColumn instanceof StringColumn);
		$this->assertTrue($stringColumn->getLength() >= 100);
		
		$bumsIndex = $comptusch->getIndexByName('bums');
		$this->assertTrue($bumsIndex->getType() === IndexType::INDEX);
		$this->assertTrue(count($bumsIndex->getColumns()) === 2);
		$this->assertTrue($bumsIndex->containsColumnName('long'));
		$this->assertTrue($bumsIndex->containsColumnName('medium'));
		
		$helloIndex = $comptusch->getIndexByName('hello_index');
		$this->assertTrue($helloIndex->getType() === IndexType::UNIQUE);
		$this->assertTrue(count($helloIndex->getColumns()) === 1);
		$this->assertTrue($helloIndex->containsColumnName('long'));
	}
	
	private function createOther(bool $reload, bool $enumAvailable, bool $mediumAvailable) {
		$comptusch = $this->getMetaEntityByName('comptusch');
		$this->assertTrue($comptusch instanceof Table);
		
		$otherTable = $comptusch->copy('other');
		
		$otherTable->removeIndexByName('bums');
		$otherTable->removeIndexByName('hello_index');
		$this->assertTrue(count($otherTable->getIndexes()) === 1);
		
		$otherTable->createColumnFactory()->createIntegerColumn('f_key', Size::INTEGER);
		$otherTable->createIndex(IndexType::FOREIGN, ['f_key'], 'f_key_comptusch', $comptusch, ['id']);
		
		$this->getDatabase()->addMetaEntity($otherTable);
		
		$this->flush($reload);
		
		$otherTable = $this->getMetaEntityByName('other');
		$this->assertTrue($otherTable instanceof Table);
		
		$indexes = $otherTable->getIndexes();
		$this->assertTrue(count($indexes) === 2);
		
		$fKey = $otherTable->getIndexByName('f_key_comptusch');
		$this->assertTrue($fKey->getType() === IndexType::FOREIGN);
		$this->assertTrue($fKey->containsColumnName('f_key'));
		$this->assertTrue($fKey->getRefTable()->getName() === 'comptusch');
		$this->assertTrue(count($fKey->getRefColumns()) === 1);
		$this->assertTrue($fKey->containsRefColumnName('id'));
		
		$otherTable->createColumnFactory()->createBinaryColumn('varbinary_other', 401);
		if ($mediumAvailable) {
			$otherTable->removeColumnByName('medium_text');
		}
		
		$otherTable->removeColumnByName('fixed_point');
		$otherTable->createColumnFactory()->createIntegerColumn('fixed_point', 440);
		
		$otherTable->createIndex(IndexType::INDEX, array('long', 'medium'));
		$otherTable->createIndex(IndexType::UNIQUE, array('short', 'hello'));
	}
	
	public function containsMetaEntityName(string $name) {
		return $this->getDatabase()->containsMetaEntityName($name);
	}
	
	public function getMetaEntityByName(string $name) {
		return $this->getDatabase()->getMetaEntityByName($name);
	}
	
	public function removeMetaEntityByName(string $name) {
		return $this->getDatabase()->removeMetaEntityByName($name);
	}
}