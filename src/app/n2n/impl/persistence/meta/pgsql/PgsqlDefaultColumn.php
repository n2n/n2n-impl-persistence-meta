<?php
namespace n2n\persistence\meta\impl\pgsql;

use n2n\persistence\meta\structure\common\ColumnAdapter;

class PgsqlDefaultColumn extends ColumnAdapter {
	public function copy($newColumnName = null) {
		if (is_null($newColumnName)) {
			$newColumnName = $this->getName();
		}
		$newColumn = new self($newColumnName);
		$newColumn->applyCommonAttributes($this);
		return $newColumn;
	}
}