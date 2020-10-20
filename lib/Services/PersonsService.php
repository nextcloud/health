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
use OCA\Health\Services\FormatHelperService;
use OCA\Health\Services\WeightdataService;

class PersonsService {

	protected $personMapper;
	protected $weightdataService;
	protected $userId;
	protected $formatHelperService;

	public function __construct(PersonMapper $pM, $userId, WeightdataService $wdS, FormatHelperService $fhS) {
		$this->personMapper = $pM;
		$this->userId = $userId;
		$this->weightdataService = $wdS;
		$this->formatHelperService = $fhS;
	}

	public function getAllPersons($full=true) {
		$persons = $this->personMapper->findAll($this->userId);
		// foreach($persons as $i => $p) {
		// $p->setWeightdata($this->weightdataService->getAllByPersonId($p->id));
		// }
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
         $wd = $this->weightdataService->getAllByPersonId($id);
         foreach( $wd as $i ) {
         	$this->weightdataService->delete($i->id);
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
         $person->{$method}($this->formatHelperService->typeCast($key, $value));
         $this->personMapper->update($person);

         return $person;
	}

	public function getData($personId) {
		return [
			'lastWeight' => $this->weightdataService->getLastWeight($personId)
		];
	}

}
