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

use n2n\io\InputStream;
use n2n\persistence\meta\structure\Column;
use n2n\persistence\Pdo;
use n2n\impl\persistence\meta\DialectAdapter;
use n2n\persistence\meta\structure\InvalidColumnAttributesException;
use n2n\persistence\PersistenceUnitConfig;
use n2n\persistence\meta\data\common\CommonSelectStatementBuilder;
use n2n\persistence\meta\data\common\CommonUpdateStatementBuilder;
use n2n\persistence\meta\data\common\CommonInsertStatementBuilder;
use n2n\persistence\meta\data\common\CommonDeleteStatementBuilder;

class PgsqlDialect extends DialectAdapter {
	public function __construct() {}
	/**
	 * @return string
	 */
	public function getName() {
		return 'Pgsql';
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::initializeConnection()
	 */
	public function initializeConnection(Pdo $dbh, PersistenceUnitConfig $data) {

	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createMetaDatabase()
	 */
	public function createMetaDatabase(Pdo $dbh) {
		return new PgsqlDatabase($dbh);
	}
	/**
	 *
	 * @param String $str
	 */
	public function quoteField($str) {
		return '"' . str_replace('"', '', (string) $str) . '"';
	}

	public function escapeLikePattern($pattern) {
		return str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $pattern);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createSelectStatementBuilder()
	 */
	public function createSelectStatementBuilder(Pdo $dbh) {
		return new CommonSelectStatementBuilder($dbh);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createUpdateStatementBuilder()
	 */
	public function createUpdateStatementBuilder(Pdo $dbh) {
		return new CommonUpdateStatementBuilder($dbh);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createInsertStatementBuilder()
	 */
	public function createInsertStatementBuilder(Pdo $dbh) {
		return new CommonInsertStatementBuilder($dbh);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createDeleteStatementBuilder()
	 */
	public function createDeleteStatementBuilder(Pdo $dbh) {
		return new CommonDeleteStatementBuilder($dbh);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::createImporter()
	 */
	public function createImporter(Pdo $dbh, InputStream $inputStream) {
		return new PgsqlImporter($dbh, $inputStream);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Dialect::getOrmDialectConfig()
	 */
	public function getOrmDialectConfig() {
		return new PgsqlOrmDialectConfig();
	}

	/**
	 * @return bool
	 */
	public function isLastInsertIdSupported() {
		return true;
	}

	/**
	 * @param string $sequenceName
	 * @return mixed
	 */
	public function generateSequenceValue(Pdo $dbh, $sequenceName) {
		$stmt = $dbh->prepare('SELECT nextval(?) AS sequence_value');
		$stmt->execute(array($sequenceName));
		$result = $stmt->fetch(Pdo::FETCH_ASSOC);

		if (!is_null($result)) {
			return $result['sequence_value'];
		}
		return null;
	}

	/**
	 * @param Column $column
	 */
	public function applyIdentifierGeneratorToColumn(Pdo $dbh, Column $column, $sequenceName) {
		if (!($column instanceof PgsqlIntegerColumn)) {
			throw new InvalidColumnAttributesException('Invalid generated identifier column "' . $column->getName()
					. 'Column  must be of type "n2n\impl\persistence\meta\pgsql\PgsqlIntegerColumn, "' . get_class($column) . '" given. ');
		}

		$column->setNullAllowed(false);
		$column->setValueGenerated(true);
	}
}