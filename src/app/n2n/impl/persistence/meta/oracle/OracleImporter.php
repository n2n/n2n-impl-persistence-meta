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
namespace n2n\impl\persistence\meta\oracle;

use n2n\util\ex\IllegalStateException;
use n2n\util\io\stream\InputStream;
use n2n\persistence\Pdo;

class OracleImporter /* implements Importer */ {
	const STATEMENT_DELIMITER = ';';
	const PLSQL_DECLARE = 'DECLARE';
	const PLSQL_BEGIN = 'BEGIN';
	const PLSQL_END_OF_BLOCK = '/';
	/**
	 * @var \n2n\persistence\Pdo
	 */
	private $dbh;
	
	/**
	 * @var \n2n\util\io\stream\InputStream
	 */
	private $inputStream;
	
	/**
	 * @param \n2n\persistence\Pdo $dbh
	 * @param \n2n\util\io\stream\InputStream $inputStream
	 */
	public function __construct(Pdo $dbh, InputStream $inputStream) {
		$this->dbh = $dbh;
		$this->inputStream = $inputStream;
	}
	
	public function execute() {

		if (!($this->inputStream->isOpen())) {
			throw new IllegalStateException('Inputstream not open');
		}
		
		$statement = '';
		$inStringContext = '';
		$inPlsqlContext = false;
		foreach (str_split($this->inputStream->read()) as $character)  {
			if (!($inPlsqlContext)) {
				if (strlen($inStringContext) == 0 
						&& ((strpos(strtoupper($statement), self::PLSQL_BEGIN) !== false)
								|| strpos(strtoupper($statement), self::PLSQL_DECLARE) !== false)) {
					$inPlsqlContext = true;
					
				} else {
					if ($character == '\'' || $character == '"')  {
						if (strlen($inStringContext) == 0) {
							$inStringContext = $character;
						} else {
							if ($inStringContext == $character) {
								$inStringContext = '';
							}
						}
					}
				}
			} 
			
			if (($inPlsqlContext && $character == self::PLSQL_END_OF_BLOCK)
				 || (!$inPlsqlContext && $character == self::STATEMENT_DELIMITER && strlen($inStringContext) == 0)) {
				$inPlsqlContext = false;
				$this->dbh->exec($statement);
				$statement = '';
			} else {
				$statement .= $character;
			}
		}
	}
}
