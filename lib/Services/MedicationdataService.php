<?php

declare(strict_types=1);
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

namespace OCA\Health\Services;

use Exception;
use OCA\Health\Db\Medicationdata;
use OCA\Health\Db\MedicationdataMapper;
use OCA\Health\Db\MedicationPlan;
use OCA\Health\Db\MedicationPlanMapper;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Http;

class MedicationdataService {

	protected $medicationdataMapper;
	protected $medicationPlanMapper;
	protected $userId;
	protected $formatHelperService;
	protected $permissionService;

	public function __construct($userId, MedicationdataMapper $mdM, MedicationPlanMapper $mpM, FormatHelperService $fhS, PermissionService $permissionService) {
		$this->userId = $userId;
		$this->medicationdataMapper = $mdM;
		$this->medicationPlanMapper = $mpM;
		$this->formatHelperService = $fhS;
		$this->permissionService = $permissionService;
	}

	public function getAllPlansByPersonId($personId): ?array {
		if(!$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}
		return $this->medicationPlanMapper->findAll($personId);
	}

	public function getAllMedicationByPlan($planId): ?array {
		if(!$this->permissionService->medicationData($planId, $this->userId)) {
			return null;
		}
		return $this->medicationdataMapper->findAll($planId);
	}

	public function createPlan($personId, $date, $comment, $takeOver): ?Entity {
		if(!$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}

		// check if there is a previous plan from where the
		// medication can be copied
		$lastPlan = $this->medicationPlanMapper->findLast($personId);

		$d = new MedicationPlan();
		$d->setPersonId($personId);
		$d->setDate($date);
		$d->setComment($comment);
		$newPlan = $this->medicationPlanMapper->insert($d);

		if ($lastPlan != null && $takeOver == true) {
			$medication = $this->medicationdataMapper->findAll($lastPlan->id);
			foreach($medication as $med) {
				$d = new Medicationdata();
				$d->setPlanId($newPlan->id);
				$d->setName($med->getName());
				$d->setIdentifier($med->getIdentifier());
				$d->setMorning($med->getMorning());
				$d->setNoon($med->getNoon());
				$d->setEvening($med->getEvening());
				$d->setNight($med->getNight());
				$d->setComment($med->getComment());
				$this->medicationdataMapper->insert($d);
			}
		}

		return $newPlan;
	}

	public function createMedication($planId, $name, $identifier, $morning, $noon, $evening, $night, $comment): ?Entity {
		if(!$this->permissionService->medicationData($planId, $this->userId)) {
			return null;
		}
		$d = new Medicationdata();
		$d->setPlanId($planId);
		$d->setName($name);
		$d->setIdentifier($identifier);
		$d->setMorning($morning);
		$d->setNoon($noon);
		$d->setEvening($evening);
		$d->setNight($night);
		$d->setComment($comment);
		return $this->medicationdataMapper->insert($d);
	}

	public function deleteMedication($id) {
		if(!$this->permissionService->medicationData($id, $this->userId)) {
			return null;
		}
		try {
			$md = $this->medicationdataMapper->find($id);
			return $this->medicationdataMapper->delete($md);
		} catch(Exception $e) {
			return Http::STATUS_NOT_FOUND;
		}
	}

	public function updateMedication($id, $name, $identifier, $morning, $noon, $evening, $night, $comment): ?Entity {
		if(!$this->permissionService->medicationData($id, $this->userId)) {
			return null;
		}
		try {
			$d = $this->medicationdataMapper->find($id);
			$d->setName($name);
			$d->setIdentifier($identifier);
			$d->setMorning($morning);
			$d->setNoon($noon);
			$d->setEvening($evening);
			$d->setNight($night);
			$d->setComment($comment);
		} catch(Exception $e) {
			return Http::STATUS_NOT_FOUND;
		}
		return $this->medicationdataMapper->update($d);
	}

	public function deletePlan($id) {
		if(!$this->permissionService->medicationData($id, $this->userId)) {
			return null;
		}

		try {
			$medication = $this->medicationdataMapper->findAll($id);
			foreach($medication as $med) {
				$this->medicationdataMapper->delete($med);
			}
		} catch(Exception $e) {
			return Http::STATUS_INTERNAL_SERVER_ERROR;
		}

		try {
			$plan = $this->medicationPlanMapper->find($id);
			return $this->medicationPlanMapper->delete($plan);
		} catch(Exception $e) {
			return Http::STATUS_NOT_FOUND;
		}
	}

	public function updatePlan($id, $date, $comment): ?Entity {
		if(!$this->permissionService->medicationData($id, $this->userId)) {
			return null;
		}
		try {
			$d = $this->medicationPlanMapper->find($id);
			$d->setDate($date);
			$d->setComment($comment);
		} catch(Exception $e) {
			return Http::STATUS_NOT_FOUND;
		}
		return $this->medicationPlanMapper->update($d);
	}
}
