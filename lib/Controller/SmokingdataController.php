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

use OCA\Health\Services\SmokingdataService;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;

class SmokingdataController extends Controller {

	protected $userId;
	protected $smokingdataService;

	public function __construct($appName, IRequest $request, SmokingdataService $mS, $userId) {
		parent::__construct($appName, $request);
		$this->smokingdataService = $mS;
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
        return new DataResponse($this->smokingdataService->getAllByPersonId($personId));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $personId
	 * @param string $date
	 * @param int|null $cigarettes
	 * @param int|null $desireLevel
	 * @param string $comment
	 * @return DataResponse
	 */
	public function create(int $personId, string $date, int $cigarettes = null, int $desireLevel = null, string $comment = ''): DataResponse
	{
		return new DataResponse($this->smokingdataService->create($personId, $date, $cigarettes, $desireLevel, $comment));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return DataResponse
	 */
	public function delete(int $id): DataResponse
	{
		return new DataResponse($this->smokingdataService->delete($id));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param string $date
	 * @param int|null $cigarettes
	 * @param int|null $desireLevel
	 * @param string $comment
	 * @return DataResponse
	 */
	public function update(int $id, string $date, int $cigarettes = null, int $desireLevel = null, string $comment = ''): DataResponse
	{
		return new DataResponse($this->smokingdataService->update($id, $date, $cigarettes, $desireLevel, $comment));
	}
}
