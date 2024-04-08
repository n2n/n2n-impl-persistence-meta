<?php

namespace meta\mysql;

use PHPUnit\Framework\TestCase;
use n2n\core\config\PersistenceUnitConfig;
use n2n\impl\persistence\meta\mysql\MysqlDialect;
use meta\test\MetaTestEnv;

class MysqlLoggingTest extends TestCase {

	function testLogInitAndTransactions() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, MysqlDialect::class,
				PersistenceUnitConfig::TIL_REPEATABLE_READ);

		$ma->pdo->getLogger()->setCapturing(true);

		$this->assertCount(0, $ma->execCalls);

		$ma->pdo->reconnect();

		$this->assertCount(3, $ma->execCalls);
		$this->assertCount(3, $ma->pdo->getLogger()->getEntries());
		$this->assertEquals('SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE', $ma->pdo->getLogger()->getEntries()[0]['sql']);
		$this->assertEquals('SET NAMES utf8mb4', $ma->pdo->getLogger()->getEntries()[1]['sql']);
		$this->assertEquals('SET SESSION sql_mode = \'STRICT_ALL_TABLES\'', $ma->pdo->getLogger()->getEntries()[2]['sql']);

		$this->assertCount(0, $ma->beginTransactionCalls);

		$ma->pdo->beginTransaction();
		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(4, $ma->execCalls);
		$this->assertCount(5, $ma->pdo->getLogger()->getEntries());
		$this->assertEquals('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE', $ma->pdo->getLogger()->getEntries()[3]['sql']);
		$this->assertEquals('begin transaction', $ma->pdo->getLogger()->getEntries()[4]['type']);

		$ma->pdo->commit();

		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(4, $ma->execCalls);
		$this->assertCount(6, $ma->pdo->getLogger()->getEntries());
		$this->assertEquals('commit', $ma->pdo->getLogger()->getEntries()[5]['type']);

		$ma->pdo->beginTransaction(true);

		$this->assertCount(2, $ma->beginTransactionCalls);
		$this->assertCount(6, $ma->execCalls);
		$this->assertCount(9, $ma->pdo->getLogger()->getEntries());
		$this->assertEquals('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ', $ma->pdo->getLogger()->getEntries()[6]['sql']);
		$this->assertEquals('SET TRANSACTION READ ONLY', $ma->pdo->getLogger()->getEntries()[7]['sql']);
		$this->assertEquals('begin transaction', $ma->pdo->getLogger()->getEntries()[8]['type']);

		$ma->pdo->rollBack();

		$this->assertCount(2, $ma->beginTransactionCalls);
		$this->assertCount(6, $ma->execCalls);
		$this->assertCount(10, $ma->pdo->getLogger()->getEntries());
		$this->assertEquals('rollback', $ma->pdo->getLogger()->getEntries()[9]['type']);
	}

}