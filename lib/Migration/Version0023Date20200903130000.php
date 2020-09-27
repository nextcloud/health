<?php

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
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
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
            ]);
            $table->addColumn('measurement', 'float', [
                'default' => null,
            ]);
            $table->addColumn('weight', 'float', [
                'default' => null,
            ]);
            $table->addColumn('date', 'datetime', [
                'default' => null,
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
            ]);
            $table->addColumn('size', 'integer', [
            ]);
            $table->addColumn('enabled_module_weight', 'boolean', [
            ]);
            $table->addColumn('enabled_module_breaks', 'boolean', [
            ]);
            $table->addColumn('enabled_module_feeling', 'boolean', [
            ]);
            $table->addColumn('sex', 'string', [
                'length' => 6
            ]);
            $table->addColumn('weight_measurement_name', 'string', [
                'length' => 255
            ]);
            $table->addColumn('weight_target_bounty', 'string', [
                'length' => 600
            ]);
            $table->addColumn('weight_unit', 'string', [
                'length' => 20
            ]);
            $table->addColumn('weight_target', 'float', [
            ]);
            $table->addColumn('weight_target_initial_weight', 'float', [
            ]);
            $table->addColumn('weight_target_start_date', 'datetime', [
            ]);
            $table->addColumn('personal_mission', 'text', [
                'notnull' => true,
                'default' => ''
            ]);


            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'health_user_id_index');
        }
    }
}