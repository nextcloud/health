<?php

declare(strict_types=1);

namespace OCA\Health\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** @psalm-suppress UnusedClass Migrations are discovered by Nextcloud. */
class Version3000Date20260820130000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('health_daily_values')) {
			$table = $schema->createTable('health_daily_values');
			$this->addPrimaryKey($table);
			$table->addColumn('user_id', Types::STRING, ['length' => 64, 'notnull' => true]);
			$table->addColumn('metric_key', Types::STRING, ['length' => 64, 'notnull' => true]);
			$table->addColumn('numeric_value', Types::DECIMAL, ['precision' => 20, 'scale' => 6, 'notnull' => true]);
			$table->addColumn('local_date', Types::STRING, ['length' => 10, 'notnull' => true]);
			$table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
			$table->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
			$table->addUniqueIndex(['user_id', 'local_date', 'metric_key'], 'health_dval_user_date_metric_uniq');
		}

		if (!$schema->hasTable('health_measurements')) {
			$table = $schema->createTable('health_measurements');
			$this->addPrimaryKey($table);
			$table->addColumn('user_id', Types::STRING, ['length' => 64, 'notnull' => true]);
			$table->addColumn('metric_key', Types::STRING, ['length' => 64, 'notnull' => true]);
			$table->addColumn('numeric_value', Types::DECIMAL, ['precision' => 20, 'scale' => 6, 'notnull' => true]);
			$table->addColumn('group_id', Types::STRING, ['length' => 64, 'notnull' => false]);
			$table->addColumn('context', Types::STRING, ['length' => 32, 'notnull' => true]);
			$table->addColumn('source', Types::STRING, ['length' => 32, 'notnull' => true]);
			$table->addColumn('recorded_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
			$table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
			$table->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
			$table->addColumn('note', Types::TEXT, ['notnull' => false]);
			$table->addIndex(['user_id', 'metric_key', 'recorded_at', 'id'], 'health_meas_user_met_rec_idx');
			$table->addIndex(['user_id', 'group_id'], 'health_meas_user_group_idx');
		}

		if (!$schema->hasTable('health_user_metrics')) {
			$table = $schema->createTable('health_user_metrics');
			$this->addPrimaryKey($table);
			$table->addColumn('user_id', Types::STRING, ['length' => 64, 'notnull' => true]);
			$table->addColumn('metric_key', Types::STRING, ['length' => 64, 'notnull' => true]);
			$table->addColumn('enabled', Types::BOOLEAN, ['notnull' => true]);
			$table->addColumn('check_in_enabled', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
			$table->addColumn('check_out_enabled', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
			$table->addColumn('display_unit', Types::STRING, ['length' => 32, 'notnull' => false]);
			$table->addUniqueIndex(['user_id', 'metric_key'], 'health_umetric_user_metric_uniq');
		}

		if (!$schema->hasTable('health_user_settings')) {
			$table = $schema->createTable('health_user_settings');
			$this->addPrimaryKey($table);
			$table->addColumn('user_id', Types::STRING, ['length' => 64, 'notnull' => true]);
			$table->addColumn('height_cm', Types::DECIMAL, ['precision' => 20, 'scale' => 6, 'notnull' => false]);
			$table->addColumn('height_display_unit', Types::STRING, ['length' => 32, 'notnull' => true, 'default' => 'cm']);
			$table->addUniqueIndex(['user_id'], 'health_usetting_user_uniq');
		}

		return $schema;
	}

	private function addPrimaryKey(object $table): void {
		$table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
		$table->setPrimaryKey(['id']);
	}
}
