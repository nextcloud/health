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
  use OCP\Migration\SimpleMigrationStep;
  use OCP\Migration\IOutput;

  class Version0123Date20201028200000 extends SimpleMigrationStep {

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

		$this->extendPersons($schema);

		return $schema;
	}

    private function extendPersons(ISchemaWrapper $schema)
	{
		try {
			$table = $schema->getTable('health_persons');
			$newColumns = [
				'feeling_column_mood',
				'feeling_column_sadness',
				'feeling_column_symptoms',
				'feeling_column_attacks',
				'feeling_column_medication',
				'feeling_column_pain',
			];
			foreach ($newColumns as $c) {
				if(!$table->hasColumn($c)) {
					$table->addColumn($c, 'boolean', [ 'default' => 1 ]);
				}
			}

			$c = 'feeling_special_symptom_name';
			if(!$table->hasColumn($c)) {
				$table->addColumn($c, 'string', [
					'default' 	=> '',
					'notnull' 	=> false,
					'length'	=> 200
				]);
			}

			$c = 'feeling_special_attack_name';
			if(!$table->hasColumn($c)) {
				$table->addColumn($c, 'string', [
					'default' 	=> '',
					'notnull' 	=> false,
					'length'	=> 200
				]);
			}

			$c = 'feeling_default_medication';
			if(!$table->hasColumn($c)) {
				$table->addColumn($c, 'string', [
					'default' 	=> '',
					'notnull' 	=> false,
					'length'	=> 600
				]);
			}
		} catch (SchemaException $e) {
		}
	}
}
