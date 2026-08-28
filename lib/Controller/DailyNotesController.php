<?php

declare(strict_types=1);

namespace OCA\Health\Controller;

use OCA\Health\AppInfo\Application;
use OCA\Health\Exception\InvalidEntryException;
use OCA\Health\ResponseDefinitions;
use OCA\Health\Service\DailyNoteService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * @psalm-import-type HealthDailyNote from ResponseDefinitions
 * @psalm-suppress UnusedClass Controller routes are discovered by Nextcloud.
 */
class DailyNotesController extends OCSController {
	public function __construct(
		IRequest $request,
		private DailyNoteService $dailyNoteService,
		private IUserSession $userSession,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Get a daily note
	 *
	 * Returns the authenticated user's plain-text note for one local calendar date. A missing note has null content.
	 *
	 * @param string $date Local calendar date in YYYY-MM-DD format.
	 * @return DataResponse<Http::STATUS_OK, HealthDailyNote, array{}>
	 * @throws OCSBadRequestException The date is invalid.
	 *
	 * 200: Daily note returned
	 * 400: Invalid local date
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'GET', url: '/api/v2/daily-notes/{date}', requirements: ['date' => '\\d{4}-\\d{2}-\\d{2}'])]
	public function show(string $date): DataResponse {
		try {
			$dailyNote = $this->dailyNoteService->get($this->getAuthenticatedUserId(), $date);
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		}

		return new DataResponse($dailyNote, Http::STATUS_OK);
	}

	/**
	 * Save a daily note
	 *
	 * Creates or updates the authenticated user's plain-text note for one local calendar date.
	 *
	 * @param string $date Local calendar date in YYYY-MM-DD format.
	 * @param string $content Plain-text content of at most 2000 characters.
	 * @return DataResponse<Http::STATUS_OK, HealthDailyNote, array{}>
	 * @throws OCSBadRequestException The date or content is invalid.
	 *
	 * 200: Daily note saved
	 * 400: Invalid local date or content
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'PUT', url: '/api/v2/daily-notes/{date}', requirements: ['date' => '\\d{4}-\\d{2}-\\d{2}'])]
	public function update(string $date, mixed $content): DataResponse {
		try {
			$dailyNote = $this->dailyNoteService->save($this->getAuthenticatedUserId(), $date, $content);
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		}

		return new DataResponse($dailyNote, Http::STATUS_OK);
	}

	private function getAuthenticatedUserId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new OCSException('Authentication required.', Http::STATUS_UNAUTHORIZED);
		}

		return $user->getUID();
	}
}
