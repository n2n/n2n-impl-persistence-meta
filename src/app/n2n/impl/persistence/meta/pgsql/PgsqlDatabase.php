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

use n2n\persistence\meta\structure\common\DatabaseAdapter;
use n2n\persistence\meta\structure\MetaEntity;
use n2n\persistence\Pdo;
use n2n\impl\persistence\meta\pgsql\management\PgsqlAlterMetaEntityRequest;
use n2n\impl\persistence\meta\pgsql\management\PgsqlCreateMetaEntityRequest;
use n2n\impl\persistence\meta\pgsql\management\PgsqlDropMetaEntityRequest;

class PgsqlDatabase extends DatabaseAdapter {
	const TABLE_SCHEMA = 'public';

	private $changeRequestQueue;
	private $metaEntityFactory;

	private $name;
	private $charset;
	private $attrs;

	public function __construct(Pdo $dbh) {
		parent::__construct($dbh);
		$this->pgsqlMetaEntityBuilder = new PgsqlMetaEntityBuilder($dbh, $this);
	}

	public function getName() {
		if (is_null($this->name)) {
			$stmt = $this->dbh->prepare('SELECT CURRENT_DATABASE() AS name');
			$stmt->execute();
			$result = $stmt->fetch(Pdo::FETCH_ASSOC);
			$this->name = $result['name'];
		}
		return $this->name;
	}

	public function getCharset() {
		if (is_null($this->charset)) {
			$stmt = $this->dbh->prepare('SELECT PG_ENCODING_TO_CHAR(ENCODING) AS charset FROM pg_database WHERE datname = ?;');
			$stmt->execute(array($this->getName()));
			$result = $stmt->fetch(Pdo::FETCH_ASSOC);
			$this->charset = $result['charset'];
		}
		return $this->charset;
	}

	public function getAttrs() {
		if (is_null($this->attrs) || !is_array($this->attrs)) {
			$stmt = $this->dbh->prepare('SHOW ALL');
			$stmt->execute();
			$result = $stmt->fetchAll(Pdo::FETCH_ASSOC);

			foreach ($result as $res) {
				$this->attrs[$res['name']] = $res['setting'];
			}
		}
		return $this->attrs;
	}

	public function createBackuper(array $metaEnties = null) {
		return new PgsqlBackuper($this->dbh, $this, $metaEnties);
	}

	public function createMetaEntityFactory() {
		if (!isset($this->metaEntityFactory)) {
			$this->metaEntityFactory = new PgsqlMetaEntityFactory($this);
		}
		return $this->metaEntityFactory;
	}

	protected function getPersistedMetaEntities() {
		$sql = 'SELECT table_name FROM information_schema.tables WHERE table_catalog = ? AND table_schema = ?';
		$stmt = $this->dbh->prepare($sql);
		$stmt->execute(array($this->getName(), self::TABLE_SCHEMA));
		$result = $stmt->fetchAll(Pdo::FETCH_ASSOC);

		$metaEntities = array();
		foreach ($result as $row) {
			$metaEntities[$row['table_name']] = $this->pgsqlMetaEntityBuilder->createMetaEntity($row['table_name']);
		}
		return $metaEntities;
	}

	public function createAlterMetaEntityRequest(MetaEntity $metaEntity) {
		return new PgsqlAlterMetaEntityRequest($metaEntity);
	}

	public function createCreateMetaEntityRequest(MetaEntity $metaEntity) {
		return new PgsqlCreateMetaEntityRequest($metaEntity);
	}

	public function createDropMetaEntityRequest(MetaEntity $metaEntity) {
		return new PgsqlDropMetaEntityRequest($metaEntity);
	}
}