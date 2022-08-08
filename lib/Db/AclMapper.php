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

namespace OCA\Health\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\AppFramework\Db\QBMapper;
use Psr\Log\LoggerInterface;

class AclMapper extends QBMapper {

	private $userManager;
	private $groupManager;
	protected $logger;

	public function __construct(
		IDBConnection $db,
		IUserManager $userManager,
		IGroupManager $groupManager
		, LoggerInterface $logger
	) {
		parent::__construct($db, 'health_persons_acl', Acl::class);
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
				$this->logger = $logger;
	}

	public function findAllByPerson($personId) {
		$qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from($this->getTableName())
           ->where(
            $qb->expr()->eq('person_id', $qb->createNamedParameter($personId))
           );

		$entities = $this->findEntities($qb);
		foreach ($entities as $acl) {
			$this->mapParticipant($acl);
		}
		return $entities;
	}

	public function findAll() {
		$qb = $this->db->getQueryBuilder();

        $qb->select('*')
			->from($this->getTableName());

		$entities = $this->findEntities($qb);
		foreach ($entities as $acl) {
			$this->mapParticipant($acl);
		}
		return $entities;
	}

	public function find(int $id) {
        $qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id))
			);

        return $this->findEntity($qb);
    }

	public function mapParticipant(Acl $acl) {
		if ($acl->getType() === Acl::PERMISSION_TYPE_USER) {
			$user = $this->userManager->get($acl->getParticipant());
			if ($user !== null) {
				$acl->setParticipant([
					'uid' => $user->getUID(),
					'displayname' => $user->getDisplayName(),
					'type' => 0
				]);
				return $acl;
			}
			return null;
		}
		if ($acl->getType() === Acl::PERMISSION_TYPE_GROUP) {
			$group = $this->groupManager->get($acl->getParticipant());
			if ($group !== null) {
				$acl->setParticipant([
					'uid' => $group->getGID(),
					'displayname' => $group->getDisplayName(),
					'type' => 1
				]);
				return $acl;
			}
			return null;
		}
	}
}
