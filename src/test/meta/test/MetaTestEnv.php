<?php

namespace meta\test;

use PHPUnit\Framework\TestCase;
use n2n\core\config\PersistenceUnitConfig;
use PHPUnit\Framework\MockObject\MockObject;
use n2n\persistence\Pdo;
use n2n\persistence\meta\Dialect;

class MetaTestEnv {

	static function setUpPdoMockAssembly(TestCase $testCase, string $dialectClass,
			string $readOnlyTransactionIsolationLevel = PersistenceUnitConfig::TIL_REPEATABLE_READ,
			bool $persistent = false, bool $sslVerify = true, string $sslCaCertificatePath = null): PdoMockAssembly {
		$config = new PersistenceUnitConfig('holeradio', 'mysql:..', 'user', null,
				PersistenceUnitConfig::TIL_SERIALIZABLE, $dialectClass,
				$sslVerify, $sslCaCertificatePath, $persistent,
				readOnlyTransactionIsolationLevel: $readOnlyTransactionIsolationLevel);

		$nativePdoMock = $testCase->getMockBuilder(\PDO::class)
				->disableOriginalConstructor()
				->getMock();
		$dialectMock = $testCase->getMockBuilder($dialectClass)
				->setConstructorArgs([$config])
				->onlyMethods(['newPDO'])->getMock();

		assert($dialectMock instanceof Dialect);
		$pdo = new Pdo('holeradio', $dialectMock);

		assert($dialectMock instanceof MockObject);
		return new PdoMockAssembly($nativePdoMock, $dialectMock, $pdo);
	}
}


class PdoMockAssembly {
	public ?array $pdoOptions = null;

	public bool $inTransaction = false;

	private int $callCounter = 0;
	public array $execCalls = [];
	public array $beginTransactionCalls = [];

	function __construct(public MockObject $nativePdoMock, public MockObject $dialectMock, public Pdo $pdo) {
		$dialectMock->expects(TestCase::once())->method('newPDO')
				->willReturnCallback(function (string $dsnUri, string $user, ?string $password, array $options) {
					$this->pdoOptions = $options;
					return $this->nativePdoMock;
				});

		$nativePdoMock->method('exec')->will(
				TestCase::returnCallback(function ($statment) use (&$execStatements) {
					$this->execCalls[] = ['_nr' => ++$this->callCounter, 'statement' => $statment];
					return 0;
				}));

		$this->nativePdoMock->method('inTransaction')->willReturnCallback(fn () => $this->inTransaction);
		$this->nativePdoMock->method('beginTransaction')->willReturnCallback(function () {
			$this->beginTransactionCalls[] = ['_nr' => ++$this->callCounter];
			$this->inTransaction = true;
			return true;
		});
		$this->nativePdoMock->method('commit')->willReturnCallback(function () {
			$this->inTransaction = false;
			return true;
		});
		$this->nativePdoMock->method('rollback')->willReturnCallback(function () {
			$this->inTransaction = false;
			return true;
		});
	}
}
