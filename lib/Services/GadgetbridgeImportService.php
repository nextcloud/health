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

use OC\Files\Node\File;
use OC\User\NoUserException;
use OCA\Health\Db\GadgetbridgeDevices;
use OCA\Health\Db\GadgetbridgeDevicesMapper;
use OCA\Health\Db\GadgetbridgeSettingsMapper;
use OCA\Health\Exceptions\ParameterInputException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\Exception;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

class GadgetbridgeImportService {

	protected $userId;
	protected $personId;
	protected $settingsMapper;
	protected $devicesMapper;
	protected $sqlConnection;
	protected $sqlitePath;
	protected $logger;
	protected $l;
	/** @var IRootFolder */
	protected $rootFolder;
	/** @var PDO */
	private $connection;
	/** @var File */
	private $sourceFileNode;

	public function __construct($userId,
								GadgetbridgeSettingsMapper $gadgetbridgeSettingsMapper,
								LoggerInterface $logger,
								IL10N $l,
								IRootFolder $folder,
								GadgetbridgeDevicesMapper $gadgetbridgeDevicesMapper
	) {
		$this->userId = $userId;
		$this->settingsMapper = $gadgetbridgeSettingsMapper;
		$this->logger = $logger;
		$this->l = $l;
		$this->rootFolder = $folder;
		$this->devicesMapper = $gadgetbridgeDevicesMapper;
	}

	public function isSqliteAvailable(): bool
	{
		try {
			$pdo = new PDO( 'sqlite::memory:');
			$return =  !!$pdo;
		} catch (PDOException | Exception $e) {
			$return = false;
		}
		if(!$return) {
			$this->logger->warning('No sqlite support. Needed to import sqlite db from gadgetbridge.');
		}
		return $return;
	}

	private function isSourcePathValid($path): bool
	{
		try {
			$userFolder = $this->rootFolder->getUserFolder($this->userId);
			try {
				$sourceNode = $userFolder->get($path);
				if($sourceNode instanceof File) {
					$this->sourceFileNode = $sourceNode;
					return true;
				}
			} catch (NotFoundException $e) {
				$this->logger->error('Try to load user folder, but not found.');
			}
		} catch (NotPermittedException $e) {
			$this->logger->error('Try to load user folder, but not permitted.');
		} catch (NoUserException $e) {
			$this->logger->error('Try to load user folder, but no user in context.');
		}
		return false;
	}

	private function loadConnection(): bool
	{
		if(!$this->sourceFileNode) {
			$this->logger->error('missing sourceFileNode to loadConnection');
			return false;
		}
		$storage = $this->sourceFileNode->getStorage();
		$tmpPath = $storage->getLocalFile($this->sourceFileNode->getInternalPath());
		return !!$this->connection = new PDO('sqlite:' . $tmpPath);
	}

	public function importFromSqlite($personId, $userId = null): array
	{
		$this->personId = $personId;
		$errors = [];
		$messages = [];

		if(!$userId) {
			$userId = $this->userId;
		}

		try {
			/** @noinspection PhpUndefinedMethodInspection */
			$sourcePath = $this->settingsMapper->findBy($userId, $personId)
				->getSqliteSourcePath();
		} catch (DoesNotExistException | MultipleObjectsReturnedException | \Exception $e) {
			$sourcePath = '';
			$errors[] = $this->l->t('No source path found.');
		}

		if(!$this->isSourcePathValid($sourcePath)) {
			$errors[] = $this->l->t('Source path not valid.');
		}

		if (!$this->loadConnection()) {
			$errors[] = $this->l->t('Could not connect to sqlite db.');
		}

		$this->loadDevicesFromDB();

		return [
			'status' => 'success',
			'message' => 'just a test',
			'sqliteAvailable' => $this->isSqliteAvailable(),
			'sourcePath' => $sourcePath,
			'sourcePathValid' => $this->isSourcePathValid($sourcePath),
			'errors' => $errors,
		];
		// test if sqlite is enabled

		// check if all prerequisites are set

		// open connection

		// private function - import devices

		// more to import?

		// private function - import watch data

		// close connection

		// update timestamps in settings

		// send feedback to FE
	}

	/** @noinspection PhpUndefinedMethodInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	private function loadDevicesFromDB()
	{
		$sql = "SELECT * FROM 'DEVICE' LIMIT 0,10";
		foreach ($this->connection->query($sql) as $row) {
			try {
				$device = $this->devicesMapper->findBy($this->userId, $this->personId, intval($row['_id']));
			} catch (DoesNotExistException | MultipleObjectsReturnedException | Exception $e) {
				$device = new GadgetbridgeDevices();
				$device->setUserId($this->userId);
				$device->setPersonId($this->personId);
			}
			$device->setName($row['NAME']);
			$device->setGbId($row['_id']);
			$device->setManufacturer($row['MANUFACTURER']);
			$device->setIdentifier($row['IDENTIFIER']);
			$device->setType($row['TYPE']);
			$device->setModel($row['MODEL']);
			$device->setAlias($row['ALIAS']);
			try {
				if($device->getId()) {
					$this->devicesMapper->update($device);
				} else {
					$this->devicesMapper->insert($device);
				}
			} catch (Exception $e) {
				$this->logger->warning('error while writing device to db', $device->jsonSerialize());
			}
		}
	}
}
