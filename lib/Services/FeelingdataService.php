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

use DateTime;
use Exception;
use OCA\Health\Db\Feelingdata;
use OCA\Health\Db\FeelingdataMapper;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Http;

class FeelingdataService {

	protected $feelingdataMapper;
	protected $userId;
	protected $formatHelperService;
	protected $permissionService;

	public function __construct($userId, FeelingdataMapper $mdM, FormatHelperService $fhS, PermissionService $permissionService) {
		$this->userId = $userId;
		$this->feelingdataMapper = $mdM;
		$this->formatHelperService = $fhS;
		$this->permissionService = $permissionService;
	}

	public function getAllByPersonId($personId): ?array
	{
		if( !$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}
		return $this->feelingdataMapper->findAll($personId);
	}

	/** @noinspection PhpUndefinedMethodInspection
	 * @noinspection DuplicatedCode
	 * @param $personId
	 * @param $datetime
	 * @param $mood
	 * @param $sadnessLevel
	 * @param $symptoms
	 * @param $attacks
	 * @param $medication
	 * @param $pain
	 * @param $energy
	 * @param $comment
	 * @return Entity|null
	 */
	public function create($personId, $datetime, $mood, $sadnessLevel, $symptoms, $attacks, $medication, $pain, $energy, $comment): ?Entity
	{
		if( !$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}
		try {
			$date = new DateTime($datetime);
		} catch (Exception $e) {
			$date = new DateTime();
		}
		$d = new Feelingdata();
		$d->setPersonId($personId);
		$d->setDatetime($date->format('Y-m-d H:i:s'));
		$d->setMood($mood);
		$d->setSadnessLevel($sadnessLevel);
		$d->setSymptoms($symptoms);
		$d->setAttacks($attacks);
		$d->setMedication($medication);
		$d->setPain($pain);
		$d->setEnergy($energy);
		$d->setComment($comment);
		return $this->feelingdataMapper->insert($d);
	}

	public function delete($id) {
		if( !$this->permissionService->feelingData($id, $this->userId)) {
			return null;
		}
		try {
			$md = $this->feelingdataMapper->find($id);
			return $this->feelingdataMapper->delete($md);
        } catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
		}
	}

	/** @noinspection DuplicatedCode
	 * @noinspection PhpUndefinedMethodInspection
	 * @param $id
	 * @param $datetime
	 * @param $mood
	 * @param $sadnessLevel
	 * @param $symptoms
	 * @param $attacks
	 * @param $medication
	 * @param $pain
	 * @param $energy
	 * @param $comment
	 * @return int|Entity|null
	 */
	public function update($id, $datetime, $mood, $sadnessLevel, $symptoms, $attacks, $medication, $pain, $energy, $comment): ?Entity
	{
		if( !$this->permissionService->feelingData($id, $this->userId)) {
			return null;
		}
		try {
			$d = $this->feelingdataMapper->find($id);
			try {
				$date = new DateTime($datetime);
			} catch (Exception $e) {
				$date = new DateTime();
			}
			$d->setDatetime($date->format('Y-m-d H:i:s'));
			$d->setMood($mood);
			$d->setSadnessLevel($sadnessLevel);
			$d->setSymptoms($symptoms);
			$d->setAttacks($attacks);
			$d->setMedication($medication);
			$d->setPain($pain);
			$d->setEnergy($energy);
			$d->setComment($comment);
		} catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
		}
		return $this->feelingdataMapper->update($d);
	}
}
