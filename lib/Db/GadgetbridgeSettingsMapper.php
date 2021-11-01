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

namespace OCA\Health\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\Exception;
use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class GadgetbridgeSettingsMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'health_gb_settings', GadgetbridgeSettings::class);
    }

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function find(int $id): Entity
	{
        $qb = $this->db->getQueryBuilder();

                    $qb->select('*')
                             ->from($this->getTableName())
                             ->where(
                                     $qb->expr()->eq('id', $qb->createNamedParameter($id))
                             );

        return $this->findEntity($qb);
    }

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function findBy(string $userId, int $personId): Entity
	{
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from($this->getTableName())
           ->where(
            $qb->expr()->eq('user_id', $qb->createNamedParameter($userId))
		   )
			->andWhere(
				$qb->expr()->eq('person_id', $qb->createNamedParameter($personId))
           );

        return $this->findEntity($qb);
    }

}
