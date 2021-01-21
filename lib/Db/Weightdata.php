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

use DateTime;
use \JsonSerializable;

use OCP\AppFramework\Db\Entity;

/**
 * @method setDate(string $format)
 */
class Weightdata extends Entity implements JsonSerializable {

    protected $insertTime;
    protected $lastupdateTime;
    protected $personId;
    protected $bodyfat;
    protected $bodyfat2;
    protected $measurement;
    protected $weight;
    protected $date;
    protected $waistSize;
    protected $hipSize;
    protected $musclePart;
    protected $comment;

    public function __construct() {
        $this->addType('id','integer');
        $this->addType('personId','integer');
        $this->addType('bodyfat','float');
        $this->addType('measurement','float');
        $this->addType('weight','float');
		$this->addType('waistSize', 'float');
		$this->addType('hipSize', 'float');
		$this->addType('musclePart', 'float');
    }

    public function jsonSerialize(): array
	{
        $date = new DateTime($this->date);
        return [
            'id' => $this->id,
            'insertTime' => $this->insertTime,
            'lastupdateTime' => $this->lastupdateTime,
            'personId' => $this->personId,
            'bodyfat' => $this->bodyfat,
            'measurement' => $this->measurement,
            'weight' => $this->weight,
            'date' => $date->format('Y-m-d'),
			'waistSize' => $this->waistSize,
			'hipSize' => $this->hipSize,
			'musclePart' => $this->musclePart,
			'comment' => $this->comment,
        ];
    }
}
