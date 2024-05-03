<?php

namespace n2n\impl\persistence\meta\mssql;

use PHPUnit\Framework\TestCase;
use n2n\core\config\PersistenceUnitConfig;
use n2n\impl\persistence\meta\test\MetaTestEnv;

class MssqlLoggingTest extends TestCase {
	
	function testLogInitAndTransactions() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, MssqlDialect::class,
				PersistenceUnitConfig::TIL_REPEATABLE_READ);

		$ma->pdo->getLogger()->setCapturing(true);

		$this->assertCount(0, $ma->execCalls);

		$ma->pdo->reconnect();

		$this->assertCount(1, $ma->execCalls);
		$this->assertCount(1, $ma->pdo->getLogger()->getEntries());
		$this->assertEquals('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE', $ma->pdo->getLogger()->getEntries()[0]['sql']);

		$this->assertCount(0, $ma->beginTransactionCalls);

		$ma->pdo->beginTransaction();
		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(2, $ma->execCalls);
		$this->assertCount(3, $ma->pdo->getLogger()->getEntries());
		$this->assertEquals('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE', $ma->pdo->getLogger()->getEntries()[1]['sql']);
		$this->assertEquals('begin transaction', $ma->pdo->getLogger()->getEntries()[2]['type']);

		$ma->pdo->commit();

		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(2, $ma->execCalls);
		$this->assertCount(4, $ma->pdo->getLogger()->getEntries());
		$this->assertEquals('commit', $ma->pdo->getLogger()->getEntries()[3]['type']);

		$ma->pdo->beginTransaction(true);

		$this->assertCount(2, $ma->beginTransactionCalls);
		$this->assertCount(3, $ma->execCalls);
		$this->assertCount(6, $ma->pdo->getLogger()->getEntries());
		$this->assertEquals('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ', $ma->pdo->getLogger()->getEntries()[4]['sql']);
		$this->assertEquals('begin transaction', $ma->pdo->getLogger()->getEntries()[5]['type']);

		$ma->pdo->rollBack();

		$this->assertCount(2, $ma->beginTransactionCalls);
		$this->assertCount(3, $ma->execCalls);
		$this->assertCount(7, $ma->pdo->getLogger()->getEntries());
		$this->assertEquals('rollback', $ma->pdo->getLogger()->getEntries()[6]['type']);
	}

}