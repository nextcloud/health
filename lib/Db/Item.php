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

use \JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Item extends Entity implements JsonSerializable {

	protected $data;
	protected $ownership;
	// protected $datetime;
	protected $context;
	protected $insertTime;
	protected $lastupdateTime;

    public function __construct() {
		$this->addType('id','integer');
		$this->addType('context','integer');
    }

    public function jsonSerialize() {
        return [
            'id' => $this->id,
			'data' => $this->data,
			// 'datetime' => $this->datetime,
			'ownership' => $this->ownership,
			'insertTime' => $this->insertTime,
			'lastupdateTime' => $this->lastupdateTime,
			// 'context' => $this->context,
        ];
    }
}
