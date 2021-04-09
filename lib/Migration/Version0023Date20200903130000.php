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
  use OCP\DB\ISchemaWrapper;
  use OCP\Migration\SimpleMigrationStep;
  use OCP\Migration\IOutput;

  class Version0023Date20200903130000 extends SimpleMigrationStep {

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

        $this->createPersons($schema);
        $this->createWeightdata($schema);

        return $schema;

    }

    private function createWeightdata(ISchemaWrapper $schema) {
                if (!$schema->hasTable('health_weightdata')) {
            $table = $schema->createTable('health_weightdata');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('person_id', 'string', [
                'notnull' => true,
                'length' => 200,
            ]);
            $table->addColumn('insert_time', 'datetime', [
            ]);
            $table->addColumn('lastupdate_time', 'datetime', [
            ]);
            $table->addColumn('bodyfat', 'integer', [
				'default' => null,
				'notnull' => false,
            ]);
            $table->addColumn('measurement', 'float', [
				'default' => null,
				'notnull' => false,
            ]);
            $table->addColumn('weight', 'float', [
				'default' => null,
				'notnull' => false,
            ]);
            $table->addColumn('date', 'datetime', [
				'default' => null,
				'notnull' => false,
            ]);

            $table->setPrimaryKey(['id']);
        }

    }

    private function createPersons(ISchemaWrapper $schema) {
        if (!$schema->hasTable('health_persons')) {
            $table = $schema->createTable('health_persons');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 200,
            ]);
            $table->addColumn('insert_time', 'datetime', [
            ]);
            $table->addColumn('lastupdate_time', 'datetime', [
            ]);
            $table->addColumn('name', 'string', [
                'notnull' => true,
                'length' => 200
            ]);
            $table->addColumn('age', 'integer', [
                'default' => null,
				'notnull' => false,
            ]);
            $table->addColumn('size', 'integer', [
				'default' => null,
				'notnull' => false,
            ]);
            $table->addColumn('enabled_module_weight', 'boolean', [
                'default' => 0,
            ]);
            $table->addColumn('enabled_module_breaks', 'boolean', [
                'default' => 0,
            ]);
            $table->addColumn('enabled_module_feeling', 'boolean', [
                'default' => 0,
            ]);
            $table->addColumn('enabled_module_medicine', 'boolean', [
                'default' => 0,
            ]);
            $table->addColumn('enabled_module_activities', 'boolean', [
                'default' => 0,
            ]);
            $table->addColumn('sex', 'string', [
                'length' => 6,
				'default' => null,
				'notnull' => false,
            ]);
            $table->addColumn('weight_measurement_name', 'string', [
                'length' => 255,
				'default' => null,
				'notnull' => false,
            ]);
            $table->addColumn('weight_target_bounty', 'string', [
				'default' => null,
				'notnull' => false,
                'length' => 600
            ]);
            $table->addColumn('weight_unit', 'string', [
				'default' => null,
				'notnull' => false,
                'length' => 20
            ]);
            $table->addColumn('weight_target', 'float', [
				'default' => null,
				'notnull' => false,
            ]);
            $table->addColumn('weight_target_initial_weight', 'float', [
				'default' => null,
				'notnull' => false,
            ]);
            $table->addColumn('weight_target_start_date', 'datetime', [
				'default' => null,
				'notnull' => false,
            ]);
            $table->addColumn('personal_mission', 'text', [
                'default' => '',
				'notnull' => false,
            ]);


            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'health_user_id_index');
        }
    }
}
