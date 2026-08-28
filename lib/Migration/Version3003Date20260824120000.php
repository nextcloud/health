<?php

declare(strict_types=1);

namespace OCA\Health\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Adds the additive Health v3 goal, revision, and notification-deduplication schema. */
class Version3003Date20260824120000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('health_goals')) {
			$table = $schema->createTable('health_goals');
			$this->addPrimaryKey($table);
			$table->addColumn('user_id', Types::STRING, ['length' => 64, 'notnull' => true]);
			$table->addColumn('target_key', Types::STRING, ['length' => 64, 'notnull' => true]);
			$table->addColumn('period', Types::STRING, ['length' => 16, 'notnull' => true]);
			$table->addColumn('active', Types::BOOLEAN, ['notnull' => true, 'default' => true]);
			$table->addColumn('reminders_enabled', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
			$table->addColumn('reminder_policy', Types::STRING, ['length' => 32, 'notnull' => true, 'default' => 'gentle']);
			$table->addColumn('retired_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
			$table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
			$table->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
			$table->addUniqueIndex(['user_id', 'target_key', 'period'], 'health_goal_user_target_period_uniq');
			$table->addIndex(['active', 'reminders_enabled'], 'health_goal_active_reminders_idx');
		}

		if (!$schema->hasTable('health_goal_revisions')) {
			$table = $schema->createTable('health_goal_revisions');
			$this->addPrimaryKey($table);
			$table->addColumn('goal_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
			$table->addColumn('comparator', Types::STRING, ['length' => 8, 'notnull' => true]);
			$table->addColumn('target_value', Types::DECIMAL, ['precision' => 20, 'scale' => 6, 'notnull' => true]);
			$table->addColumn('secondary_target_value', Types::DECIMAL, ['precision' => 20, 'scale' => 6, 'notnull' => false]);
			$table->addColumn('effective_from', Types::STRING, ['length' => 10, 'notnull' => true]);
			$table->addColumn('effective_to', Types::STRING, ['length' => 10, 'notnull' => false]);
			$table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
			$table->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
			$table->addIndex(['goal_id', 'effective_from'], 'health_goalrev_goal_from_idx');
			$table->addUniqueIndex(['goal_id', 'effective_from'], 'health_goalrev_goal_from_uniq');
		}

		if (!$schema->hasTable('health_goal_reminder_state')) {
			$table = $schema->createTable('health_goal_reminder_state');
			$this->addPrimaryKey($table);
			$table->addColumn('goal_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
			$table->addColumn('period_key', Types::STRING, ['length' => 16, 'notnull' => true]);
			$table->addColumn('last_notification_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
			$table->addColumn('notification_count', Types::INTEGER, ['notnull' => true, 'default' => 0]);
			$table->addColumn('last_notification_reason', Types::STRING, ['length' => 32, 'notnull' => false]);
			$table->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
			$table->addUniqueIndex(['goal_id', 'period_key'], 'health_goalrem_goal_period_uniq');
		}

		return $schema;
	}

	private function addPrimaryKey(object $table): void {
		$table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
		$table->setPrimaryKey(['id']);
	}
}
