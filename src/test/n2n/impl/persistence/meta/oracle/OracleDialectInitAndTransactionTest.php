<?php

namespace n2n\impl\persistence\meta\oracle;

use PHPUnit\Framework\TestCase;
use n2n\impl\persistence\meta\test\MetaTestEnv;
use n2n\spec\tx\TransactionIsolationLevel;
use n2n\spec\dbo\err\DboException;

class OracleDialectInitAndTransactionTest extends TestCase {

	/**
	 * @throws DboException
	 */
	function testWithSameTransactionIsolationLevel() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, OracleDialect::class,
				readOnlyTransactionIsolationLevel: TransactionIsolationLevel::TIL_SERIALIZABLE);

		$this->assertCount(0, $ma->execCalls);

		$ma->pdo->reconnect();

		$this->assertCount(4, $ma->execCalls);
		$this->assertEquals('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE', $ma->execCalls[0]['statement']);
		$this->assertEquals('ALTER SESSION SET NLS_TIMESTAMP_FORMAT = ', $ma->execCalls[1]['statement']);
		$this->assertEquals('ALTER SESSION SET NLS_DATE_FORMAT = ', $ma->execCalls[2]['statement']);
		$this->assertEquals('ALTER SESSION SET NLS_TIMESTAMP_TZ_FORMAT = ', $ma->execCalls[3]['statement']);
		$this->assertCount(0, $ma->beginTransactionCalls);

		$ma->pdo->beginTransaction();

		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(4, $ma->execCalls);

		$ma->pdo->commit();

		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(4, $ma->execCalls);

		$ma->pdo->beginTransaction(true);

		$this->assertCount(2, $ma->beginTransactionCalls);
		$this->assertCount(4, $ma->execCalls);

		$ma->pdo->commit();

		$ma->pdo->beginTransaction(isolationLevel: TransactionIsolationLevel::TIL_READ_COMMITTED);

		$this->assertCount(3, $ma->beginTransactionCalls);
		$this->assertCount(5, $ma->execCalls);
		$this->assertEquals('SET TRANSACTION ISOLATION LEVEL READ COMMITTED', $ma->execCalls[4]['statement']);

		$ma->pdo->commit();
	}

	/**
	 * @throws DboException
	 */
	function testWithDifferentTransactionIsolationLevel() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, OracleDialect::class,
				TransactionIsolationLevel::TIL_REPEATABLE_READ);

		$this->assertCount(0, $ma->execCalls);

		$ma->pdo->reconnect();

		$this->assertCount(4, $ma->execCalls);
		$this->assertEquals('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE', $ma->execCalls[0]['statement']);
		$this->assertEquals('ALTER SESSION SET NLS_TIMESTAMP_FORMAT = ', $ma->execCalls[1]['statement']);
		$this->assertEquals('ALTER SESSION SET NLS_DATE_FORMAT = ', $ma->execCalls[2]['statement']);
		$this->assertEquals('ALTER SESSION SET NLS_TIMESTAMP_TZ_FORMAT = ', $ma->execCalls[3]['statement']);
		$this->assertCount(0, $ma->beginTransactionCalls);

		$ma->pdo->beginTransaction();
		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(5, $ma->execCalls);
		$this->assertEquals('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE', $ma->execCalls[4]['statement']);
		$this->assertTrue($ma->execCalls[4]['_nr'] < $ma->beginTransactionCalls[0]['_nr']);

		$ma->pdo->commit();

		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(5, $ma->execCalls);

		$ma->pdo->beginTransaction(true);

		$this->assertCount(2, $ma->beginTransactionCalls);
		$this->assertCount(6, $ma->execCalls);
		$this->assertEquals('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ', $ma->execCalls[5]['statement']);
		$this->assertTrue($ma->execCalls[5]['_nr'] < $ma->beginTransactionCalls[1]['_nr']);

		$ma->pdo->commit();

		$ma->pdo->beginTransaction(isolationLevel: TransactionIsolationLevel::TIL_READ_COMMITTED);

		$this->assertCount(3, $ma->beginTransactionCalls);
		$this->assertCount(7, $ma->execCalls);
		$this->assertEquals('SET TRANSACTION ISOLATION LEVEL READ COMMITTED', $ma->execCalls[6]['statement']);
		$this->assertTrue($ma->execCalls[6]['_nr'] < $ma->beginTransactionCalls[2]['_nr']);

		$ma->pdo->commit();
	}

}