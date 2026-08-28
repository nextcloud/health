<?php

declare(strict_types=1);

namespace OCA\Health\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** @psalm-suppress UnusedClass Migrations are discovered by Nextcloud. */
class Version3001Date20260820230000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();
		if (!$schema->hasTable('health_user_settings')) {
			return $schema;
		}

		$table = $schema->getTable('health_user_settings');
		if (!$table->hasColumn('date_of_birth')) {
			$table->addColumn('date_of_birth', Types::STRING, ['length' => 10, 'notnull' => false]);
		}
		if (!$table->hasColumn('growth_reference_sex')) {
			$table->addColumn('growth_reference_sex', Types::STRING, ['length' => 16, 'notnull' => false]);
		}

		return $schema;
	}
}
