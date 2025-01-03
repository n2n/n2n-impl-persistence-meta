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
use n2n\spec\dbo\meta\structure\Backuper;
use n2n\spec\dbo\meta\structure\Table;
use n2n\persistence\meta\structure\common\MetaManagerAdapter;
use n2n\persistence\meta\structure\common\DatabaseAdapter;
use n2n\spec\dbo\meta\structure\MetaEntity;
use n2n\impl\persistence\meta\mysql\management\MysqlAlterMetaEntityRequest;
use n2n\impl\persistence\meta\mysql\management\MysqlCreateMetaEntityRequest;
use n2n\impl\persistence\meta\mysql\management\MysqlDropMetaEntityRequest;
use n2n\impl\persistence\meta\mysql\management\MysqlRenameMetaEntityRequest;

class MysqlMetaManager extends MetaManagerAdapter {
	private $metaEntityBuilder;
	
	public function __construct(Pdo $dbh) {
		parent::__construct($dbh);
		$this->metaEntityBuilder = new MysqlMetaEntityBuilder($dbh);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\spec\dbo\meta\structure\Database::createBackuper()
	 * @return Backuper
	 */
	public function createBackuper(?array $metaEnities = null): Backuper {
		return new MysqlBackuper($this->dbh, $this->createDatabase(), $metaEnities);
	}
	
	protected function createAlterMetaEntityRequest(MetaEntity $metaEntity) {
		return new MysqlAlterMetaEntityRequest($metaEntity);
	}
	
	protected function createCreateMetaEntityRequest(MetaEntity $metaEntity) {
		return new MysqlCreateMetaEntityRequest($metaEntity);
	}
	
	protected function createDropMetaEntityRequest(MetaEntity $metaEntity) {
		return new MysqlDropMetaEntityRequest($metaEntity);
	}
	
	protected function createRenameMetaEntityRequest(MetaEntity $metaEntity, string $oldName, string $newName) {
		return new MysqlRenameMetaEntityRequest($metaEntity, $oldName, $newName);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\meta\structure\common\MetaManagerAdapter::buildDatabase()
	 * @return DatabaseAdapter
	 */
	protected function buildDatabase(): DatabaseAdapter {
		$dbName = $this->determineDbName();
		$database = new MysqlDatabase($dbName, $this->determineDbCharset(), 
				$this->getPersistedMetaEntities($dbName), $this->determineDbAttrs($dbName));
		
		foreach ($database->getMetaEntities() as $metaEntity) {
			if (!$metaEntity instanceof Table) continue;
			
			$this->metaEntityBuilder->applyIndexesForTable($dbName, $metaEntity);
		}
		
		return $database;
	}
	
	private function determineDbName() {
		$sql = 'SELECT DATABASE() as name;';
		$statement = $this->dbh->prepare($sql);
		$statement->execute();
		$result = $statement->fetch(\PDO::FETCH_ASSOC);
		return $result['name'];
	} 
	
	private function determineDbCharset() {
		$sql = 'SHOW VARIABLES LIKE "character_set_database"';
		$statement = $this->dbh->prepare($sql);
		$statement->execute();
		$result = $statement->fetch(\PDO::FETCH_ASSOC);
		return $result['Value'];
	}
	
	private function determineDbAttrs(string $dbName) {
		$sql = 'SHOW VARIABLES';
		$statement = $this->dbh->prepare($sql);
		$statement->execute();
		$results = $statement->fetchAll(\PDO::FETCH_ASSOC);
		return $results;
	}
	
	private function getPersistedMetaEntities(string $dbName) {
		$metaEntities = array();
		$sql = 'SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = :TABLE_SCHEMA;';
		$statement = $this->dbh->prepare($sql);
		$statement->execute(array(':TABLE_SCHEMA' => $dbName));
		
		while (null != ($result =  $statement->fetch(\PDO::FETCH_ASSOC))) {
			$metaEntities[$result['TABLE_NAME']] = $this->metaEntityBuilder->createMetaEntity($dbName, $result['TABLE_NAME']);
		}
		
		return $metaEntities;
	}
}