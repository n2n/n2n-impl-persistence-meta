<?php

namespace n2n\impl\persistence\meta\mysql;

use PHPUnit\Framework\TestCase;
use n2n\impl\persistence\meta\test\MetaTestEnv;

class MysqlDialectConfigTest extends TestCase {

	function testDefaultConfig() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, MysqlDialect::class);
		$ma->pdo->reconnect();

		$this->assertEquals([], $ma->pdoOptions);
	}

	function testPersistentConfig() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, MysqlDialect::class, persistent: true);
		$ma->pdo->reconnect();

		$this->assertEquals([\PDO::ATTR_PERSISTENT => true], $ma->pdoOptions);
	}

	function testSslConfig() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, MysqlDialect::class, sslVerify: false);
		$ma->pdo->reconnect();

		$this->assertEquals([\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false], $ma->pdoOptions);

		$ma = MetaTestEnv::setUpPdoMockAssembly($this, MysqlDialect::class, sslCaCertificatePath: '/holeradio');
		$ma->pdo->reconnect();

		$this->assertEquals([\PDO::MYSQL_ATTR_SSL_CA => '/holeradio'], $ma->pdoOptions);
	}

	function testAllOptions() {
		$ma = MetaTestEnv::setUpPdoMockAssembly($this, MysqlDialect::class, persistent: true,
				sslVerify: false, sslCaCertificatePath: '/holeradio');
		$ma->pdo->reconnect();

		$this->assertEquals([\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, \PDO::MYSQL_ATTR_SSL_CA => '/holeradio',
				\PDO::ATTR_PERSISTENT => true], $ma->pdoOptions);
	}

}