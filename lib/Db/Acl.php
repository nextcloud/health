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

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Acl extends Entity implements JsonSerializable {
	public const PERMISSION_READ = 0;
	public const PERMISSION_EDIT = 1;
	public const PERMISSION_MANAGE = 2;

	public const PERMISSION_TYPE_USER = 0;
	public const PERMISSION_TYPE_GROUP = 1;

	protected $participant;
	protected $type;
	protected $personId;
	protected $permissionEdit = false;
	protected $permissionManage = false;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('personId', 'integer');
		$this->addType('permissionEdit', 'boolean');
		$this->addType('permissionManage', 'boolean');
		$this->addType('type', 'integer');
	}

	public function getPermission($permission) {
		switch ($permission) {
			case self::PERMISSION_READ:
				return true;
			case self::PERMISSION_EDIT:
				return $this->getPermissionEdit() ?? false;
			case self::PERMISSION_MANAGE:
				return $this->getPermissionManage() ?? false;
		}
		return false;
	}

	public function jsonSerialize(): array
	{
        return [
			'id' => $this->id,
			'personId' => $this->personId,
			'permissionEdit' => $this->permissionEdit,
			'permissionManage' => $this->permissionManage,
			'type' => $this->type,
			'participant' => $this->participant
        ];
    }
}
