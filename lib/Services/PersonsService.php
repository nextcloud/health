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
use OCP\IUser;
use OCP\IUserManager;

class PersonsService {

	protected PersonMapper $personMapper;
	protected WeightdataService $weightdataService;
	protected String $userId;
	protected FormatHelperService $formatHelperService;
	protected PermissionService $permissionService;
	protected IUserManager $userManager;

	public function __construct(PersonMapper $pM, $userId, WeightdataService $wdS, FormatHelperService $fhS, PermissionService $permissionService, IUserManager $userManager) {
		$this->personMapper = $pM;
		$this->userId = $userId;
		$this->weightdataService = $wdS;
		$this->formatHelperService = $fhS;
		$this->permissionService = $permissionService;
		$this->userManager = $userManager;
	}

	public function getAllPersons() {
		$persons = $this->personMapper->findAll($this->userId);
		if( count($persons) === 0) {
			$user = $this->userManager->get($this->userId);
			$this->createPerson( $user->getDisplayName() );
			$persons = $this->personMapper->findAll($this->userId);
		}
		return $persons;
	}

	public function createPerson($name) {
		$time = new \DateTime();
		$p = new Person();
		/** @noinspection PhpUndefinedMethodInspection */
		$p->setInsertTime($time->format('Y-m-d H:i:s'));
		/** @noinspection PhpUndefinedMethodInspection */
		$p->setLastupdateTime($time->format('Y-m-d H:i:s'));
		/** @noinspection PhpUndefinedMethodInspection */
		$p->setUserId($this->userId);
		/** @noinspection PhpUndefinedMethodInspection */
		$p->setName($name);
		/** @noinspection PhpUndefinedMethodInspection */
		$p->setEnabledModuleWeight(true);
		/** @noinspection PhpUndefinedMethodInspection */
		$p->setEnabledModuleBreaks(false);
		/** @noinspection PhpUndefinedMethodInspection */
		$p->setEnabledModuleFeeling(false);
		/** @noinspection PhpUndefinedMethodInspection */
		$p->setEnabledModuleMedicine(false);
		/** @noinspection PhpUndefinedMethodInspection */
		$p->setEnabledModuleActivities(false);
		/** @noinspection PhpUndefinedMethodInspection */
		$p->setWeightUnit('kg');
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
         $this->personMapper->delete($person);
         return new $person;
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
         $person->{$method}($this->formatHelperService->typeCast($key, $value));
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
