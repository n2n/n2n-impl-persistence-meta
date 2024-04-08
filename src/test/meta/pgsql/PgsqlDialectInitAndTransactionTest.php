<?php

namespace meta\pgsql;

use PHPUnit\Framework\TestCase;
use n2n\core\config\PersistenceUnitConfig;
use n2n\impl\persistence\meta\mysql\MysqlDialect;
use meta\test\MetaTestEnv;
use n2n\impl\persistence\meta\pgsql\PgsqlDialect;

class PgsqlDialectInitAndTransactionTest extends TestCase {

	function testWithSameTransactionIsolationLevel() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, PgsqlDialect::class,
				readOnlyTransactionIsolationLevel: PersistenceUnitConfig::TIL_SERIALIZABLE);;

		$this->assertCount(0, $ma->execCalls);

		$ma->pdo->reconnect();

		$this->assertCount(1, $ma->execCalls);
		$this->assertEquals('SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE', $ma->execCalls[0]['statement']);

		$this->assertCount(0, $ma->beginTransactionCalls);

		$ma->pdo->beginTransaction();

		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(1, $ma->execCalls);

		$ma->pdo->commit();

		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(1, $ma->execCalls);

		$ma->pdo->beginTransaction(true);

		$this->assertCount(2, $ma->beginTransactionCalls);
		$this->assertCount(2, $ma->execCalls);
		$this->assertEquals('SET TRANSACTION READ ONLY', $ma->execCalls[1]['statement']);
	}

	function testWithDifferentTransactionIsolationLevel() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, PgsqlDialect::class,
				PersistenceUnitConfig::TIL_REPEATABLE_READ);

		$this->assertCount(0, $ma->execCalls);

		$ma->pdo->reconnect();

		$this->assertCount(1, $ma->execCalls);
		$this->assertEquals('SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE', $ma->execCalls[0]['statement']);

		$this->assertCount(0, $ma->beginTransactionCalls);

		$ma->pdo->beginTransaction();
		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(2, $ma->execCalls);
		$this->assertEquals('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE', $ma->execCalls[1]['statement']);
		$this->assertTrue($ma->execCalls[1]['_nr'] < $ma->beginTransactionCalls[0]['_nr']);

		$ma->pdo->commit();

		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(2, $ma->execCalls);

		$ma->pdo->beginTransaction(true);

		$this->assertCount(2, $ma->beginTransactionCalls);
		$this->assertCount(4, $ma->execCalls);
		$this->assertEquals('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ', $ma->execCalls[2]['statement']);
		$this->assertEquals('SET TRANSACTION READ ONLY', $ma->execCalls[3]['statement']);
		$this->assertTrue($ma->execCalls[2]['_nr'] < $ma->beginTransactionCalls[1]['_nr']);
	}

}