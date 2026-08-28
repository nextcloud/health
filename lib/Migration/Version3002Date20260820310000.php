<?php

declare(strict_types=1);

namespace OCA\Health\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** @psalm-suppress UnusedClass Migrations are discovered by Nextcloud. */
class Version3002Date20260820310000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();
		if (!$schema->hasTable('health_user_settings')) {
			return $schema;
		}

		$table = $schema->getTable('health_user_settings');
		if (!$table->hasColumn('search_daily_notes')) {
			$table->addColumn('search_daily_notes', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
		}

		return $schema;
	}
}
