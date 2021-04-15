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
  use Doctrine\DBAL\Types\Type;
  use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
  use OCP\Migration\SimpleMigrationStep;
  use OCP\Migration\IOutput;

class Version1100Date20210414000000 extends SimpleMigrationStep {

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
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
	{
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if($schema->hasTable('health_ces_contexts')) {
			$schema->dropTable('health_ces_contexts');
		}

		if($schema->hasTable('health_ces_entities')) {
			$schema->dropTable('health_ces_entities');
		}

		$this->expandPersons($schema);
		$this->createActivitiesData($schema);

		return $schema;
	}

	private function expandPersons(ISchemaWrapper $schema) {
		try {
			$table = $schema->getTable('health_persons');
			$newColumns = [
				'activities_column_calories',
				'activities_column_duration',
				'activities_column_category',
				'activities_column_feeling',
				'activities_column_intensity',
				'activities_column_distance',
			];
			foreach ($newColumns as $c) {
				if(!$table->hasColumn($c)) {
					$table->addColumn($c, 'boolean', [ 'default' => 0 ]);
				}
			}
			$table->addColumn('activities_distance_unit', 'text', [
				'notnull' => false,
				'default' => 'meters',
			]);
		} catch (SchemaException $e) {
			// TODO
		}
	}

	private function createActivitiesData(ISchemaWrapper $schema) {
		if (!$schema->hasTable('health_activitiesdata')) {
			$table = $schema->createTable('health_activitiesdata');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('datetime', 'datetime', [
				'default' => null,
				'notnull' => false,
			]);
			$table->addColumn('person_id', 'string', [
				'notnull' => true,
				'length' => 200,
			]);
			$table->addColumn('calories', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('duration', 'float', [
				'default' => null,
				'notnull' => false,
			]);
			$table->addColumn('category', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('feeling', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('intensity', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('distance', 'float', [
				'notnull' => false,
			]);
			$table->addColumn('comment', 'text', [
				'notnull' => false,
				'default' => null,
			]);
			$table->setPrimaryKey(['id']);
		}
	}

}
