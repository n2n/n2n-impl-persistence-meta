<?php

namespace n2n\impl\persistence\meta;

use PHPUnit\Framework\TestCase;
use n2n\core\config\PersistenceUnitConfig;
use n2n\impl\persistence\meta\sqlite\SqliteDialect;
use n2n\persistence\PdoLogger;

class DialectAdapterTest extends TestCase {


	private function createPersistenceUnitConfig(bool $persistent = false, bool $sslVerify = true) {
		return new PersistenceUnitConfig('holeradio', 'sqlite::memory:', '', '',
				PersistenceUnitConfig::TIL_SERIALIZABLE, SqliteDialect::class,
				persistent: $persistent);
	}

	function testPersistent() {
		$config = $this->createPersistenceUnitConfig();
		$dialect = new SqliteDialect($config);
		$pdo = $dialect->createPDO($this->createMock(PdoLogger::class));

		$this->assertFalse($pdo->getAttribute(\PDO::ATTR_PERSISTENT));

		$config = $this->createPersistenceUnitConfig(persistent: true);
		$dialect = new SqliteDialect($config);
		$pdo = $dialect->createPDO($this->createMock(PdoLogger::class));

		$this->assertTrue($pdo->getAttribute(\PDO::ATTR_PERSISTENT));
	}

}