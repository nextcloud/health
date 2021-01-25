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

namespace OCA\Health\Controller;

use OCA\Health\Services\SleepdataService;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;

class SleepdataController extends Controller {

	protected $userId;
	protected $sleepdataService;

	public function __construct($appName, IRequest $request, SleepdataService $mS, $userId) {
		parent::__construct($appName, $request);
		$this->sleepdataService = $mS;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $personId
	 * @return DataResponse
	 */
	public function findByPerson(int $personId): DataResponse
	{
        return new DataResponse($this->sleepdataService->getAllByPersonId($personId));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $personId
	 * @param string|null $asleep
	 * @param string|null $wakeup
	 * @param int|null $quality
	 * @param int|null $countedWakeups
	 * @param float|null $durationWakeups
	 * @param string $comment
	 * @return DataResponse
	 */
	public function create(int $personId, string $asleep = null, string $wakeup = null, int $quality = null, int $countedWakeups = null, float $durationWakeups = null, string $comment = ''): DataResponse
	{
		return new DataResponse($this->sleepdataService->create($personId, $asleep, $wakeup, $quality, $countedWakeups, $durationWakeups, $comment));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return DataResponse
	 */
	public function delete(int $id): DataResponse
	{
		return new DataResponse($this->sleepdataService->delete($id));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param string|null $asleep
	 * @param string|null $wakeup
	 * @param int|null $quality
	 * @param int|null $countedWakeups
	 * @param float|null $durationWakeups
	 * @param string $comment
	 * @return DataResponse
	 */
	public function update(int $id, string $asleep = null, string $wakeup = null, int $quality = null, int $countedWakeups = null, float $durationWakeups = null, string $comment = ''): DataResponse
	{
		return new DataResponse($this->sleepdataService->update($id, $asleep, $wakeup, $quality, $countedWakeups, $durationWakeups, $comment));
	}
}
