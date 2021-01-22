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

class Feelingdata extends Entity implements JsonSerializable {

	protected $personId;
	protected $datetime;
	protected $mood;
	protected $sadnessLevel;
	protected $symptoms;
	protected $attacks;
	protected $medication;
	protected $pain;
	protected $energy;
	protected $comment;

    public function __construct() {
		$this->addType('id','integer');
		$this->addType('mood','integer');
		$this->addType('sadnessLevel','integer');
		$this->addType('pain','integer');
		$this->addType('energy','integer');
	}

    public function jsonSerialize(): array
	{
        return [
			'id' => $this->id,
			'personId' => $this->personId,
			'datetime' => $this->datetime,
			'mood' => $this->mood,
			'sadnessLevel' => $this->sadnessLevel,
			'symptoms' => $this->symptoms,
			'attacks' => $this->attacks,
			'medication' => $this->medication,
			'pain' => $this->pain,
			'energy' => $this->energy,
			'comment' => $this->comment,
        ];
    }
}
