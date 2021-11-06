<?php
/**
 * @copyright Copyright (c) 2020 Florian Steffens <flost-dev@mailbox.org>
 *
 * @author Florian Steffens <flost-dev@mailbox.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Health\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version1400Date20211030000000 extends SimpleMigrationStep {

	/** @var IDBConnection */
	protected $connection;

	public function __construct(IDBConnection $connection) {
	  $this->connection = $connection;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @throws SchemaException
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
	{
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		// add module gadgetbridge
		if($schema->hasTable('health_persons')) {
			$table = $schema->getTable('health_persons');
			$table->addColumn('enabled_module_gadgetbridge', 'boolean', [ 'default' => 0, 'notnull' => false ]);
		}

		// add table gb_devices
		if (!$schema->hasTable('health_gb_devices')) {
			$table = $schema->createTable('health_gb_devices');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('gb_id', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('person_id', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 200,
			]);
			$table->addColumn('insert_time', 'datetime', ['notnull' => false]);
			$table->addColumn('lastupdate_time', 'datetime', ['notnull' => false]);
			$table->addColumn('name', 'string', [
				'notnull' => true,
				'length' => 200,
			]);
			$table->addColumn('manufacturer', 'string', [
				'notnull' => false,
				'length' => 200,
			]);
			$table->addColumn('identifier', 'string', [
				'notnull' => false,
				'length' => 200,
			]);
			$table->addColumn('type', 'string', [
				'notnull' => false,
				'length' => 200,
			]);
			$table->addColumn('model', 'string', [
				'notnull' => false,
				'length' => 200,
			]);
			$table->addColumn('alias', 'string', [
				'notnull' => false,
				'length' => 200,
			]);

			$table->setPrimaryKey(['id']);
		}

		// add table gb_activities
		if (!$schema->hasTable('health_gb_data')) {
			$table = $schema->createTable('health_gb_data');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('device_id', 'integer', ['notnull' => true]);
			$table->addColumn('sqlite_table', 'string', [
				'notnull' => false,
				'length' => 200,
			]);
			$table->addColumn('sqlite_user_id', 'integer', ['notnull' => false]);

			$table->addColumn('insert_time', 'datetime', ['notnull' => false]);
			$table->addColumn('lastupdate_time', 'datetime', ['notnull' => false]);
			$table->addColumn('time_from', 'datetime', ['notnull' => false]);
			$table->addColumn('time_to', 'datetime', ['notnull' => false]);

			$table->addColumn('steps', 'integer', ['notnull' => false, 'default' => 0]);
			$table->addColumn('calories', 'integer', ['notnull' => false, 'default' => 0]);
			$table->addColumn('variability', 'integer', ['notnull' => false]);
			$table->addColumn('variability_max', 'integer', ['notnull' => false]);
			$table->addColumn('heart_rate', 'integer', ['notnull' => false]);
			$table->addColumn('heart_rate_quality', 'integer', ['notnull' => false]);
			$table->addColumn('wear_type', 'integer', ['notnull' => false]);
			$table->addColumn('active', 'integer', ['notnull' => false]);

			$table->setPrimaryKey(['id']);
		}

		// add table gb_settings
		if (!$schema->hasTable('health_gb_settings')) {
			$table = $schema->createTable('health_gb_settings');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 200,
			]);
			$table->addColumn('person_id', 'integer', ['notnull' => false]);
			$table->addColumn('sqlite_source_path', 'string', [
				'notnull' => false,
				'length' => 500,
			]);
			$table->addColumn('background_import', Types::BOOLEAN, ['notnull' => false, 'default' => false]);
			$table->addColumn('last_import_time', Types::DATETIME, ['notnull' => false]);
			$table->addColumn('last_import_type', Types::TEXT, ['notnull' => false]);
			$table->addColumn('last_import_message', Types::TEXT, ['notnull' => false]);
			$table->addColumn('last_import_message_type', Types::TEXT, ['notnull' => false]);

			$table->setPrimaryKey(['id']);
		}

		return $schema;
	}

}
