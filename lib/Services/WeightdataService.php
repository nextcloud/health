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

use Exception;
use OCA\Health\Db\Weightdata;
use OCA\Health\Db\WeightdataMapper;
use OCA\Health\Services\FormatHelperService;
use OCP\AppFramework\Http;

class WeightdataService {

	protected $weightdataMapper;
	protected $userId;
	protected $formatHelperService;
	protected $permissionService;

	public function __construct($userId, WeightdataMapper $wdM, FormatHelperService $fhS, PermissionService $permissionService) {
		$this->userId = $userId;
		$this->weightdataMapper = $wdM;
		$this->formatHelperService = $fhS;
		$this->permissionService = $permissionService;
	}

	public function getAllByPersonId($personId) {
		if( !$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}
		return $this->weightdataMapper->findAll($personId);
	}

	public function getLastWeight($personId) {
		if( !$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}
		return $this->weightdataMapper->findLast($personId);
	}

	public function create($personId, $weight, $measurement, $bodyfat, $date, $waistSize, $hipSize, $musclePart, $comment) {
		if( !$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}
		$time = new \DateTime();
		try {
			$date = new \DateTime($date);
		} catch (Exception $e) {
			$date = new \DateTime();
		}
		$wd = new Weightdata();
		$wd->setDate($date->format('Y-m-d H:i:s'));
		/** @noinspection PhpUndefinedMethodInspection */
		$wd->setInsertTime($time->format('Y-m-d H:i:s'));
		/** @noinspection PhpUndefinedMethodInspection */
		$wd->setLastupdateTime($time->format('Y-m-d H:i:s'));
		/** @noinspection PhpUndefinedMethodInspection */
		$wd->setPersonId($personId);
		/** @noinspection PhpUndefinedMethodInspection */
		$wd->setWeight($weight);
		/** @noinspection PhpUndefinedMethodInspection */
		$wd->setMeasurement($measurement);
		/** @noinspection PhpUndefinedMethodInspection */
		$wd->setBodyfat($bodyfat);
		/** @noinspection PhpUndefinedMethodInspection */
		$wd->setWaistSize($this->formatHelperService->typeCastByEntity('waistSize', $waistSize, $wd));
		/** @noinspection PhpUndefinedMethodInspection */
		$wd->setHipSize($this->formatHelperService->typeCastByEntity('hipSize', $hipSize, $wd));
		/** @noinspection PhpUndefinedMethodInspection */
		$wd->setMusclePart($this->formatHelperService->typeCastByEntity('musclePart', $musclePart, $wd));
		/** @noinspection PhpUndefinedMethodInspection */
		$wd->setComment($comment);
		// error_log(print_r($wd, true));
		return $this->weightdataMapper->insert($wd);
	}

	public function delete($id) {
		if( !$this->permissionService->weightData($id, $this->userId)) {
			return null;
		}
		try {
			$wd = $this->weightdataMapper->find($id);
        } catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
		}
		return $this->weightdataMapper->delete($wd);
	}

	public function update($id, $date, $weight, $measurement, $bodyfat, $waistSize, $hipSize, $musclePart, $comment) {
		if( !$this->permissionService->weightData($id, $this->userId)) {
			return null;
		}
		try {
			$wd = $this->weightdataMapper->find($id);
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$wd->setDate($this->formatHelperService->convertDate($date));
			/** @noinspection PhpUndefinedMethodInspection */
			$wd->setWeight($this->formatHelperService->typeCastByEntity('weight', $weight, $wd));
			/** @noinspection PhpUndefinedMethodInspection */
			$wd->setMeasurement($this->formatHelperService->typeCastByEntity('measurement', $measurement, $wd));
			/** @noinspection PhpUndefinedMethodInspection */
			$wd->setBodyfat($this->formatHelperService->typeCastByEntity('bodyfat', $bodyfat, $wd));
			/** @noinspection PhpUndefinedMethodInspection */
			$wd->setWaistSize($this->formatHelperService->typeCastByEntity('waistSize', $waistSize, $wd));
			/** @noinspection PhpUndefinedMethodInspection */
			$wd->setHipSize($this->formatHelperService->typeCastByEntity('hipSize', $hipSize, $wd));
			/** @noinspection PhpUndefinedMethodInspection */
			$wd->setMusclePart($this->formatHelperService->typeCastByEntity('musclePart', $musclePart, $wd));
			/** @noinspection PhpUndefinedMethodInspection */
			$wd->setComment($comment);
        } catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
		}
		return $this->weightdataMapper->update($wd);
	}
}
