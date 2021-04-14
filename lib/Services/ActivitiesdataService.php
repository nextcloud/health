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
use OCA\Health\Db\Activitiesdata;
use OCA\Health\Db\ActivitiesdataMapper;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Http;

class ActivitiesdataService {

	protected $activitiesdataMapper;
	protected $userId;
	protected $formatHelperService;
	protected $permissionService;

	public function __construct($userId, ActivitiesdataMapper $adM, FormatHelperService $fhS, PermissionService $permissionService) {
		$this->userId = $userId;
		$this->activitiesdataMapper = $adM;
		$this->formatHelperService = $fhS;
		$this->permissionService = $permissionService;
	}

	public function getAllByPersonId($personId): ?array
	{
		if( !$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}
		return $this->activitiesdataMapper->findAll($personId);
	}

	/** @noinspection PhpUndefinedMethodInspection
	 * @noinspection DuplicatedCode
	 * @param $personId
	 * @param $datetime
	 * @param $calories
	 * @param $duration
	 * @param $category
	 * @param $feeling
	 * @param $intensity
	 * @param $distance
	 * @param $comment
	 * @return Entity|null
	 */
	public function create($personId, $datetime, $calories, $duration, $category, $feeling, $intensity, $distance, $comment): ?Entity
	{
		if( !$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}
		try {
			$date = new DateTime($datetime);
		} catch (Exception $e) {
			$date = new DateTime();
		}
		$d = new Activitiesdata();
		$d->setPersonId($personId);
		$d->setDatetime($date->format('Y-m-d H:i:s'));
		$d->setCalories($calories);
		$d->setDuration($duration);
		$d->setCategory($category);
		$d->setFeeling($feeling);
		$d->setIntensity($intensity);
		$d->setDistance($distance);
		$d->setComment($comment);
		return $this->activitiesdataMapper->insert($d);
	}

	public function delete($id) {
		if( !$this->permissionService->smokingData($id, $this->userId)) {
			return null;
		}
		try {
			$md = $this->activitiesdataMapper->find($id);
			return $this->activitiesdataMapper->delete($md);
        } catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
		}
	}

	/** @noinspection DuplicatedCode
	 * @noinspection PhpUndefinedMethodInspection
	 * @param $id
	 * @param $datetime
	 * @param $calories
	 * @param $duration
	 * @param $category
	 * @param $feeling
	 * @param $intensity
	 * @param $distance
	 * @param $comment
	 * @return int|Entity|null
	 */
	public function update($id, $datetime, $calories, $duration, $category, $feeling, $intensity, $distance, $comment): ?Entity
	{
		if( !$this->permissionService->smokingData($id, $this->userId)) {
			return null;
		}
		try {
			$d = $this->activitiesdataMapper->find($id);
			try {
				$date = new DateTime($datetime);
			} catch (Exception $e) {
				$date = new DateTime();
			}
			$d->setDatetime($date->format('Y-m-d H:i:s'));
			$d->setCalories($calories);
			$d->setDuration($duration);
			$d->setCategory($category);
			$d->setFeeling($feeling);
			$d->setIntensity($intensity);
			$d->setDistance($distance);
			$d->setComment($comment);
		} catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
		}
		return $this->activitiesdataMapper->update($d);
	}
}
