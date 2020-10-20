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

namespace OCA\Health\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;
use OCP\Util;
use OCP\AppFramework\Http\DataResponse;
use OCA\Health\Services\PersonsService;

class PersonsController extends Controller {

	protected $appName;
	protected $userId;
	protected $personsService;
	protected $request;

	public function __construct($appName, IRequest $request, PersonsService $pS, $userId) {
		parent::__construct($appName, $request);
		$this->appName = $appName;
		$this->personsService = $pS;
		$this->userId = $userId;
		$this->request = $request;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
        return new DataResponse($this->personsService->getAllPersons());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * 
	 * @param int $personId
	 */
	public function data($personId) {
        return new DataResponse($this->personsService->getData($personId));
	}	

	/**
	 * @NoAdminRequired
	 *
     * @param string $name
	 */
	public function create($name) {
		return new DataResponse($this->personsService->createPerson($name));
	}

	/**
	 * @NoAdminRequired
	 *
     * @param int $id
	 */
	public function destroy(int $id) {
		return new DataResponse($this->personsService->deletePerson($id));
	}

	/**
	 * @NoAdminRequired
	 *
     * @param int $id
     * @param string $key
     * @param string $value
	 */
	public function update(int $id, string $key, string $value) {
		return new DataResponse($this->personsService->updatePerson($id, $key, $value));
	}
}
