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
use OCA\Health\Db\PersonMapper;
use Psr\Log\LoggerInterface;

class PermissionService {

	protected $userId;
	protected $personMapper;
	protected $logger;

	public function __construct($userId, PersonMapper $pM, LoggerInterface $logger) {
		$this->userId = $userId;
		$this->personMapper = $pM;
		$this->logger = $logger;
	}

	public function personData($destinationPersonId, $sourceUserId): bool
	{
		try {
            if($this->personMapper->find($destinationPersonId, $sourceUserId) !== null) {
            	return true;
            }
        } catch(Exception $e) {
			$context = [
				'personId' => $destinationPersonId,
				'userId' => $sourceUserId
			];
			$this->logger->error('User tries to fetch data from personId that is not permitted.', $context);
        }
        return false;
	}

	public function weightData($id, $userId): bool
	{
		// TODO
		return true;
	}

	public function measurementData($id, $userId): bool
	{
		// TODO
		return true;
	}

	public function feelingData($id, $userId): bool
	{
		// TODO
		return true;
	}

	public function sleepData($id, $userId): bool
	{
		// TODO
		return true;
	}

	public function smokingData($id, $userId): bool
	{
		// TODO
		return true;
	}

	public function activitiesData($id, $userId): bool
	{
		// TODO
		return true;
	}

}
