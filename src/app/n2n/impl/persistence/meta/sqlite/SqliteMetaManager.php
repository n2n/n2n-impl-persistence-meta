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

use n2n\persistence\Pdo;
use n2n\persistence\meta\structure\Backuper;
use n2n\persistence\meta\structure\Table;
use n2n\persistence\meta\structure\common\MetaManagerAdapter;
use n2n\persistence\meta\structure\common\DatabaseAdapter;
use n2n\persistence\meta\structure\MetaEntity;
use n2n\impl\persistence\meta\sqlite\SqliteMetaEntityBuilder;
use n2n\impl\persistence\meta\sqlite\SqliteBackuper;
use n2n\impl\persistence\meta\sqlite\management\SqliteAlterMetaEntityRequest;
use n2n\impl\persistence\meta\sqlite\management\SqliteCreateMetaEntityRequest;
use n2n\impl\persistence\meta\sqlite\management\SqliteDropMetaEntityRequest;
use n2n\impl\persistence\meta\sqlite\management\SqliteRenameMetaEntityRequest;
use n2n\impl\persistence\meta\sqlite\SqliteDatabase;

class SqliteMetaManager extends MetaManagerAdapter {
	private $metaEntityBuilder;
	
	public function __construct(Pdo $dbh) {
		parent::__construct($dbh);
		$this->metaEntityBuilder = new SqliteMetaEntityBuilder($dbh);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\Database::createBackuper()
	 * @return Backuper
	 */
	public function createBackuper(array $metaEnities = null): Backuper {
		return new SqliteBackuper($this->dbh, $this->createDatabase(), $metaEnities);
	}
	
	protected function createAlterMetaEntityRequest(MetaEntity $metaEntity) {
		return new SqliteAlterMetaEntityRequest($metaEntity);
	}
	
	protected function createCreateMetaEntityRequest(MetaEntity $metaEntity) {
		return new SqliteCreateMetaEntityRequest($metaEntity);
	}
	
	protected function createDropMetaEntityRequest(MetaEntity $metaEntity) {
		return new SqliteDropMetaEntityRequest($metaEntity);
	}
	
	protected function createRenameMetaEntityRequest(MetaEntity $metaEntity, string $oldName, string $newName) {
		return new SqliteRenameMetaEntityRequest($metaEntity, $oldName, $newName);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\structure\common\MetaManagerAdapter::buildDatabase()
	 * @return DatabaseAdapter
	 */
	protected function buildDatabase(): DatabaseAdapter {
		$dbName = $this->determineDbName();
		$database = new SqliteDatabase($dbName, $this->determineDbCharset(), 
				$this->getPersistedMetaEntities($dbName), $this->determineDbAttrs($dbName));
		
		foreach ($database->getMetaEntities() as $metaEntity) {
			if (!$metaEntity instanceof Table) continue;
			
			$this->metaEntityBuilder->applyIndexesForTable($dbName, $metaEntity);
		}
		
		return $database;
	}
	
	
	private function determineCharset() {
		$sql = 'pragma ' . $this->dbh->quoteField(SqliteDatabase::FIXED_DATABASE_NAME) . '.encoding';
		$statement = $this->dbh->prepare($sql);
		$statement->execute();
		$result = $statement->fetch(Pdo::FETCH_ASSOC);
		return $result['encoding'];
	}
	
	private function getAttrs() {
		$sql = 'SHOW VARIABLES';
		$statement = $this->dbh->prepare($sql);
		$statement->execute(array(':TABLE_SCHEMA' => SqliteDatabase::FIXED_DATABASE_NAME));
		return $statement->fetchAll(Pdo::FETCH_ASSOC);
	}
	
	protected function getPersistedMetaEntities() {
		$metaEntities = array();
		$sql = 'SELECT * FROM ' . $this->dbh->quoteField(SqliteDatabase::FIXED_DATABASE_NAME) 
				. '.sqlite_master WHERE type in (:type_table, :type_view) AND  '
				. $this->dbh->quoteField('name') . 'NOT LIKE :reserved_names';
		$statement = $this->dbh->prepare($sql);
		$statement->execute(
				[':type_table' => SqliteMetaEntityBuilder::TYPE_TABLE,
						':type_view' => SqliteMetaEntityBuilder::TYPE_VIEW,
						':reserved_names' => SqliteDatabase::RESERVED_NAME_PREFIX . '%']);
		while (null != ($result =  $statement->fetch(Pdo::FETCH_ASSOC))) {
			$metaEntities[$result['name']] = $this->metaEntityBuilder->createMetaEntity($result['name']);
		}
		return $metaEntities;
	}
	
	
}