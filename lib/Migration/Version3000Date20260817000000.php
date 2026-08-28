<?php

declare(strict_types=1);

namespace OCA\Health\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** @psalm-suppress UnusedClass Migrations are discovered by Nextcloud. */
class Version3000Date20260817000000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if ($schema->hasTable('health_entries')) {
			return null;
		}

		$table = $schema->createTable('health_entries');
		$table->addColumn('id', Types::BIGINT, [
			'autoincrement' => true,
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->addColumn('user_id', Types::STRING, [
			'length' => 64,
			'notnull' => true,
		]);
		$table->addColumn('metric_key', Types::STRING, [
			'length' => 64,
			'notnull' => true,
		]);
		$table->addColumn('numeric_value', Types::DECIMAL, [
			'notnull' => false,
			'precision' => 20,
			'scale' => 6,
		]);
		$table->addColumn('option_value', Types::STRING, [
			'length' => 255,
			'notnull' => false,
		]);
		$table->addColumn('context', Types::STRING, [
			'length' => 32,
			'notnull' => true,
		]);
		$table->addColumn('recorded_at', Types::DATETIME_IMMUTABLE, [
			'notnull' => true,
		]);
		$table->addColumn('created_at', Types::DATETIME_IMMUTABLE, [
			'notnull' => true,
		]);
		$table->addColumn('updated_at', Types::DATETIME_IMMUTABLE, [
			'notnull' => true,
		]);
		$table->addColumn('note', Types::TEXT, [
			'notnull' => false,
		]);

		$table->setPrimaryKey(['id']);
		$table->addIndex(['user_id', 'recorded_at', 'id'], 'health_ent_user_rec_idx');
		$table->addIndex(['user_id', 'metric_key', 'recorded_at', 'id'], 'health_ent_user_met_rec_idx');

		return $schema;
	}
}
