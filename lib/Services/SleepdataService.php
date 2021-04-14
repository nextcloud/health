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
use OCA\Health\Db\Sleepdata;
use OCA\Health\Db\SleepdataMapper;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Http;

class SleepdataService {

	protected $sleepdataMapper;
	protected $userId;
	protected $formatHelperService;
	protected $permissionService;

	public function __construct($userId, SleepdataMapper $mdM, FormatHelperService $fhS, PermissionService $permissionService) {
		$this->userId = $userId;
		$this->sleepdataMapper = $mdM;
		$this->formatHelperService = $fhS;
		$this->permissionService = $permissionService;
	}

	public function getAllByPersonId($personId): ?array
	{
		if( !$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}
		return $this->sleepdataMapper->findAll($personId);
	}

	/** @noinspection PhpUndefinedMethodInspection
	 * @noinspection DuplicatedCode
	 * @param $personId
	 * @param $asleep
	 * @param $wakeup
	 * @param $quality
	 * @param $countedWakeups
	 * @param $durationWakeups
	 * @param $comment
	 * @return Entity|null
	 */
	public function create($personId, $asleep, $wakeup, $quality, $countedWakeups, $durationWakeups, $comment): ?Entity
	{
		if( !$this->permissionService->personData($personId, $this->userId)) {
			return null;
		}
		try {
			$date = new DateTime($asleep);
		} catch (Exception $e) {
			$date = new DateTime();
		}
		try {
			$date2 = new DateTime($wakeup);
		} catch (Exception $e) {
			$date2 = new DateTime();
		}
		$d = new Sleepdata();
		$d->setPersonId($personId);
		$d->setAsleep($date->format('Y-m-d H:i:s'));
		$d->setWakeup($date2->format('Y-m-d H:i:s'));
		$d->setQuality($quality);
		$d->setCountedWakeups($countedWakeups);
		$d->setDurationWakeups($durationWakeups);
		$d->setComment($comment);
		return $this->sleepdataMapper->insert($d);
	}

	public function delete($id) {
		if( !$this->permissionService->sleepData($id, $this->userId)) {
			return null;
		}
		try {
			$md = $this->sleepdataMapper->find($id);
			return $this->sleepdataMapper->delete($md);
        } catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
		}
	}

	/** @noinspection DuplicatedCode
	 * @noinspection PhpUndefinedMethodInspection
	 * @param $id
	 * @param $asleep
	 * @param $wakeup
	 * @param $quality
	 * @param $countedWakeups
	 * @param $durationWakeups
	 * @param $comment
	 * @return int|Entity|null
	 */
	public function update($id, $asleep, $wakeup, $quality, $countedWakeups, $durationWakeups, $comment): ?Entity
	{
		if( !$this->permissionService->sleepData($id, $this->userId)) {
			return null;
		}
		try {
			$d = $this->sleepdataMapper->find($id);
			try {
				$date = new DateTime($asleep);
			} catch (Exception $e) {
				$date = new DateTime();
			}
			try {
				$date2 = new DateTime($wakeup);
			} catch (Exception $e) {
				$date2 = new DateTime();
			}
			$d->setAsleep($date->format('Y-m-d H:i:s'));
			$d->setWakeup($date2->format('Y-m-d H:i:s'));
			$d->setQuality($quality);
			$d->setCountedWakeups($countedWakeups);
			$d->setDurationWakeups($durationWakeups);
			$d->setComment($comment);
		} catch(Exception $e) {
             return Http::STATUS_NOT_FOUND;
		}
		return $this->sleepdataMapper->update($d);
	}
}
