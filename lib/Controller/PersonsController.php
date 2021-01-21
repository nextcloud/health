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

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCA\Health\Services\PersonsService;

class PersonsController extends Controller {

	protected $userId;
	protected $personsService;

	public function __construct($appName, IRequest $request, $userId, PersonsService $personsService) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->personsService = $personsService;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */
	public function index(): DataResponse
	{
        return new DataResponse($this->personsService->getAllPersons());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $personId
	 *
	 * @return DataResponse
	 */
	public function data(int $personId): DataResponse
	{
        return new DataResponse($this->personsService->getData($personId));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function create(string $name): DataResponse
	{
		return new DataResponse($this->personsService->createPerson($name));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @return DataResponse
	 */
	public function destroy(int $id): DataResponse
	{
		return new DataResponse($this->personsService->deletePerson($id));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param string $key
	 * @param string $value
	 *
	 * @return DataResponse
	 */
	public function update(int $id, string $key, string $value): DataResponse
	{
		return new DataResponse($this->personsService->updatePerson($id, $key, $value));
	}
}
