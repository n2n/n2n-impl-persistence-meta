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
namespace n2n\impl\persistence\meta\pgsql;

use n2n\util\io\stream\InputStream;
use n2n\spec\dbo\meta\structure\Column;
use n2n\persistence\Pdo;
use n2n\impl\persistence\meta\DialectAdapter;
use n2n\persistence\meta\structure\InvalidColumnAttributesException;
use n2n\core\config\PersistenceUnitConfig;
use n2n\persistence\meta\data\common\CommonSelectStatementBuilder;
use n2n\persistence\meta\data\common\CommonUpdateStatementBuilder;
use n2n\persistence\meta\data\common\CommonInsertStatementBuilder;
use n2n\persistence\meta\data\common\CommonDeleteStatementBuilder;
use n2n\spec\dbo\meta\data\SelectStatementBuilder;
use n2n\spec\dbo\meta\data\UpdateStatementBuilder;
use n2n\spec\dbo\meta\data\InsertStatementBuilder;
use n2n\spec\dbo\meta\data\DeleteStatementBuilder;
use n2n\persistence\meta\data\Importer;
use n2n\persistence\meta\OrmDialectConfig;
use n2n\spec\dbo\meta\structure\MetaManager;
use n2n\persistence\meta\data\common\CommonSelectLockBuilder;

class PgsqlDialect extends DialectAdapter {

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'Pgsql';
	}

//	public function createPDO(PersistenceUnitConfig $persistenceUnitConfig): \PDO {
//		$pdo = parent::createPDO($persistenceUnitConfig);
//
//		return $pdo;
//	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createMetaManager()
	 * @return MetaManager
	 */
	public function createMetaManager(Pdo $dbh): MetaManager {
		return new PgsqlMetaManager($dbh);
	}

	/**
	 *
	 * @param String $str
	 */
	public function quoteField(string $str): string {
		return '"' . str_replace('"', '', (string) $str) . '"';
	}

	public function escapeLikePattern(string $pattern): string {
		return str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $pattern);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createSelectStatementBuilder()
	 * @return SelectStatementBuilder
	 */
	public function createSelectStatementBuilder(Pdo $dbh): SelectStatementBuilder {
		return new CommonSelectStatementBuilder($dbh, new PgsqlQueryFragmentBuilderFactory($dbh),
				new CommonSelectLockBuilder());
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createUpdateStatementBuilder()
	 * @return UpdateStatementBuilder
	 */
	public function createUpdateStatementBuilder(Pdo $dbh): UpdateStatementBuilder {
		return new CommonUpdateStatementBuilder($dbh, new PgsqlQueryFragmentBuilderFactory($dbh));
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createInsertStatementBuilder()
	 * @return InsertStatementBuilder
	 */
	public function createInsertStatementBuilder(Pdo $dbh): InsertStatementBuilder {
		return new CommonInsertStatementBuilder($dbh, new PgsqlQueryFragmentBuilderFactory($dbh));
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createDeleteStatementBuilder()
	 * @return DeleteStatementBuilder
	 */
	public function createDeleteStatementBuilder(Pdo $dbh): DeleteStatementBuilder {
		return new CommonDeleteStatementBuilder($dbh, new PgsqlQueryFragmentBuilderFactory($dbh));
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createImporter()
	 * @return Importer
	 */
	public function createImporter(Pdo $dbh, InputStream $inputStream): Importer {
		return new PgsqlImporter($dbh, $inputStream);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::getOrmDialectConfig()
	 * @return OrmDialectConfig
	 */
	public function getOrmDialectConfig(): OrmDialectConfig {
		return new PgsqlOrmDialectConfig();
	}

	/**
	 * @return bool
	 */
	public function isLastInsertIdSupported(): bool {
		return true;
	}

	/**
	 * @param Pdo $dbh
	 * @param string $sequenceName
	 * @return string|null
	 */
	public function generateSequenceValue(Pdo $dbh, string $sequenceName): ?string {
		$stmt = $dbh->prepare('SELECT nextval(?) AS sequence_value');
		$stmt->execute(array($sequenceName));
		$result = $stmt->fetch(\PDO::FETCH_ASSOC);

		if (!is_null($result)) {
			return $result['sequence_value'];
		}
		return null;
	}

	/**
	 * @param Pdo $dbh
	 * @param Column $column
	 * @param string $sequenceName
	 */
	public function applyIdentifierGeneratorToColumn(Pdo $dbh, Column $column, string $sequenceName): void {
		if (!($column instanceof PgsqlIntegerColumn)) {
			throw new InvalidColumnAttributesException('Invalid generated identifier column "' . $column->getName()
					. 'Column  must be of type "n2n\impl\persistence\meta\pgsql\PgsqlIntegerColumn, "' . get_class($column) . '" given. ');
		}

		$column->setNullAllowed(false);
		$column->setValueGenerated(true);
	}
}