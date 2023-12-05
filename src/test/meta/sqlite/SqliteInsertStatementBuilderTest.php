<?php

namespace n2n\impl\persistence\meta\sqlite;

use PHPUnit\Framework\TestCase;
use n2n\test\TestEnv;
use n2n\persistence\meta\data\QueryColumn;
use n2n\persistence\meta\data\QueryConstant;
use n2n\persistence\meta\data\QueryPlaceMarker;

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
}
