<?php
namespace n2n\persistence\meta\impl\pgsql\management;

use n2n\persistence\Pdo;
use n2n\persistence\meta\impl\pgsql\PgsqlIndexStatementBuilder;
use n2n\persistence\meta\structure\Table;
use n2n\persistence\meta\structure\common\ChangeRequestAdapter;
use n2n\persistence\meta\impl\pgsql\PgsqlMetaEntityBuilder;
use n2n\persistence\meta\impl\pgsql\PgsqlColumnStatementFragmentBuilder;

class PgsqlAlterMetaEntityRequest extends ChangeRequestAdapter {

	public function execute(Pdo $dbh) {
		$metaEntity = $this->getMetaEntity();
		$quotedEntityName = $dbh->quoteField($metaEntity->getName());
		
		if ($metaEntity instanceof View) {
			$dbh->exec('DROP VIEW ' . $quotedEntityName);
			$dbh->exec('CREATE VIEW ' . $quotedEntityName . ' AS ' . $dbh->quote($metaEntity->getQuery()));
			return;
		}

		$database = $metaEntity->getDataBase();
		$columnStatementStringBuilder = new PgsqlColumnStatementFragmentBuilder($dbh);
		$metaEntityBuilder = new PgsqlMetaEntityBuilder($dbh, $database);
		
		if ($metaEntity instanceof Table) {
			//columns to Add
			$metaEntityBuilder = new PgsqlMetaEntityBuilder($dbh, $database);
			
			//columns to Add
			$columns = $this->getMetaEntity()->getColumns();
			$persistedTable =  $metaEntityBuilder->createMetaEntity($this->getMetaEntity()->getName());
			$persistedColumns = $persistedTable->getColumns();
			
			foreach ($columns as $column) {
				if (!(isset($persistedColumns[$column->getName()]))) {
					$sql .= $columnStatementStringBuilder->buildAddColumnStatement($column);
				} elseif (isset($persistedColumns[$column->getName()]) && (!($column->equals($persistedColumns[$column->getName()])))) {
					$sql .= $columnStatementStringBuilder->buildAlterColumnStatement($column, 
							$persistedColumns[$column->getName()]);
				}
			}
				
			foreach ($persistedColumns as $persistedColumn) {
				if (!(isset($columns[$persistedColumn->getName()]))) {
					$sql .= $columnStatementStringBuilder->buildDropColumnStatement($persistedColumn);
				}
			}

			//weiter mit same same, wie mit columns bei indexes
			
			$dbh->exec($sql . $this->buildIndexSql(new PgsqlIndexStatementBuilder($dbh), 
					$metaEntity, $persistedTable));
		}
	}
	
	private function buildIndexSql(PgsqlIndexStatementBuilder $indexStatementBuilder, 
			Table $newTable, Table $currentTable) {
		$sql = '';
		
		$currentTableIndexes = $currentTable->getIndexes();
		$newTableIndexes = $this->getMetaEntity()->getIndexes();
			
		$creatableIndexes = array_diff($newTableIndexes, $currentTableIndexes);
		$changeableIndexes = array_intersect($currentTableIndexes, $newTableIndexes);
		$dropableIndexes = array_diff($currentTableIndexes, $newTableIndexes);
			
		if (sizeof($dropableIndexes)) {
			foreach ($dropableIndexes as $dropableIndex) {
				$sql .= $indexStatementBuilder->buildDropStatement($dropableIndex);
			}
		}
			
		if (sizeof($creatableIndexes)) {
			foreach ($creatableIndexes as $creatableIndex) {
				$sql .= $indexStatementBuilder->buildCreateStatement($creatableIndex);
			}
		}
			
		if (sizeof($changeableIndexes)) {
			foreach ($changeableIndexes as $changeableIndex) {
				$sql .= $indexStatementBuilder->buildDropStatement($changeableIndex)
						. $indexStatementBuilder->buildCreateStatement($changeableIndex);
			}
		}
		
		return $sql;
	}
}