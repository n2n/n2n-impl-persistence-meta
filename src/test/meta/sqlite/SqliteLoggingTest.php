<?php

namespace meta\sqlite;

use PHPUnit\Framework\TestCase;
use n2n\core\config\PersistenceUnitConfig;
use n2n\impl\persistence\meta\mysql\MysqlDialect;
use meta\test\MetaTestEnv;
use n2n\impl\persistence\meta\mssql\MssqlDialect;
use n2n\impl\persistence\meta\sqlite\SqliteDialect;

class SqliteLoggingTest extends TestCase {
	
	function testLogInitAndTransactions() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, SqliteDialect::class,
				PersistenceUnitConfig::TIL_REPEATABLE_READ);

		$ma->pdo->getLogger()->setCapturing(true);

		$this->assertCount(0, $ma->execCalls);

		$ma->pdo->reconnect();

		$this->assertCount(1, $ma->execCalls);
		$this->assertCount(1, $ma->pdo->getLogger()->getEntries());
		$this->assertEquals('PRAGMA foreign_keys=ON', $ma->pdo->getLogger()->getEntries()[0]['sql']);

		$this->assertCount(0, $ma->beginTransactionCalls);

		$ma->pdo->beginTransaction();
		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(1, $ma->execCalls);
		$this->assertCount(2, $ma->pdo->getLogger()->getEntries());
		$this->assertEquals('begin transaction', $ma->pdo->getLogger()->getEntries()[1]['type']);

		$ma->pdo->commit();

		$this->assertCount(1, $ma->beginTransactionCalls);
		$this->assertCount(1, $ma->execCalls);
		$this->assertCount(3, $ma->pdo->getLogger()->getEntries());
		$this->assertEquals('commit', $ma->pdo->getLogger()->getEntries()[2]['type']);

		$ma->pdo->beginTransaction(true);

		$this->assertCount(2, $ma->beginTransactionCalls);
		$this->assertCount(1, $ma->execCalls);
		$this->assertCount(4, $ma->pdo->getLogger()->getEntries());
		$this->assertEquals('begin transaction', $ma->pdo->getLogger()->getEntries()[3]['type']);

		$ma->pdo->rollBack();

		$this->assertCount(2, $ma->beginTransactionCalls);
		$this->assertCount(1, $ma->execCalls);
		$this->assertCount(5, $ma->pdo->getLogger()->getEntries());
		$this->assertEquals('rollback', $ma->pdo->getLogger()->getEntries()[4]['type']);
	}

}