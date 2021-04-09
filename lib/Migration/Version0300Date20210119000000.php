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

class Version0300Date20210119000000 extends SimpleMigrationStep {

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
		$this->expandWeightdata($schema);
		$this->createFeelingData($schema);
		$this->createMeasurementData($schema);
		$this->createSleepData($schema);

		return $schema;
	}

	private function expandPersons(ISchemaWrapper $schema) {
		try {
			$table = $schema->getTable('health_persons');
			$newColumns = [
				'feeling_column_energy',
				'feeling_column_sadness_level',
				'measurement_column_temperature',
				'measurement_column_heart_rate',
				'measurement_column_blood_pres',
				'measurement_column_oxygen_sat',
				'measurement_column_blood_sugar',
				'measurement_column_defecation',
				'measurement_column_appetite',
				'measurement_column_allergies',
				'measurement_chart_detail',
				'weight_column_weight',
				'weight_column_bodyfat',
				'weight_column_measurement',
				'weight_column_waist_size',
				'weight_column_hip_size',
				'weight_column_muscle_part',
				'sleep_column_quality',
				'sleep_column_wakeups',
			];
			foreach ($newColumns as $c) {
				if(!$table->hasColumn($c)) {
					$table->addColumn($c, 'boolean', [ 'default' => 0 ]);
				}
			}
			$table->addColumn('shared', 'text', [
				'notnull' => false,
				'default' => null,
			]);
			$table->addColumn('shared_readonly', 'text', [
				'notnull' => false,
				'default' => null,
			]);
		} catch (SchemaException $e) {
		}
	}

	private function expandWeightdata(ISchemaWrapper $schema) {
		try {
			$table = $schema->getTable('health_weightdata');
			$table->addColumn('waist_size', 'float', [
				'notnull' => false,
				'default' => null,
			]);
			$table->addColumn('hip_size', 'float', [
				'notnull' => false,
				'default' => null,
			]);
			$table->addColumn('muscle_part', 'float', [
				'notnull' => false,
				'default' => null,
			]);
			$table->addColumn('comment', 'text', [
				'notnull' => false,
				'default' => null,
			]);
		} catch (SchemaException $e) {
		}
	}

	private function createFeelingData(ISchemaWrapper $schema) {
		if (!$schema->hasTable('health_feelingdata')) {
			$table = $schema->createTable('health_feelingdata');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('person_id', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('datetime', 'datetime', [
				'default' => null,
				'notnull' => false,
			]);
			$table->addColumn('mood', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('sadness_level', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('symptoms', 'text', [
				'notnull' => false,
				'default' => null,
			]);
			$table->addColumn('attacks', 'text', [
				'notnull' => false,
				'default' => null,
			]);
			$table->addColumn('medication', 'text', [
				'notnull' => false,
				'default' => null,
			]);
			$table->addColumn('pain', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('energy', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('comment', 'text', [
				'notnull' => false,
				'default' => null,
			]);
			$table->setPrimaryKey(['id']);
		}
	}

	private function createMeasurementData(ISchemaWrapper $schema) {
		if (!$schema->hasTable('health_measurementdata')) {
			$table = $schema->createTable('health_measurementdata');
			/** @noinspection DuplicatedCode */
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('person_id', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('datetime', 'datetime', [
				'default' => null,
				'notnull' => false,
			]);
			$table->addColumn('temperature', 'float', [
				'notnull' => false,
			]);
			$table->addColumn('heart_rate', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('blood_pressure_s', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('blood_pressure_d', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('blood_sugar', 'float', [
				'notnull' => false,
			]);
			$table->addColumn('oxygen_sat', 'float', [
				'notnull' => false,
			]);
			$table->addColumn('defecation', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('appetite', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('allergies', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('cigarettes', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('alcohol', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('comment', 'text', [
				'notnull' => false,
				'default' => null,
			]);
			$table->setPrimaryKey(['id']);
		}
	}

	private function createSleepData(ISchemaWrapper $schema) {
		if (!$schema->hasTable('health_sleepdata')) {
			$table = $schema->createTable('health_sleepdata');
			/** @noinspection DuplicatedCode */
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('person_id', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('asleep', 'datetime', [
				'default' => null,
				'notnull' => false,
			]);
			$table->addColumn('wakeup', 'datetime', [
				'default' => null,
				'notnull' => false,
			]);
			$table->addColumn('quality', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('counted_wakeups', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('duration_wakeups', 'integer', [
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
