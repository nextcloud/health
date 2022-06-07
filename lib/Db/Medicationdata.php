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

class Medicationdata extends Entity implements JsonSerializable {

	protected $planId;
	protected $name;
	protected $identifier;
	protected $morning;
	protected $noon;
	protected $evening;
	protected $night;
	protected $comment;

    public function __construct() {
		$this->addType('id','integer');
		$this->addType('planId','integer');
		$this->addType('morning','integer');
		$this->addType('noon','integer');
		$this->addType('evening','integer');
		$this->addType('night','integer');
	}

    public function jsonSerialize(): array
	{
        return [
			'id' => $this->id,
			'planId' => $this->planId,
			'name' => $this->name,
			'identifier' => $this->identifier,
			'morning' => $this->morning,
			'noon' => $this->noon,
			'evening' => $this->evening,
			'night' => $this->night,
			'comment' => $this->comment,
        ];
    }
}
