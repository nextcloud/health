<?php

declare(strict_types=1);

namespace OCA\Health\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** @psalm-suppress UnusedClass Migrations are discovered by Nextcloud. */
class Version3000Date20260817220000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if ($schema->hasTable('health_daily_notes')) {
			return null;
		}

		$table = $schema->createTable('health_daily_notes');
		$table->addColumn('id', Types::BIGINT, [
			'autoincrement' => true,
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->addColumn('user_id', Types::STRING, [
			'length' => 64,
			'notnull' => true,
		]);
		$table->addColumn('local_date', Types::STRING, [
			'length' => 10,
			'notnull' => true,
		]);
		$table->addColumn('content', Types::TEXT, [
			'notnull' => true,
		]);
		$table->addColumn('created_at', Types::DATETIME_IMMUTABLE, [
			'notnull' => true,
		]);
		$table->addColumn('updated_at', Types::DATETIME_IMMUTABLE, [
			'notnull' => true,
		]);

		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['user_id', 'local_date'], 'health_dnote_user_date_uniq');

		return $schema;
	}
}
