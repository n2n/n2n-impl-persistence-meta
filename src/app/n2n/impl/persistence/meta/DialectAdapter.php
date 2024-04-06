<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\impl\persistence\meta;

use n2n\persistence\meta\data\QueryComparator;
use n2n\persistence\meta\Dialect;
use n2n\core\config\PersistenceUnitConfig;

abstract class DialectAdapter implements Dialect {

	protected string $readWriteTransactionIsolationLevel;
	protected string $readOnlyTransactionIsolationLevel;

	public function __construct(private PersistenceUnitConfig $persistenceUnitConfig) {
		$this->readWriteTransactionIsolationLevel = $this->persistenceUnitConfig->getReadWriteTransactionIsolationLevel();
		$this->readOnlyTransactionIsolationLevel = $this->persistenceUnitConfig->getReadOnlyTransactionIsolationLevel()
				?? $this->readWriteTransactionIsolationLevel;
	}

	protected function newPDO(string $dsnUri, string $user, ?string $password, array $options): \PDO {
		return new \PDO($dsnUri, $user, $password, $options);
	}

	protected function specifySessionTransactionIsolationLevel(\PDO $pdo): void {
		$pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL ' . $this->readWriteTransactionIsolationLevel);
	}

	protected function specifyNextTransactionIsolationLevel(\PDO $pdo, bool $readOnly): void {
		if ($this->readWriteTransactionIsolationLevel === $this->readOnlyTransactionIsolationLevel) {
			return;
		}

		$transactionIsolationLevel = ($readOnly ? $this->readOnlyTransactionIsolationLevel
				: $this->readWriteTransactionIsolationLevel);
		$pdo->exec('SET TRANSACTION ISOLATION LEVEL ' . $transactionIsolationLevel);
	}

	protected function specifyNextTransactionAccessMode(\PDO $pdo, bool $readOnly): void {
		if ($readOnly) {
			$pdo->exec('SET TRANSACTION READ ONLY');
		}
	}

	/**
	 * Quotes the like wildcard chars
	 * @param string $pattern
	 */
	public function escapeLikePattern(string $pattern): string {
		$esc = $this->getLikeEscapeCharacter();
		return str_replace(array($esc, QueryComparator::LIKE_WILDCARD_MANY_CHARS,
				QueryComparator::LIKE_WILDCARD_ONE_CHAR),
				array($esc . $esc,  $esc . QueryComparator::LIKE_WILDCARD_MANY_CHARS, $esc .
						QueryComparator::LIKE_WILDCARD_ONE_CHAR), $pattern);
	}
	/**
	 * Returns the escape character used in {@link Dialect::escapeLikePattern()}.
	 * @return string
	 */
	public function getLikeEscapeCharacter(): string {
		return self::DEFAULT_ESCAPING_CHARACTER;
	}

	public function createPDO(): \PDO {
		$options = [\PDO::ATTR_PERSISTENT => $this->persistenceUnitConfig->isPersistent()];

		if (!$this->persistenceUnitConfig->isSslVerify()) {
			$options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
		}

		if (null !== ($caPath = $this->persistenceUnitConfig->getSslCaCertificatePath())) {
			$options[\PDO::MYSQL_ATTR_SSL_CA] = $caPath;
		}

		$pdo = $this->newPDO($this->persistenceUnitConfig->getDsnUri(), $this->persistenceUnitConfig->getUser(),
				$this->persistenceUnitConfig->getPassword(), $options);

		$this->specifySessionTransactionIsolationLevel($pdo);

		return $pdo;
	}



	function beginTransaction(\PDO $pdo, bool $readOnly): void {
		$this->specifyNextTransactionIsolationLevel($pdo, $readOnly);
		$this->specifyNextTransactionAccessMode($pdo, $readOnly);

		$pdo->beginTransaction();
	}
}
