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

  class Version0140Date20201117200000 extends SimpleMigrationStep {

    /**
    * @param IOutput $output
    * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
    * @param array $options
    * @return null|ISchemaWrapper
    */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Context Entity Storage
        $this->createCES($schema);

        return $schema;

    }

    private function createCES(ISchemaWrapper $schema) {
		if (!$schema->hasTable('health_ces_contexts')) {
			$table = $schema->createTable('health_ces_contexts');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('description', 'text', [
				'notnull' => false,
				'default' => null,
			]);
			$table->addColumn('insert_time', 'datetime', [
				'default' => 'CURRENT_TIMESTAMP',
			]);
			$table->addColumn('lastupdate_time', 'datetime', [
				'default' => 'CURRENT_TIMESTAMP',
			]);

            $table->setPrimaryKey(['id']);
        }

		if (!$schema->hasTable('health_ces_entities')) {
			$table = $schema->createTable('health_ces_entities');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('context', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('data', 'text', [
				'notnull' => false,
				'default' => null,
			]);
			$table->addColumn('ownership', 'text', [
				'notnull' => false,
				'default' => null,
			]);
			$table->addColumn('insert_time', 'datetime', [
				'default' => 'CURRENT_TIMESTAMP',
			]);
			$table->addColumn('lastupdate_time', 'datetime', [
				'default' => 'CURRENT_TIMESTAMP',
			]);
			// $table->addColumn('datetime', 'datetime', []);

			$table->setPrimaryKey(['id']);
		}
    }
}
