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

  class Version0230Date20210110000000 extends SimpleMigrationStep {

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
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
		$table = $schema->getTable('health_weightdata');

		$table = $schema->getTable('health_weightdata');
		if($table->hasColumn('bodyfat') && $table->hasColumn('bodyfat2')) {
			$table->dropColumn('bodyfat');
			$table->addColumn('bodyfat', 'float', [
				'default' => null,
				'notnull' => false,
			]);
		}
        return $schema;
    }

	  public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		  // copy data into tmp column
		  $query = $this->connection->getQueryBuilder();
		  $query->update('health_weightdata')
			  ->set('bodyfat', 'bodyfat2');
		  $query->execute();
	  }
}
