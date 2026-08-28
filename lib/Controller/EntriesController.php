<?php

declare(strict_types=1);

namespace OCA\Health\Controller;

use OCA\Health\AppInfo\Application;
use OCA\Health\Exception\EntryNotFoundException;
use OCA\Health\Exception\InvalidEntryException;
use OCA\Health\ResponseDefinitions;
use OCA\Health\Service\EntryService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * @psalm-import-type HealthEntry from ResponseDefinitions
 * @psalm-import-type HealthEntriesPage from ResponseDefinitions
 * @psalm-suppress UnusedClass Controller routes are discovered by Nextcloud.
 */
class EntriesController extends OCSController {
	public function __construct(
		IRequest $request,
		private EntryService $entryService,
		private IUserSession $userSession,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Create a journal entry
	 *
	 * Records one supported metric value for the authenticated user and returns its canonical representation.
	 *
	 * @param string $metricKey Stable metric identifier: `stress`, `energy`, `mood`, `hydration`, or `break`.
	 * @param string $recordedAt RFC3339 timestamp with an explicit timezone or offset.
	 * @param int|null $numericValue Integer value from 1 through 5 for a scale metric; null for an event metric.
	 * @param string|null $optionValue Allowed option for an event metric; null for a scale metric.
	 * @param string $context Entry context: `manual`, `checkin`, `checkout`, or `reminder`.
	 * @param string|null $note Optional plain-text journal note of at most 1000 characters.
	 * @param string $source Entry source: `web`, `api`, `mobile`, or `notification`. Defaults to `api`.
	 * @return DataResponse<Http::STATUS_CREATED, HealthEntry, array{}>
	 * @throws OCSBadRequestException The entry fields are malformed or invalid.
	 *
	 * 201: Entry created
	 * 400: Invalid entry data
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'POST', url: '/api/v2/entries')]
	public function create(
		mixed $metricKey,
		mixed $recordedAt,
		mixed $numericValue = null,
		mixed $optionValue = null,
		mixed $context = 'manual',
		mixed $note = null,
		mixed $source = 'api',
	): DataResponse {
		try {
			$entry = $this->entryService->create(
				$this->getAuthenticatedUserId(),
				$metricKey,
				$numericValue,
				$optionValue,
				$context,
				$recordedAt,
				$note,
				$source,
			);
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		}

		return new DataResponse($entry, Http::STATUS_CREATED);
	}

	/**
	 * Update a journal entry
	 *
	 * Replaces the mutable fields of an entry owned by the authenticated user. The entry's metric and source cannot be changed.
	 *
	 * @param int $id Entry ID.
	 * @param int|null $numericValue Integer value from 1 through 5 for a scale metric; null for an event metric.
	 * @param string|null $optionValue Allowed option for an event metric; null for a scale metric.
	 * @param string $context Entry context: `manual`, `checkin`, `checkout`, or `reminder`.
	 * @param string $recordedAt RFC3339 timestamp with an explicit timezone or offset.
	 * @param string|null $note Optional plain-text journal note of at most 1000 characters.
	 * @return DataResponse<Http::STATUS_OK, HealthEntry, array{}>
	 * @throws OCSBadRequestException The entry fields are malformed or invalid.
	 * @throws OCSNotFoundException The entry does not exist or is not owned by the authenticated user.
	 *
	 * 200: Entry updated
	 * 400: Invalid entry data
	 * 404: Entry not found
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'PUT', url: '/api/v2/entries/{id}', requirements: ['id' => '\\d+'])]
	public function update(
		int $id,
		mixed $numericValue,
		mixed $optionValue,
		mixed $context,
		mixed $recordedAt,
		mixed $note,
	): DataResponse {
		try {
			$entry = $this->entryService->update(
				$this->getAuthenticatedUserId(),
				$id,
				$numericValue,
				$optionValue,
				$context,
				$recordedAt,
				$note,
			);
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		} catch (EntryNotFoundException $exception) {
			throw new OCSNotFoundException($exception->getMessage(), $exception);
		}

		return new DataResponse($entry, Http::STATUS_OK);
	}

	/**
	 * Delete a journal entry
	 *
	 * Permanently removes one entry owned by the authenticated user.
	 *
	 * @param int $id Entry ID.
	 * @return DataResponse<Http::STATUS_OK, null, array{}>
	 * @throws OCSNotFoundException The entry does not exist or is not owned by the authenticated user.
	 *
	 * 200: Entry deleted
	 * 404: Entry not found
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'DELETE', url: '/api/v2/entries/{id}', requirements: ['id' => '\\d+'])]
	public function destroy(int $id): DataResponse {
		try {
			$this->entryService->delete($this->getAuthenticatedUserId(), $id);
		} catch (EntryNotFoundException $exception) {
			throw new OCSNotFoundException($exception->getMessage(), $exception);
		}

		return new DataResponse(null, Http::STATUS_OK);
	}

	/**
	 * List journal entries
	 *
	 * Returns only entries owned by the authenticated user, ordered by `recordedAt DESC, id DESC`.
	 * Time filters use half-open `[from, to)` semantics.
	 *
	 * @param string|null $metricKey Optional stable metric identifier.
	 * @param string|null $from Optional inclusive RFC3339 lower bound for `recordedAt`.
	 * @param string|null $to Optional exclusive RFC3339 upper bound for `recordedAt`.
	 * @param int $limit Page size from 1 through 200. Defaults to 50.
	 * @param string|null $cursor Opaque cursor returned by the previous page.
	 * @return DataResponse<Http::STATUS_OK, HealthEntriesPage, array{}>
	 * @throws OCSBadRequestException A filter, limit, timestamp, or cursor is invalid.
	 *
	 * 200: Entries returned
	 * 400: Invalid list parameters
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'GET', url: '/api/v2/entries')]
	public function index(
		mixed $metricKey = null,
		mixed $from = null,
		mixed $to = null,
		mixed $limit = 50,
		mixed $cursor = null,
	): DataResponse {
		try {
			$page = $this->entryService->list(
				$this->getAuthenticatedUserId(),
				$metricKey,
				$from,
				$to,
				$limit,
				$cursor,
			);
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		}

		return new DataResponse($page, Http::STATUS_OK);
	}

	private function getAuthenticatedUserId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new OCSException('Authentication required.', Http::STATUS_UNAUTHORIZED);
		}

		return $user->getUID();
	}
}
