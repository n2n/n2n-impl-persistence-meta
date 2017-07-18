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
namespace n2n\impl\persistence\meta\oracle\management;

use n2n\persistence\meta\structure\common\CommonTextColumn;

use n2n\impl\persistence\meta\oracle\OracleMetaEntityBuilder;

use n2n\impl\persistence\meta\oracle\OracleIndexStatementStringBuilder;

use n2n\impl\persistence\meta\oracle\OracleColumnStatementStringBuilder;

use n2n\persistence\meta\structure\common\ChangeRequestAdapter;

use n2n\persistence\meta\structure\Table;

use n2n\persistence\meta\structure\common\AlterMetaEntityRequest;

use n2n\persistence\meta\structure\View;

use n2n\persistence\Pdo;

class OracleAlterMetaEntityRequest extends ChangeRequestAdapter implements AlterMetaEntityRequest  {
	
	public function execute(Pdo $dbh) {
		if ($this->getMetaEntity() instanceof View) {
			$dbh->exec('ALTER VIEW ' . $dbh->quoteField($this->getMetaEntity()->getName()) . ' AS ' . $this->getMetaEntity()->getQuery());
			return;
		}	
		if ($this->getMetaEntity() instanceof Table) {
			$columnStatementStringBuilder = new OracleColumnStatementStringBuilder($dbh);
			$indexStatementStringBuilder = new OracleIndexStatementStringBuilder($dbh);
			$metaEntityBuilder = new OracleMetaEntityBuilder($dbh, $this->getMetaEntity()->getDatabase());
			//columns to Add
			$columns = $this->getMetaEntity()->getColumns();
			$persistedTable =  $metaEntityBuilder->createTable($this->getMetaEntity()->getName());
			$persistedColumns = $persistedTable->getColumns();

			foreach ($columns as $column) {
				if (!(isset($persistedColumns[$column->getName()]))) {
					$dbh->exec('ALTER TABLE ' . $dbh->quoteField($this->getMetaEntity()->getName()) . ' ADD ' 
							. $columnStatementStringBuilder->generateStatementString($column));
				} elseif (isset($persistedColumns[$column->getName()]) && (!($column->equals($persistedColumns[$column->getName()])))) {
					$dbh->exec('ALTER TABLE ' . $dbh->quoteField($this->getMetaEntity()->getName()) . ' MODIFY (' 
							. $columnStatementStringBuilder->generateStatementString($column) . ')'); 
				}
			}
			
			foreach ($persistedColumns as $persistedColumn) {
				if (!(isset($columns[$persistedColumn->getName()]))) {
					$dbh->exec('ALTER TABLE ' . $dbh->quoteField($this->getMetaEntity()->getName()) . ' DROP COLUMN ' . $dbh->quoteField($persistedColumn->getName()));
				}
			}
			
			$indexes = $this->getMetaEntity()->getIndexes();
			$persistedIndexes = $persistedTable->getIndexes();
			
			foreach ($indexes as $index) {
				if (!isset($persistedIndexes[$index->getName()])) {
					$dbh->exec($indexStatementStringBuilder->generateCreateStatementString($this->getMetaEntity(), $index));
				}
			}
			
			foreach ($persistedIndexes as $persistedIndex) {
				if (!isset($indexes[$persistedIndex->getName()]) ) {
					$dbh->exec($indexStatementStringBuilder->generateDropStatementString($this->getMetaEntity(), $index));
				}
			}
		}
	}
}
