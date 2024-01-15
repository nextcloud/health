<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace OCA\Health\Migration;

use Closure;
use OCP\DB\Exception;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version030000Date20240115000000 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @throws Exception
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('health3_persons')) {
			$table = $schema->createTable('health3_persons');

			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);

			// who manages this person
			$table->addColumn('user_id', Types::STRING, ['notnull' => true]);

			// timestamps
			$table->addColumn('created_at', Types::DATETIME, ['notnull' => true]);
			$table->addColumn('created_by', Types::STRING, ['notnull' => true, 'length' => 128]);
			$table->addColumn('last_edit_at', Types::DATETIME, ['notnull' => true]);
			$table->addColumn('last_edit_by', Types::STRING, ['notnull' => true, 'length' => 128]);

			// properties
			$table->addColumn('name', Types::STRING, ['notnull' => true, 'length' => 200]);
			$table->addColumn('birthday', Types::DATE, ['notnull' => false]);
			$table->addColumn('modules', Types::STRING, ['notnull' => false]); // enabled modules
			$table->addColumn('sex', Types::STRING, ['notnull' => false]); // male, female, divers, other
			$table->addColumn('mission', Types::TEXT, ['notnull' => false]);
			$table->addColumn('unit_currency', Types::STRING, ['notnull' => false, 'length' => 10]);
			$table->addColumn('unit_distance', Types::STRING, ['notnull' => false, 'length' => 10]);
			$table->addColumn('unit_weight', Types::STRING, ['notnull' => false, 'length' => 10]);

			// db keys
			$table->setPrimaryKey(['id']);

			// indexes
			$table->addIndex(['id'], 'health_person_id_idx');
			$table->addIndex(['user_id'], 'health_user_id_idx');
		}

		return $schema;
	}
}
