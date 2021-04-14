<?php
declare(strict_types=1);
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

namespace OCA\Health\Services;

use OCA\Health\Db\PersonMapper;
use OCA\Health\Db\Person;
use Exception;
use OCP\AppFramework\Http;
use OCP\IUserManager;

class PersonsService {

	protected $personMapper;
	protected $weightdataService;
	protected $sleepdataService;
	protected $feelingdataService;
	protected $measurementdataService;
	protected $activitiesdataService;
	protected $userId;
	protected $formatHelperService;
	protected $permissionService;
	protected $userManager;

	public function __construct(PersonMapper $personMapper,
								$userId,
								WeightdataService $weightdataService,
								FormatHelperService $formatHelperService,
								PermissionService $permissionService,
								IUserManager $userManager,
								FeelingdataService $feelingdataService,
								SleepdataService $sleepdataService,
								MeasurementdataService $measurementdataService,
								ActivitiesdataService $activitiesdataService
	) {
		$this->personMapper = $personMapper;
		$this->userId = $userId;
		$this->weightdataService = $weightdataService;
		$this->formatHelperService = $formatHelperService;
		$this->permissionService = $permissionService;
		$this->userManager = $userManager;
		$this->sleepdataService = $sleepdataService;
		$this->measurementdataService = $measurementdataService;
		$this->feelingdataService = $feelingdataService;
		$this->activitiesdataService = $activitiesdataService;
	}

	public function getAllPersons(): array
	{
		$persons = $this->personMapper->findAll($this->userId);
		if( count($persons) === 0) {
			$user = $this->userManager->get($this->userId);
			$this->createPerson( $user->getDisplayName() );
			$persons = $this->personMapper->findAll($this->userId);
		}
		return $persons;
	}

	/** @noinspection PhpUndefinedMethodInspection */
	public function createPerson($name): \OCP\AppFramework\Db\Entity
	{
		$time = new \DateTime();
		$p = new Person();
		$p->setInsertTime($time->format('Y-m-d H:i:s'));
		$p->setLastupdateTime($time->format('Y-m-d H:i:s'));
		$p->setUserId($this->userId);
		$p->setName($name);
		$p->setEnabledModuleWeight(true);
		$p->setEnabledModuleBreaks(false);
		$p->setEnabledModuleFeeling(true);
		$p->setEnabledModuleMedicine(false);
		$p->setEnabledModuleActivities(false);
		$p->setEnabledModuleSleep(true);
		$p->setEnabledModuleNutrition(false);
		$p->setEnabledModuleMeasurement(true);
		$p->setEnabledModuleActivities(true);
		$p->setWeightUnit('kg');
		$p->setWeightColumnWeight(true);
		$p->setWeightColumnBodyfat(true);
		$p->setMeasurementColumnTemperature(true);
		$p->setMeasurementColumnHeartRate(true);
		$p->setMeasurementColumnBloodPres(true);
		$p->setFeelingColumnMood(true);
		$p->setFeelingColumnEnergy(true);
		$p->setSleepColumnQuality(true);
		$p->setSmokingColumnDesireLevel(true);
		$p->setSmokingColumnCigarettes(true);
		$p->setSmokingColumnSavedMoney(true);
		$p->setActivitiesColumnCalories(true);
		$p->setActivitiesColumnDuration(true);
		$p->setActivitiesColumnCategory(true);
		$p->setActivitiesDistanceUnit('m');
		return $this->personMapper->insert($p);
	}

	public function deletePerson($id) {
		if( !$this->permissionService->personData($id, $this->userId)) {
			return null;
		}

		try {
			$person = $this->personMapper->find($id, $this->userId);
		} catch(Exception $e) {
			return Http::STATUS_NOT_FOUND;
		}

		$wd = $this->weightdataService->getAllByPersonId($id);
		foreach( $wd as $i ) {
			$this->weightdataService->delete($i->id);
		}

		$wd = $this->feelingdataService->getAllByPersonId($id);
		foreach( $wd as $i ) {
			$this->feelingdataService->delete($i->id);
		}

		$wd = $this->sleepdataService->getAllByPersonId($id);
		foreach( $wd as $i ) {
			$this->sleepdataService->delete($i->id);
		}

		$wd = $this->measurementdataService->getAllByPersonId($id);
		foreach( $wd as $i ) {
			$this->measurementdataService->delete($i->id);
		}

		$ad = $this->activitiesdataService->getAllByPersonId($id);
		foreach( $ad as $i ) {
			$this->activitiesdataService->delete($i->id);
		}

		return $this->personMapper->delete($person);
	}

	public function updatePerson($id, $key, $value) {
		if( !$this->permissionService->personData($id, $this->userId)) {
			return null;
		}

		try {
			$person = $this->personMapper->find($id, $this->userId);
		} catch(Exception $e) {
			return Http::STATUS_NOT_FOUND;
		}

		$method = 'set'.ucfirst($key);
		$person->{$method}($this->formatHelperService->typeCastByEntity($key, $value, $person));
		$this->personMapper->update($person);

		return $person;
	}

	public function getData($personId) {
		if( !$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}

		return [
			'lastWeight' => $this->weightdataService->getLastWeight($personId)
		];
	}

}
