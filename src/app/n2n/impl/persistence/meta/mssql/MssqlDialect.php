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
namespace n2n\impl\persistence\meta\mssql;

use n2n\util\io\stream\InputStream;
use n2n\persistence\meta\data\common\CommonInsertStatementBuilder;
use n2n\persistence\meta\structure\InvalidColumnAttributesException;
use n2n\persistence\meta\structure\IntegerColumn;
use n2n\persistence\meta\data\common\CommonDeleteStatementBuilder;
use n2n\persistence\meta\data\common\CommonUpdateStatementBuilder;
use n2n\persistence\meta\data\common\CommonSelectStatementBuilder;
use n2n\persistence\meta\structure\Column;
use n2n\core\config\PersistenceUnitConfig;
use n2n\persistence\Pdo;
use n2n\impl\persistence\meta\DialectAdapter;
use n2n\spec\dbo\meta\data\SelectStatementBuilder;
use n2n\spec\dbo\meta\data\UpdateStatementBuilder;
use n2n\spec\dbo\meta\data\InsertStatementBuilder;
use n2n\spec\dbo\meta\data\DeleteStatementBuilder;
use n2n\persistence\meta\OrmDialectConfig;
use n2n\persistence\meta\data\Importer;
use n2n\persistence\meta\MetaManager;
use n2n\persistence\PdoLogger;
use n2n\persistence\PDOOperations;

class MssqlDialect extends DialectAdapter {

	public function getName(): string {
		return 'Mssql';
	}

	protected function specifySessionSettings(\PDO $pdo, PdoLogger $pdoLogger = null): void {
		PDOOperations::exec($pdoLogger, $pdo,
				'SET TRANSACTION ISOLATION LEVEL ' . $this->readWriteTransactionIsolationLevel);
	}

	protected function specifyNextTransactionAccessMode(\PDO $pdo, bool $readOnly, PdoLogger $pdoLogger = null): void {
		// ACCESS MODE (e. g. READ ONLY) not supported
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createMetaManager()
	 * @return MetaManager
	 */
	public function createMetaManager(Pdo $dbh): MetaManager {
		return new MssqlMetaManager($dbh);
	}
	
	public function quoteField(string $str): string {
		return "[" . str_replace("]", "]]", (string) $str) . "]";
	}
	
	public function escapeLikePattern(string $pattern): string {
		return $pattern;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createSelectStatementBuilder()
	 * @return SelectStatementBuilder
	 */
	public function createSelectStatementBuilder(Pdo $dbh): SelectStatementBuilder {
		return new CommonSelectStatementBuilder($dbh, new MssqlQueryFragmentBuilderFactory($dbh), null);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createUpdateStatementBuilder()
	 * @return UpdateStatementBuilder
	 */
	public function createUpdateStatementBuilder(Pdo $dbh): UpdateStatementBuilder {
		return new CommonUpdateStatementBuilder($dbh, new MssqlQueryFragmentBuilderFactory($dbh));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createInsertStatementBuilder()
	 * @return InsertStatementBuilder
	 */
	public function createInsertStatementBuilder(Pdo $dbh): InsertStatementBuilder {
		return new CommonInsertStatementBuilder($dbh, new MssqlQueryFragmentBuilderFactory($dbh));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createDeleteStatementBuilder()
	 * @return DeleteStatementBuilder
	 */
	public function createDeleteStatementBuilder(Pdo $dbh): DeleteStatementBuilder {
		return new CommonDeleteStatementBuilder($dbh, new MssqlQueryFragmentBuilderFactory($dbh));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::getOrmDialectConfig()
	 * @return OrmDialectConfig
	 */
	public function getOrmDialectConfig(): OrmDialectConfig {
		return new MssqlOrmDialectConfig();
	}
	
	public function isLastInsertIdSupported(): bool {
		return true;
	}
	
	public function generateSequenceValue(Pdo $dbh, string $sequenceName): ?string {
		return null;
	}

	public function applyIdentifierGeneratorToColumn(Pdo $dbh, Column $column, string $sequenceName = null) {
		if (!($column instanceof IntegerColumn)) {
			throw new InvalidColumnAttributesException('Invalid generated identifier "' . $column->getName() 
					. '" in table "' . $column->getTable()->getName() 
					. '". Column of type ' . IntegerColumn::class . ' required, ' . get_class($column) . ' given');
		}
		//this triggers a changerequest -> type will be changed to INTEGER
		$column->setGeneratedIdentifier(true);
		$column->setNullAllowed(false);
		return $column;
	}
	
	public function isColumnIdentifierGenerator($column): bool {
		return ($column instanceof MssqlIntegerColumn && $column->isGeneratedIdentifier());
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createImporter()
	 * @return Importer
	 */
	public function createImporter(Pdo $dbh, InputStream $inputStream): Importer {
		return new MssqlImporter($dbh, $inputStream);
	}
}
