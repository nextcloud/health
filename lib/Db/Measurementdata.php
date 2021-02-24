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

class Measurementdata extends Entity implements JsonSerializable {

	protected $personId;
	protected $datetime;
	protected $temperature;
	protected $heartRate;
	protected $bloodPressureS;
	protected $bloodPressureD;
	protected $bloodSugar;
	protected $oxygenSat;
	protected $defecation;
	protected $appetite;
	protected $allergies;
	protected $cigarettes;
	protected $alcohol;
	protected $comment;

    public function __construct() {
		$this->addType('id','integer');
		$this->addType('temperature','float');
		$this->addType('heartRate','integer');
		$this->addType('bloodPressureS','integer');
		$this->addType('bloodPressureD','integer');
		$this->addType('bloodSugar','float');
		$this->addType('oxygenSat','float');
		$this->addType('defecation','integer');
		$this->addType('cigarettes','integer');
		$this->addType('alcohol','integer');
	}

    public function jsonSerialize(): array
	{
        return [
			'id' => $this->id,
			'personId' => $this->personId,
			'datetime' => $this->datetime,
			'temperature' => $this->temperature,
			'heartRate' => $this->heartRate,
			'bloodPressureS' => $this->bloodPressureS,
			'bloodPressureD' => $this->bloodPressureD,
			'bloodSugar' => $this->bloodSugar,
			'oxygenSat' => $this->oxygenSat,
			'defecation' => $this->defecation,
			'appetite' => $this->appetite,
			'allergies' => $this->allergies,
			'cigarettes' => $this->cigarettes,
			'alcohol' => $this->alcohol,
			'comment' => $this->comment,
        ];
    }
}
