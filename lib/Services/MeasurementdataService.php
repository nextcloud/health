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
use OCA\Health\Db\Measurementdata;
use OCA\Health\Db\MeasurementdataMapper;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Http;

class MeasurementdataService {

	protected $measurementdataMapper;
	protected $userId;
	protected $formatHelperService;
	protected $permissionService;

	public function __construct($userId, MeasurementdataMapper $mdM, FormatHelperService $fhS, PermissionService $permissionService) {
		$this->userId = $userId;
		$this->measurementdataMapper = $mdM;
		$this->formatHelperService = $fhS;
		$this->permissionService = $permissionService;
	}

	public function getAllByPersonId($personId): ?array
	{
		if( !$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}
		return $this->measurementdataMapper->findAll($personId);
	}

	/** @noinspection PhpUndefinedMethodInspection
	 * @noinspection DuplicatedCode
	 * @param $personId
	 * @param $datetime
	 * @param $temperature
	 * @param $heartRate
	 * @param $bloodPressureSystolic
	 * @param $bloodPressureDiastolic
	 * @param $bloodSugar
	 * @param $oxygenSaturation
	 * @param $defecation
	 * @param $appetite
	 * @param $allergies
	 * @param $cigarettes
	 * @param $alcohol
	 * @param $comment
	 * @return Entity|null
	 */
	public function create($personId, $datetime, $temperature, $heartRate, $bloodPressureSystolic, $bloodPressureDiastolic, $bloodSugar, $oxygenSaturation, $defecation, $appetite, $allergies, $cigarettes, $alcohol, $comment): ?Entity
	{
		if( !$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}
		try {
			$date = new DateTime($datetime);
		} catch (Exception $e) {
			$date = new DateTime();
		}
		$d = new Measurementdata();
		$d->setPersonId($personId);
		$d->setDatetime($date->format('Y-m-d H:i:s'));
		$d->setTemperature($temperature);
		$d->setHeartRate($heartRate);
		$d->setBloodPressureS($bloodPressureSystolic);
		$d->setBloodPressureD($bloodPressureDiastolic);
		$d->setBloodSugar($bloodSugar);
		$d->setOxygenSat($oxygenSaturation);
		$d->setDefecation($defecation);
		$d->setAppetite($appetite);
		$d->setAllergies($allergies);
		$d->setCigarettes($cigarettes);
		$d->setAlcohol($alcohol);
		$d->setComment($comment);
		// error_log(print_r($wd, true));
		return $this->measurementdataMapper->insert($d);
	}

	public function delete($id) {
		if( !$this->permissionService->measurementData($id, $this->userId)) {
			return null;
		}
		try {
			$md = $this->measurementdataMapper->find($id);
			return $this->measurementdataMapper->delete($md);
        } catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
		}
	}

	/** @noinspection DuplicatedCode
	 * @noinspection PhpUndefinedMethodInspection
	 * @param $id
	 * @param $datetime
	 * @param $temperature
	 * @param $heartRate
	 * @param $bloodPressureSystolic
	 * @param $bloodPressureDiastolic
	 * @param $bloodSugar
	 * @param $oxygenSaturation
	 * @param $defecation
	 * @param $appetite
	 * @param $allergies
	 * @param $cigarettes
	 * @param $alcohol
	 * @param $comment
	 * @return int|Entity|null
	 */
	public function update($id, $datetime, $temperature, $heartRate, $bloodPressureSystolic, $bloodPressureDiastolic, $bloodSugar, $oxygenSaturation, $defecation, $appetite, $allergies, $cigarettes, $alcohol, $comment): ?Entity
	{
		if( !$this->permissionService->measurementData($id, $this->userId)) {
			return null;
		}
		try {
			$d = $this->measurementdataMapper->find($id);
			try {
				$date = new DateTime($datetime);
			} catch (Exception $e) {
				$date = new DateTime();
			}
			$d->setDatetime($date->format('Y-m-d H:i:s'));
			$d->setTemperature($temperature);
			$d->setHeartRate($heartRate);
			$d->setBloodPressureS($bloodPressureSystolic);
			$d->setBloodPressureD($bloodPressureDiastolic);
			$d->setBloodSugar($bloodSugar);
			$d->setOxygenSat($oxygenSaturation);
			$d->setDefecation($defecation);
			$d->setAppetite($appetite);
			$d->setAllergies($allergies);
			$d->setCigarettes($cigarettes);
			$d->setAlcohol($alcohol);
			$d->setComment($comment);
        } catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
		}
		return $this->measurementdataMapper->update($d);
	}
}
