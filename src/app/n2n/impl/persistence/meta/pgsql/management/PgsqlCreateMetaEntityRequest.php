<?php
namespace n2n\persistence\meta\impl\pgsql\management;

use n2n\persistence\Pdo;
use n2n\persistence\meta\structure\common\ChangeRequestAdapter;
use n2n\persistence\meta\impl\pgsql\PgsqlCreateStatementBuilder;

class PgsqlCreateMetaEntityRequest extends ChangeRequestAdapter {
	public function execute(Pdo $dbh) {
		$createStatementBuilder = new PgsqlCreateStatementBuilder($dbh);
		$createStatementBuilder->setMetaEntity($this->getMetaEntity());
		$createStatementBuilder->executeSqlStatements();
	}
}