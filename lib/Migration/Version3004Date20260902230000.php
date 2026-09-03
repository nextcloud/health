<?php

declare(strict_types=1);

namespace OCA\Health\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Adds private, reusable Statistics view configurations without storing derived statistics.
 *
 * @psalm-suppress UnusedClass Migrations are discovered by Nextcloud.
 */
class Version3004Date20260902230000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();
		if ($schema->hasTable('health_statistics_views')) {
			return $schema;
		}

		$table = $schema->createTable('health_statistics_views');
		$table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
		/** @psalm-suppress DeprecatedMethod Nextcloud's portable schema migration API currently delegates to Doctrine's supported primary-key method. */
		$table->setPrimaryKey(['id']);
		$table->addColumn('user_id', Types::STRING, ['length' => 64, 'notnull' => true]);
		$table->addColumn('title', Types::STRING, ['length' => 120, 'notnull' => true]);
		$table->addColumn('icon', Types::STRING, ['length' => 64, 'notnull' => true]);
		$table->addColumn('metric_keys', Types::STRING, ['length' => 512, 'notnull' => true]);
		$table->addColumn('period', Types::STRING, ['length' => 32, 'notnull' => true]);
		$table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
		$table->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
		$table->addIndex(['user_id', 'created_at'], 'health_statview_user_created_idx');

		return $schema;
	}
}
