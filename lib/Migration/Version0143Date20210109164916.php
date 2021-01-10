<?php

declare(strict_types=1);

namespace OCA\Health\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0143Date20210109164916 extends SimpleMigrationStep {


	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		$schema = $schemaClosure();
		$table = $schema->getTable('health_weightdata');
		if(!$table->hasColumn('waist')) {
			$table->addColumn('waist', 'float', [
				'default' => null,
				'notnull' => false,
			]);
		}
		if(!$table->hasColumn('hip')) {
			$table->addColumn('hip', 'float', [
				'default' => null,
				'notnull' => false,
			]);
		}
		return $schema;
	}

}
