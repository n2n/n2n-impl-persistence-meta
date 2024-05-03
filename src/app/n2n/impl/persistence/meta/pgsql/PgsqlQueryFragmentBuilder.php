<?php
namespace n2n\impl\persistence\meta\pgsql;

use n2n\spec\dbo\meta\data\QueryFragmentBuilder;
use n2n\persistence\Pdo;

class PgsqlQueryFragmentBuilder implements QueryFragmentBuilder {
	const ALIAS_COLUMN_SEPARATOR = '.';
	const PLACE_MARKER = '?';
	const NAMED_PLACE_MARKER_PREFIX = ':';

	private Pdo $dbh;
	private string $sql = '';

	public function __construct(Pdo $dbh) {
		$this->dbh = $dbh;
	}

	public function addTable($tableName): void {
		$this->sql .= ' ' . $this->dbh->quoteField($tableName);
	}

	public function addField($fieldName, $fieldAlias = null): void {
		$this->sql .= ' '
				. (isset($fieldAlias) ? $this->dbh->quoteField($fieldAlias) . self::ALIAS_COLUMN_SEPARATOR : '')
				. $this->dbh->quoteField($fieldName);
	}

	public function addFieldAlias($fieldAlias): void {
		$this->sql .= ' AS ' . $this->dbh->quoteField($fieldAlias);
	}

	public function addConstant($value): void {
		if (null === $value) {
			$this->sql .= ' NULL';
			return;
		}
		$this->sql .= ' ' . $this->dbh->quote($value);
	}

	public function addPlaceMarker($name = null): void {
		if (is_null($name)) {
			$this->sql .= ' ' . self::PLACE_MARKER;
		} else {
			$this->sql .= ' ' . self::NAMED_PLACE_MARKER_PREFIX . $name;
		}
	}

	public function addOperator($operator): void {
		$this->sql .= ' ' . $operator;
	}

	public function openGroup(): void {
		$this->sql .= ' (';
	}

	public function closeGroup(): void {
		$this->sql .= ' )';
	}

	public function addSeparator(): void {
		$this->sql .= ', ';
	}

	public function addRawString($sqlString): void {
		$this->sql .= ' ' . $sqlString;
	}

	public function openFunction($name): void {
		$this->sql .= ' ' . $name . '(';
	}

	public function closeFunction(): void {
		$this->sql .= ' )';
	}

	public function toSql(): string {
		return $this->sql;
	}
}