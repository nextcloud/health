<?php

declare(strict_types=1);

namespace OCA\Health\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** @psalm-suppress UnusedClass Migrations are discovered by Nextcloud. */
class Version3000Date20260817210000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('health_entries')) {
			return null;
		}

		$table = $schema->getTable('health_entries');
		if ($table->hasColumn('source')) {
			return null;
		}

		$table->addColumn('source', Types::STRING, [
			'length' => 32,
			'notnull' => true,
			'default' => 'web',
		]);

		return $schema;
	}
}
