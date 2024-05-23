<?php

namespace n2n\impl\persistence\meta\sqlite;

use PHPUnit\Framework\TestCase;
use n2n\test\TestEnv;
use n2n\spec\dbo\meta\data\impl\QueryColumn;
use n2n\spec\dbo\meta\data\impl\QueryConstant;
use n2n\spec\dbo\meta\data\impl\QueryPlaceMarker;
use n2n\spec\dbo\meta\data\impl\QueryItems;

class SqliteInsertStatementBuilderTest extends TestCase {
	function testEmptyInsertStatement() {
		$pdo = TestEnv::db()->pdo('sqlite');


		$builder = new SqliteInsertStatementBuilder($pdo);
		$builder->setTable('holeradio');

		$this->assertEquals('INSERT INTO [holeradio] ' . PHP_EOL . 'DEFAULT VALUES', $builder->toSqlString());
	}

	function testFilledInsertStatement() {
		$pdo = TestEnv::db()->pdo('sqlite');


		$builder = new SqliteInsertStatementBuilder($pdo);
		$builder->setTable('holeradio');
		$builder->addColumn(new QueryColumn('blubb'), new QueryConstant(true));
		$builder->addColumn(new QueryColumn('blubb2'), new QueryPlaceMarker());

		$this->assertEquals('INSERT INTO [holeradio] ([blubb], [blubb2])' . PHP_EOL . 'VALUES ( \'1\',  ?)', $builder->toSqlString());
	}

	function testUpsertStatement() {
		$pdo = TestEnv::db()->pdo('sqlite');


		$builder = new SqliteInsertStatementBuilder($pdo);
		$builder->setTable('holeradio');
		$builder->addColumn(new QueryColumn('col'), new QueryPlaceMarker());
		$builder->setUpsertUniqueColumns([QueryItems::column('not_used')]);

		$this->assertEquals('REPLACE INTO [holeradio] ([col])' . PHP_EOL . 'VALUES ( ?)', $builder->toSqlString());
	}
}
