<?php
/**
 * @copyright Copyright (c) 2020 Florian Steffens <flost-dev@mailbox.org>
 *
 * @author Florian Steffens <flost-dev@mailbox.org>
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

namespace OCA\Health\Db;

use OCA\Health\Db\Acl;
use OCA\Health\Db\AclMapper;
use OCA\Health\Services\PermissionHelperService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\AppFramework\Db\QBMapper;

class PersonMapper extends QBMapper {

	private $aclMapper;
	private $groupManager;
	private $permissionHelper;

    public function __construct(
		IDBConnection $db,
		AclMapper $aclMapper,
		IGroupManager $groupManager,
		PermissionHelperService $permissionHelper
	) {
		parent::__construct($db, 'health_persons', Person::class);
		$this->aclMapper = $aclMapper;
		$this->groupManager = $groupManager;
		$this->permissionHelper = $permissionHelper;
    }

    public function find(int $id, string $userId = ""): ?Entity
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select('p.*');
		$qb->from($this->getTableName(), 'p');
		if($userId !== "") {
			$qb->leftJoin('p', 'health_persons_acl', 'acl', 'p.id=acl.person_id');
		}
		$qb->where( $qb->expr()->eq('p.id', $qb->createNamedParameter($id)) );

		if($userId !== "") {
			$qb->andWhere( $qb->expr()->orX(
				$qb->expr()->eq('acl.participant', $qb->createNamedParameter($userId) ),
				$qb->expr()->eq('p.user_id', $qb->createNamedParameter($userId) )
			));
		}
		$qb->groupBy('p.id');
		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException | MultipleObjectsReturnedException $e) {
			return null;
		}
	}

    public function findAll(string $userId): array
	{
		$qb = $this->db->getQueryBuilder();

		// get all acl's
		$acls = $this->aclMapper->findAll();
		$personIds = $this->permissionHelper->getSharedPersonsOfCurrentUser($acls);

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

		if (count($personIds) > 0) {
			$qb->orWhere("id IN (".implode(",", $personIds).")");
		}
		return $this->findEntities($qb);
	}

}
