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
namespace n2n\impl\persistence\meta\mysql;

use n2n\util\io\stream\InputStream;
use n2n\persistence\meta\data\common\CommonInsertStatementBuilder;
use n2n\persistence\meta\data\common\CommonDeleteStatementBuilder;
use n2n\persistence\meta\data\common\CommonUpdateStatementBuilder;
use n2n\persistence\meta\data\common\CommonSelectStatementBuilder;
use n2n\persistence\meta\structure\InvalidColumnAttributesException;
use n2n\spec\dbo\meta\structure\IntegerColumn;
use n2n\spec\dbo\meta\structure\Column;
use n2n\persistence\Pdo;
use n2n\impl\persistence\meta\DialectAdapter;
use n2n\core\config\PersistenceUnitConfig;
use n2n\spec\dbo\meta\structure\MetaManager;
use n2n\spec\dbo\meta\data\SelectStatementBuilder;
use n2n\spec\dbo\meta\data\UpdateStatementBuilder;
use n2n\spec\dbo\meta\data\InsertStatementBuilder;
use n2n\spec\dbo\meta\data\DeleteStatementBuilder;
use n2n\persistence\meta\OrmDialectConfig;
use n2n\persistence\meta\data\Importer;
use n2n\persistence\meta\data\common\CommonSelectLockBuilder;
use n2n\persistence\PDOOperations;
use n2n\persistence\PdoLogger;

class MysqlDialect extends DialectAdapter {

	public function getName(): string {
		return 'Mysql';
	}

	protected function determinePdoOptions(): array {
		$options = parent::determinePdoOptions();

		if (!$this->persistenceUnitConfig->isSslVerify()) {
			$options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
		}

		if (null !== ($caPath = $this->persistenceUnitConfig->getSslCaCertificatePath())) {
			$options[\PDO::MYSQL_ATTR_SSL_CA] = $caPath;
		}

		return $options;
	}

	protected function specifySessionSettings(\PDO $pdo, PdoLogger $pdoLogger = null): void {
		parent::specifySessionSettings($pdo, $pdoLogger);
		PDOOperations::exec($pdoLogger, $pdo, 'SET NAMES utf8mb4');
		PDOOperations::exec($pdoLogger, $pdo,'SET SESSION sql_mode = \'STRICT_ALL_TABLES\'');
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createMetaManager()
	 * @return MetaManager
	 */
	public function createMetaManager(Pdo $dbh): MetaManager {
		return new MysqlMetaManager($dbh);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::quoteField()
	 */
	public function quoteField(string $str): string {
		return "`" . str_replace("`", "``", (string) $str) . "`";
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createSelectStatementBuilder()
	 * @return SelectStatementBuilder
	 */
	public function createSelectStatementBuilder(Pdo $dbh): SelectStatementBuilder {
		return new CommonSelectStatementBuilder($dbh, new MysqlQueryFragmentBuilderFactory($dbh),
				new CommonSelectLockBuilder());
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createUpdateStatementBuilder()
	 * @return UpdateStatementBuilder
	 */
	public function createUpdateStatementBuilder(Pdo $dbh): UpdateStatementBuilder {
		return new CommonUpdateStatementBuilder($dbh, new MysqlQueryFragmentBuilderFactory($dbh));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createInsertStatementBuilder()
	 * @return InsertStatementBuilder
	 */
	public function createInsertStatementBuilder(Pdo $dbh): InsertStatementBuilder {
		return new CommonInsertStatementBuilder($dbh, new MysqlQueryFragmentBuilderFactory($dbh));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createDeleteStatementBuilder()
	 * @return DeleteStatementBuilder
	 */
	public function createDeleteStatementBuilder(Pdo $dbh): DeleteStatementBuilder {
		return new CommonDeleteStatementBuilder($dbh, new MysqlQueryFragmentBuilderFactory($dbh));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::getOrmDialectConfig()
	 * @return OrmDialectConfig
	 */
	public function getOrmDialectConfig(): OrmDialectConfig {
		return new MysqlOrmDialectConfig();
	}

	public function isLastInsertIdSupported(): bool {
		return true;
	}
	
	public function generateSequenceValue(Pdo $dbh, string $sequenceName): ?string {
		return null;
	}
	
	public function applyIdentifierGeneratorToColumn(Pdo $dbh, Column $column, string $sequenceName = null) {
		if (!($column instanceof IntegerColumn)) {
			throw new InvalidColumnAttributesException('Invalid generated identifier column \"' . $column->getName() 
					. '\" for Table \"' . $column->getTable()->getName() 
					. '\". Column must be of type \"' . IntegerColumn::class . "\". Given column type is \"" . get_class($column) . "\"");
		}
		//the Value automatically gets Generated Identifier if the column type is Integer
		//this triggers a changerequest -> type will be changed to INTEGER
		$column->setNullAllowed(false);
		$column->setValueGenerated(true);
		return $column;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createImporter()
	 * @return Importer
	 */
	public function createImporter(Pdo $dbh, InputStream $inputStream): Importer {
		return new MysqlImporter($dbh, $inputStream);
	}
}