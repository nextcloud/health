<?php

declare(strict_types=1);
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

namespace OCA\Health\Services;

use OCA\Health\Db\Acl;
use OCA\Health\Db\Person;
use OCP\IGroupManager;

class PermissionHelperService {

	protected $userId;
	private $groupManager;

	public function __construct(
		$userId,
		IGroupManager $groupManager
	) {
		$this->userId = $userId;
		$this->groupManager = $groupManager;
	}

	/**
	 * Resolved the permission of a person according to existing ACL's.
	 *
	 * @param Person $person
	 * @return array
	 */
	public function matchPermissions(Person $person) {
		$owner = $person->getUserId() === $this->userId;
		$acls = $person->getAcl() ?? [];
		return [
			Acl::PERMISSION_READ => $owner || $this->userCan($acls, Acl::PERMISSION_READ),
			Acl::PERMISSION_EDIT => $owner || $this->userCan($acls, Acl::PERMISSION_EDIT),
			Acl::PERMISSION_MANAGE => $owner || $this->userCan($acls, Acl::PERMISSION_MANAGE),
		];
	}

	/**
	 * Check if permission matches the acl rules for current user and groups.
	 *
	 * @param Acl[] $acls
	 * @param $permission
	 * @return bool
	 */
	public function userCan(array $acls, $permission): bool {
		foreach ($acls as $acl) {
			if ($acl->getType() === Acl::PERMISSION_TYPE_USER && $acl->getParticipant()["uid"] === $this->userId) {
				return $acl->getPermission($permission);
			}
			if ($acl->getType() === Acl::PERMISSION_TYPE_GROUP && $this->groupManager->isInGroup($this->userId, $acl->getParticipant()["uid"])) {
				return $acl->getPermission($permission);
			}
		}
		return false;
	}

	/**
	 * Get all person ids that are shared with the current user.
	 *
	 * @param Acl[] $acls
	 * @return array
	 */
	public function getSharedPersonsOfCurrentUser(array $acls): array {
		$personIds = array();
		foreach($acls as $acl) {
			if ($acl->getType() == Acl::PERMISSION_TYPE_USER && $acl->getParticipant()["uid"] == $this->userId) {
				$personIds[] = $acl->getPersonId();
			}
			if ($acl->getType() == Acl::PERMISSION_TYPE_GROUP && $this->groupManager->isInGroup($this->userId, $acl->getParticipant()["uid"])) {
				$personIds[] = $acl->getPersonId();
			}
		}

		return $personIds;
	}
}
