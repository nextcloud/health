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

use OCA\Health\Db\Weightdata;
use OCA\Health\Db\WeightdataMapper;
use OCA\Health\Services\FormatHelperService;
use OCA\Health\Services\PermissionService;

class WeightdataService {

	protected $weightdataMapper;
	protected $userId;
	protected $formatHelperService;
	protected $permissionService;

	public function __construct($userId, WeightdataMapper $wdM, FormatHelperService $fhS, PermissionService $pmS) {
		$this->userId = $userId;
		$this->weightdataMapper = $wdM;
		$this->formatHelperService = $fhS;
		$this->permissionService = $pmS;
	}

	public function getAllByPersonId($personId) {
		if(!$this->permissionService->personData($this->userId, $personId)) {
			return null;
		}
		return $this->weightdataMapper->findAll($personId);
	}

	public function getLastWeight($personId) {
		return $this->weightdataMapper->findLast($personId);		
	}

	public function create($personId, $weight, $measurement, $bodyfat, $date) {
		$time = new \DateTime();
		$date = new \DateTime($date);
		$wd = new Weightdata();
		$wd->setDate($date->format('Y-m-d H:i:s'));
		$wd->setInsertTime($time->format('Y-m-d H:i:s'));
		$wd->setLastupdateTime($time->format('Y-m-d H:i:s'));
		$wd->setPersonId($personId);
		$wd->setWeight($weight);
		$wd->setMeasurement($measurement);
		$wd->setBodyfat($bodyfat);
		error_log(print_r($wd, true));
		return $this->weightdataMapper->insert($wd);
	}

	public function delete($id) {
		try {
			$wd = $this->weightdataMapper->find($id);
        } catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
		}
		return $this->weightdataMapper->delete($wd);
	}

	public function update($id, $date, $weight, $measurement, $bodyfat) {
		try {
			$wd = $this->weightdataMapper->find($id);
			$wd->setDate($this->formatHelperService->typeCast('date', $date));
			$wd->setWeight($this->formatHelperService->typeCast('weight', $weight));
			$wd->setMeasurement($this->formatHelperService->typeCast('measurement', $measurement));
			$wd->setBodyfat($this->formatHelperService->typeCast('bodyfat', $bodyfat));
        } catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
		}
		return $this->weightdataMapper->update($wd);
	}
}
