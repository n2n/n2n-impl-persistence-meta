<?php

namespace meta\oracle;

use PHPUnit\Framework\TestCase;
use n2n\core\config\PersistenceUnitConfig;
use n2n\impl\persistence\meta\mysql\MysqlDialect;
use meta\test\MetaTestEnv;
use n2n\impl\persistence\meta\oracle\OracleDialect;

class OracleDialectInitAndTransactionTest extends TestCase {

	function testWithSameTransactionIsolationLevel() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, OracleDialect::class,
				readOnlyTransactionIsolationLevel: PersistenceUnitConfig::TIL_SERIALIZABLE);

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
	}

	function testWithDifferentTransactionIsolationLevel() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, OracleDialect::class,
				PersistenceUnitConfig::TIL_REPEATABLE_READ);

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
	}

}