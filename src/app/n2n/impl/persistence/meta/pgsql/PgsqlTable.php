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
namespace n2n\impl\persistence\meta\pgsql;

use n2n\persistence\meta\structure\Column;
use n2n\persistence\meta\structure\ColumnFactory;
use n2n\impl\persistence\meta\pgsql\PgsqlColumnFactory;
use n2n\persistence\meta\structure\IndexType;
use n2n\impl\persistence\meta\pgsql\PgsqlColumn;
use n2n\persistence\meta\structure\UnknownColumnException;
use n2n\persistence\meta\structure\common\ColumnChangeListener;
use n2n\persistence\meta\structure\Table;
use n2n\core\SysTextUtils;
use n2n\persistence\meta\structure\DuplicateMetaElementException;

class PgsqlTable extends TableAdapter {
	private $columnFactory;

	public function createColumnFactory() {
		if (!($this->columnFactory)) {
			$this->columnFactory = new PgsqlColumnFactory($this);
		}

		return $this->columnFactory;
	}

	public function generatePrimaryKeyName() {
		return StringUtils::hyphenated($this->getName()) . '_primary';
	}
}
