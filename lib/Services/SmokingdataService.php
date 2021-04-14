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
use OCA\Health\Db\Smokingdata;
use OCA\Health\Db\SmokingdataMapper;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Http;

class SmokingdataService {

	protected $smokingdataMapper;
	protected $userId;
	protected $formatHelperService;
	protected $permissionService;

	public function __construct($userId, SmokingdataMapper $mdM, FormatHelperService $fhS, PermissionService $permissionService) {
		$this->userId = $userId;
		$this->smokingdataMapper = $mdM;
		$this->formatHelperService = $fhS;
		$this->permissionService = $permissionService;
	}

	public function getAllByPersonId($personId): ?array
	{
		if( !$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}
		return $this->smokingdataMapper->findAll($personId);
	}

	/** @noinspection PhpUndefinedMethodInspection
	 * @noinspection DuplicatedCode
	 * @param $personId
	 * @param $date
	 * @param $cigarettes
	 * @param $desireLevel
	 * @param $comment
	 * @return Entity|null
	 */
	public function create($personId, $date, $cigarettes, $desireLevel, $comment): ?Entity
	{
		if( !$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}
		try {
			$date = new DateTime($date);
		} catch (Exception $e) {
			$date = new DateTime();
		}
		$d = new Smokingdata();
		$d->setPersonId($personId);
		$d->setDate($date->format('Y-m-d H:i:s'));
		$d->setCigarettes($cigarettes);
		$d->setDesireLevel($desireLevel);
		$d->setComment($comment);
		return $this->smokingdataMapper->insert($d);
	}

	public function delete($id) {
		if( !$this->permissionService->smokingData($id, $this->userId)) {
			return null;
		}
		try {
			$md = $this->smokingdataMapper->find($id);
			return $this->smokingdataMapper->delete($md);
        } catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
		}
	}

	/** @noinspection DuplicatedCode
	 * @noinspection PhpUndefinedMethodInspection
	 * @param $id
	 * @param $date
	 * @param $cigarettes
	 * @param $desireLevel
	 * @param $comment
	 * @return int|Entity|null
	 */
	public function update($id, $date, $cigarettes, $desireLevel, $comment): ?Entity
	{
		if( !$this->permissionService->smokingData($id, $this->userId)) {
			return null;
		}
		try {
			$d = $this->smokingdataMapper->find($id);
			try {
				$date = new DateTime($date);
			} catch (Exception $e) {
				$date = new DateTime();
			}
			$d->setDate($date->format('Y-m-d H:i:s'));
			$d->setCigarettes($cigarettes);
			$d->setDesireLevel($desireLevel);
			$d->setComment($comment);
		} catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
		}
		return $this->smokingdataMapper->update($d);
	}
}
