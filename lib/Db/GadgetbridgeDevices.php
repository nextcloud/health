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

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class GadgetbridgeDevices extends Entity implements JsonSerializable {

	protected $gbId;
	protected $personId;
	protected $userId;
	protected $insertTime;
	protected $lastupdateTime;
	protected $name;
	protected $manufacturer;
	protected $identifier;
	protected $type;
	protected $model;
	protected $alias;

    public function __construct() {
		$this->addType('id','integer');
		$this->addType('personId','integer');
		$this->addType('gbId','integer');
	}

    public function jsonSerialize(): array
	{
        return [
			'id' => $this->id,
			'gbId' => $this->gbId,
			'personId' => $this->personId,
			'userId' => $this->userId,
			'insertTime' => $this->insertTime,
			'lastupdateTime' => $this->lastupdateTime,
			'name' => $this->name,
			'manufacturer' => $this->manufacturer,
			'identifier' => $this->identifier,
			'type' => $this->type,
			'model' => $this->model,
			'alias' => $this->alias,
        ];
    }
}
