<?php

namespace n2n\impl\persistence\meta\sqlite;

use PHPUnit\Framework\TestCase;
use n2n\impl\persistence\meta\test\MetaTestEnv;
use n2n\spec\tx\TransactionIsolationLevel;
use n2n\spec\dbo\err\DboException;

class SqliteDialectInitAndTransactionTest extends TestCase {

	/**
	 * @throws DboException
	 */
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

		$ma->pdo->beginTransaction(isolationLevel: TransactionIsolationLevel::TIL_READ_COMMITTED);

		$this->assertCount(3, $ma->beginTransactionCalls);
		$this->assertCount(1, $ma->execCalls);

		$ma->pdo->commit();
	}

	/**
	 * @throws DboException
	 */
	function testWithDifferentTransactionIsolationLevel() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, SqliteDialect::class,
				TransactionIsolationLevel::TIL_REPEATABLE_READ);

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