<?php

namespace n2n\impl\persistence\meta\sqlite;

use PHPUnit\Framework\TestCase;
use n2n\core\config\PersistenceUnitConfig;
use n2n\impl\persistence\meta\test\MetaTestEnv;

class SqliteDialectInitAndTransactionTest extends TestCase {

	function testWithSameTransactionIsolationLevel() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, SqliteDialect::class);

		$this->assertCount(0, $ma->execCalls);

		$ma->pdo->reconnect();

		$this->assertCount(1, $ma->execCalls);
		$this->assertEquals('PRAGMA foreign_keys=ON', $ma->execCalls[0]['statement']);

		$this->assertCount(0, $ma->beginTransactionCalls);

		$ma->pdo->beginTransaction();

		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(1, $ma->execCalls);

		$ma->pdo->commit();

		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(1, $ma->execCalls);

		$ma->pdo->beginTransaction(true);

		$this->assertCount(2, $ma->beginTransactionCalls);
		$this->assertCount(1, $ma->execCalls);

		$ma->pdo->commit();

		$ma->pdo->beginTransaction(isolationLevel: PersistenceUnitConfig::TIL_READ_COMMITTED);

		$this->assertCount(3, $ma->beginTransactionCalls);
		$this->assertCount(1, $ma->execCalls);

		$ma->pdo->commit();
	}

	function testWithDifferentTransactionIsolationLevel() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, SqliteDialect::class,
				PersistenceUnitConfig::TIL_REPEATABLE_READ);

		$this->assertCount(0, $ma->execCalls);

		$ma->pdo->reconnect();

		$this->assertCount(1, $ma->execCalls);
		$this->assertEquals('PRAGMA foreign_keys=ON', $ma->execCalls[0]['statement']);

		$this->assertCount(0, $ma->beginTransactionCalls);

		$ma->pdo->beginTransaction();
		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(1, $ma->execCalls);

		$ma->pdo->commit();

		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(1, $ma->execCalls);

		$ma->pdo->beginTransaction(true);

		$this->assertCount(2, $ma->beginTransactionCalls);
		$this->assertCount(1, $ma->execCalls);
	}

}