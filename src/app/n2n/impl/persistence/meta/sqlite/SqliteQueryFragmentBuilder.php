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
namespace n2n\impl\persistence\meta\sqlite;

use n2n\spec\dbo\meta\data\QueryFragmentBuilder;
use n2n\persistence\Pdo;
use n2n\persistence\meta\data\QueryComparator;
use n2n\persistence\meta\Dialect;
use n2n\spec\dbo\meta\data\impl\QueryFunction;

class SqliteQueryFragmentBuilder implements QueryFragmentBuilder {
	
	const ALIAS_COLUMN_SEPARATOR = '.';
	const PLACE_MARKER = '?';
	const NAMED_PLACE_MARKER_PREFIX = ':';
	const KEYWORD_OPERATOR_LIKE_ESCAPE = 'ESCAPE';
	/**
	 * @var Pdo
	 */
	private $dbh;
	private $sql = '';
	private $inLikeContext = false;
	
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
		if (!isset($value)) {
			$this->sql .= ' NULL';
			return;
		}
		$this->sql .= ' ' . $this->dbh->quote($value);
		if ($this->inLikeContext) {
			$this->inLikeContext = false;
			$this->sql .= ' ' . self::KEYWORD_OPERATOR_LIKE_ESCAPE . " '" . Dialect::DEFAULT_ESCAPING_CHARACTER . "'";
		}
	}
	
	public function addPlaceMarker($name = null): void {
		if (is_null($name)) {
			$this->sql .= ' ' . self::PLACE_MARKER;
		} else {
			$this->sql .= ' ' . self::NAMED_PLACE_MARKER_PREFIX . $name;
		}
	}
	
	public function addOperator($operator): void {
		if ($operator == QueryComparator::OPERATOR_LIKE) {
			$this->inLikeContext;
		}
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
		$name = match($name) {
			QueryFunction::RAND => 'RANDOM',
			default => $name
		};

		$this->sql .= ' ' . $name . '(';
	}
	
	public function closeFunction(): void {
		$this->sql .= ' )';
	}
	
	public function toSql(): string {
		return $this->sql;
	}
}
