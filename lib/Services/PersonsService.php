<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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

namespace OCA\Health\Services;

use OCA\Health\Db\PersonMapper;
use OCA\Health\Db\WeightdataMapper;
use OCA\Health\Db\Person;

class PersonsService {

	protected $personMapper;
	protected $weightdataMapper;
	protected $userId;

	public function __construct(PersonMapper $pM, $userId, WeightdataMapper $wdM) {
		$this->personMapper = $pM;
		$this->userId = $userId;
		$this->weightdataMapper = $wdM;
	}

	public function getAllPersons($full=true) {
		$persons = $this->personMapper->findAll($this->userId);
		foreach($persons as $i => $p) {
			$p->setWeightdata($this->weightdataMapper->findAll($p->id));
		}
		return $persons;
	}

	public function createPerson($name) {
		$time = new \DateTime();
		$p = new Person();
		$p->setInsertTime($time->format('Y-m-d H:i:s'));
		$p->setLastupdateTime($time->format('Y-m-d H:i:s'));
		$p->setUserId($this->userId);
		$p->setName($name);
		$p->setEnabledModuleWeight(true);
		$p->setWeightUnit('kg');
		return $this->personMapper->insert($p);
	}

	public function deletePerson($id) {
		try {
             $person = $this->personMapper->find($id, $this->userId);
         } catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
         }
         $this->personMapper->delete($person);
         return new $person;
	}

	public function updatePerson($id, $key, $value) {
		try {
             $person = $this->personMapper->find($id, $this->userId);
         } catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
         }

         $method = 'set'.ucfirst($key);
         $person->{$method}($this->typeCast($key, $value));
         $this->personMapper->update($person);

         return $person;
	}

	private function typeCast($key, $value) {
		 $intData = ['age', 'size'];
         if(in_array($key, $intData)) {
         	$value = intval($value);
         }

         $doubleData = ['weightTarget', 'weightTargetInitialWeight'];
         if(in_array($key, $doubleData)) {
         	$value = floatval($value);
         }

         $booleanData = ['enabledModuleWeight'];
         if(in_array($key, $booleanData)) {
         	if( $value === true || $value === 'true' || $value === 1 || $value === '1') {
         		$value = true;
         	} else {
         		$value = false;
         	}
         }

         $datetimeData = ['weightTargetStartDate'];
         if(in_array($key, $datetimeData)) {
         	$dt = new \DateTime($value);
         	$value = $dt->format('Y-m-d H:i:s');
         }

         return $value;
	}
}
