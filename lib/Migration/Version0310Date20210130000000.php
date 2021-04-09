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

class Version0310Date20210130000000 extends SimpleMigrationStep {

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

		$this->expandPersons($schema);
		$this->createSmokingData($schema);

		return $schema;
	}

	private function expandPersons(ISchemaWrapper $schema) {
		try {
			$table = $schema->getTable('health_persons');
			$newColumns = [
				'weight_column_bmi',
				'enabled_module_smoking',
				'smoking_column_cigarettes',
				'smoking_column_desire_level',
				'smoking_column_saved_money',
			];
			foreach ($newColumns as $c) {
				if(!$table->hasColumn($c)) {
					$table->addColumn($c, 'boolean', [ 'default' => 0 ]);
				}
			}
			$newColumns = [
				'smoking_goal',
				'smoking_start_value',
			];
			foreach ($newColumns as $c) {
				if(!$table->hasColumn($c)) {
					$table->addColumn($c, 'integer', [ 'default' => null, 'notnull' => false ]);
				}
			}
			$newColumns = [
				'smoking_price',
			];
			foreach ($newColumns as $c) {
				if(!$table->hasColumn($c)) {
					$table->addColumn($c, 'float', [ 'default' => null, 'notnull' => false ]);
				}
			}
			$newColumns = [
				'currency',
			];
			foreach ($newColumns as $c) {
				if(!$table->hasColumn($c)) {
					$table->addColumn($c, 'text', [ 'default' => null, 'notnull' => false ]);
				}
			}
		} catch (SchemaException $e) {
		}
	}

	private function createSmokingData(ISchemaWrapper $schema) {
		if (!$schema->hasTable('health_smokingdata')) {
			$table = $schema->createTable('health_smokingdata');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('person_id', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('date', 'datetime', [
				'default' => null,
				'notnull' => false,
			]);
			$table->addColumn('cigarettes', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('desire_level', 'integer', [
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
