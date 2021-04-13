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

use OCA\Health\Services\ActivitiesdataService;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;

class ActivitiesdataController extends Controller {

	protected $userId;
	protected $activitiesdataService;

	public function __construct($appName, IRequest $request, ActivitiesdataService $aS, $userId) {
		parent::__construct($appName, $request);
		$this->activitiesdataService = $aS;
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
        return new DataResponse($this->activitiesdataService->getAllByPersonId($personId));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $personId
	 * @param string $datetime
	 * @param int|null $calories
	 * @param float|null $duration
	 * @param int|null $category
	 * @param int|null $feeling
	 * @param int|null $intensity
	 * @param float|null $distance
	 * @param string $comment
	 * @return DataResponse
	 */
	public function create(int $personId, string $datetime, int $calories = null, float $duration = null, int $category = null, int $feeling = null, int $intensity = null, float $distance = null, string $comment = ''): DataResponse
	{
		return new DataResponse($this->activitiesdataService->create($personId, $datetime, $calories, $duration, $category, $feeling, $intensity, $distance, $comment));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return DataResponse
	 */
	public function delete(int $id): DataResponse
	{
		return new DataResponse($this->activitiesdataService->delete($id));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param string $datetime
	 * @param int|null $calories
	 * @param float|null $duration
	 * @param int|null $category
	 * @param int|null $feeling
	 * @param int|null $intensity
	 * @param float|null $distance
	 * @param string $comment
	 * @return DataResponse
	 */
	public function update(int $id, string $datetime, int $calories = null, float $duration = null, int $category = null, int $feeling = null, int $intensity = null, float $distance = null, string $comment = ''): DataResponse
	{
		return new DataResponse($this->activitiesdataService->update($id, $datetime, $calories, $duration, $category, $feeling, $intensity, $distance, $comment));
	}
}
