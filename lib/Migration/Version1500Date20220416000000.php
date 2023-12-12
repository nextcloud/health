<?php
/**
 * @copyright Copyright (c) 2020 Florian Steffens <flost-dev@mailbox.org>
 *
 * @author Marcus Nitzschke <mail@kendix.org>
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
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1500Date20220416000000 extends SimpleMigrationStep {

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
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$this->createMedicationPlanTable($schema);
		$this->createMedicationDataTable($schema);

		return $schema;
	}

	private function createMedicationPlanTable(ISchemaWrapper $schema) {
		if (!$schema->hasTable('health_medicationplans')) {
			$table = $schema->createTable('health_medicationplans');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('person_id', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('date', 'datetime', [
				'default' => null,
				'notnull' => true,
			]);
			$table->addColumn('comment', 'text', [
				'notnull' => false,
				'default' => null,
			]);
			$table->setPrimaryKey(['id']);
		}
	}

	private function createMedicationDataTable(ISchemaWrapper $schema) {
		if (!$schema->hasTable('health_medicationdata')) {
			$table = $schema->createTable('health_medicationdata');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('plan_id', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('name', 'string', [
				'notnull' => true,
			]);
			$table->addColumn('identifier', 'string', [
				'notnull' => false,
			]);
			$table->addColumn('morning', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('noon', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('evening', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('night', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('comment', 'text', [
				'notnull' => false,
				'default' => null,
			]);
			$table->setPrimaryKey(['id']);
			$table->addForeignKeyConstraint($schema->getTable('health_medicationplans'), ['plan_id'], ['id'], [], 'fk_health_medication_plan_id');
		}
	}

}
