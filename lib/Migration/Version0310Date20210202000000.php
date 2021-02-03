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
  use OCP\IDBConnection;
  use OCP\Migration\SimpleMigrationStep;
  use OCP\Migration\IOutput;

class Version0310Date20210202000000 extends SimpleMigrationStep {

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

		return $schema;
	}

	private function expandPersons(ISchemaWrapper $schema) {
		try {
			$table = $schema->getTable('health_persons');
			$newColumns = [
				'smoking_desire_level',
				'smoking_cigarettes',
				'smoking_saved_money',
			];
			foreach ($newColumns as $c) {
				if(!$table->hasColumn($c)) {
					$table->addColumn($c, TYPE::INTEGER, [ 'default' => null, 'notnull' => false ]);
				}
			}
		} catch (SchemaException $e) {
		}
	}

}
