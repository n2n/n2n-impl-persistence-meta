<?php

namespace meta\mysql;

use PHPUnit\Framework\TestCase;
use n2n\persistence\Pdo;
use n2n\core\config\PersistenceUnitConfig;
use n2n\impl\persistence\meta\mysql\MysqlDialect;
use meta\test\MetaTestEnv;

class MysqlDialectTest extends TestCase {

	function testWithSameTransactionIsolationLevel() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, MysqlDialect::class);

		$this->assertCount(0, $ma->execCalls);

		$ma->pdo->reconnect();

		$this->assertCount(3, $ma->execCalls);
		$this->assertEquals('SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE', $ma->execCalls[0]['statement']);
		$this->assertEquals('SET NAMES utf8mb4', $ma->execCalls[1]['statement']);
		$this->assertEquals('SET SESSION sql_mode = \'STRICT_ALL_TABLES\'', $ma->execCalls[2]['statement']);

		$this->assertCount(0, $ma->beginTransactionCalls);

		$ma->pdo->beginTransaction();

		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(3, $ma->execCalls);

		$ma->pdo->commit();

		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(3, $ma->execCalls);

		$ma->pdo->beginTransaction(true);

		$this->assertCount(2, $ma->beginTransactionCalls);
		$this->assertCount(4, $ma->execCalls);
		$this->assertEquals('SET TRANSACTION READ ONLY', $ma->execCalls[3]['statement']);
	}

	function testWithDifferentTransactionIsolationLevel() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, MysqlDialect::class,
				PersistenceUnitConfig::TIL_REPEATABLE_READ);

		$this->assertCount(0, $ma->execCalls);

		$ma->pdo->reconnect();

		$this->assertCount(3, $ma->execCalls);
		$this->assertEquals('SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE', $ma->execCalls[0]['statement']);
		$this->assertEquals('SET NAMES utf8mb4', $ma->execCalls[1]['statement']);
		$this->assertEquals('SET SESSION sql_mode = \'STRICT_ALL_TABLES\'', $ma->execCalls[2]['statement']);

		$this->assertCount(0, $ma->beginTransactionCalls);

		$ma->pdo->beginTransaction();
		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(4, $ma->execCalls);
		$this->assertEquals('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE', $ma->execCalls[3]['statement']);

		$ma->pdo->commit();

		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(4, $ma->execCalls);

		$ma->pdo->beginTransaction(true);

		$this->assertCount(2, $ma->beginTransactionCalls);
		$this->assertCount(6, $ma->execCalls);
		$this->assertEquals('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ', $ma->execCalls[4]['statement']);
		$this->assertEquals('SET TRANSACTION READ ONLY', $ma->execCalls[5]['statement']);
	}

}