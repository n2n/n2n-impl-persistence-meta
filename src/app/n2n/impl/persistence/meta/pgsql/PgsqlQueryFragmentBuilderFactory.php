<?php
namespace n2n\impl\persistence\meta\pgsql;

use n2n\persistence\Pdo;
use n2n\persistence\meta\data\common\QueryFragmentBuilderFactory;

class PgsqlQueryFragmentBuilderFactory implements QueryFragmentBuilderFactory {
	private Pdo $dbh;

	public function __construct(Pdo $dbh) {
		$this->dbh = $dbh;
	}

	public function create(): PgsqlQueryFragmentBuilder {
		return new PgsqlQueryFragmentBuilder($this->dbh);
	}
}