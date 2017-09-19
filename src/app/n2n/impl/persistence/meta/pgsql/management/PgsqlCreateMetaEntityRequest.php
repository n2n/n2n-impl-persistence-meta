<?php
namespace n2n\impl\persistence\meta\pgsql\management;

use n2n\persistence\Pdo;
use n2n\persistence\meta\structure\common\ChangeRequestAdapter;
use n2n\impl\persistence\meta\pgsql\PgsqlCreateStatementBuilder;

class PgsqlCreateMetaEntityRequest extends ChangeRequestAdapter {
	public function execute(Pdo $dbh) {
		$createStatementBuilder = new PgsqlCreateStatementBuilder($dbh);
		$createStatementBuilder->setMetaEntity($this->getMetaEntity());
		$createStatementBuilder->executeSqlStatements();
	}
}