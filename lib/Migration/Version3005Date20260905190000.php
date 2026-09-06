<?php

declare(strict_types=1);

namespace OCA\Health\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Adds optional owner-scoped idempotency keys for offline capture retries.
 *
 * @psalm-suppress UnusedClass Migration is discovered by Nextcloud's lifecycle.
 */
class Version3005Date20260905190000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$entries = $schema->getTable('health_entries');
		if (!$entries->hasColumn('client_operation_id')) {
			$entries->addColumn('client_operation_id', Types::STRING, ['length' => 36, 'notnull' => false]);
			$entries->addUniqueIndex(['user_id', 'client_operation_id'], 'health_ent_user_operation_uniq');
		}

		$measurements = $schema->getTable('health_measurements');
		if (!$measurements->hasColumn('client_operation_id')) {
			$measurements->addColumn('client_operation_id', Types::STRING, ['length' => 36, 'notnull' => false]);
			$measurements->addUniqueIndex(['user_id', 'client_operation_id', 'metric_key'], 'health_meas_user_operation_uniq');
		}

		return $schema;
	}
}
