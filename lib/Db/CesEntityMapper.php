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
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IDbConnection;
use OCP\AppFramework\Db\QBMapper;
use OCP\ILogger;

class CesEntityMapper extends QBMapper {

	protected $logger;
	protected $userId;

    public function __construct(IDbConnection $db, ILogger $ILogger, $userId) {
        parent::__construct($db, 'health_ces_entities', Item::class);
        $this->logger = $ILogger;
        $this->userId = $userId;
    }

    public function find($contexts = null, $entityFilter = null) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*');
		$qb->from($this->getTableName());

		$c = 0;
		if($contexts !== null) {
			foreach ($contexts as $context) {
				if($c === 0) {
					$qb->where('context LIKE :c'.$c);
					$qb->setParameter(':c'.$c, $context->getId());
				} else {
					$qb->orWhere('context LIKE :c'.$c);
					$qb->setParameter(':c'.$c, $context->getId());
				}
				$c = $c + 1;
			}
		}

		$first = true;
		if($entityFilter !== null) {
			foreach ($entityFilter as $key => $value) {
				if($key === 'id') {
					if($first && $c === 0) {
						$qb->where('id = :id');
						$first = false;
					} else {
						$qb->andWhere('id = :id');
					}
					$qb->setParameter(':id', $value);
				} else {
					if($first && $c === 0) {
						// $qb->where('data->>\'$.'.$key.'\' LIKE :'.$key);
						// $qb->where('`data` LIKE \'%"'.$key.'"\:":'.$key.'"%\'');
						// $qb->where($this->getWhereClause('data', $key, $value));
						$qb->where('`data` LIKE :'.$key);
						$first = false;
					} else {
						// $qb->andWhere($this->getWhereClause('data', $key, $value));
						$qb->andWhere('`data` LIKE :'.$key);
					}
					// $qb->setParameter($key, $value);
					$qb->setParameter($key, $this->getParameterClause($key, $value));
				}
			}
		}

		if($first && $c === 0) {
			// $qb->where('ownership->>\'$.owner\' LIKE :userId');
			// $qb->where("`ownership` LIKE '%\"owner\":\":userId\"%'");
			$qb->where("`ownership` LIKE :userId");
		} else {
			$qb->andWhere("`ownership` LIKE :userId");
		}
		$qb->setParameter(':userId', '%"owner":"'.$this->userId.'"%');

		return $this->findEntities($qb);
    }

	private function getParameterClause($key, $value): string
	{
		$numberTypes = ['integer', 'double', 'float'];
		if(in_array(gettype($value), $numberTypes)) {
			return '%"'.$key.'":'.$value.'%';
		} else {
			return '%"'.$key.'":"'.$value.'"%';
		}
	}

}
