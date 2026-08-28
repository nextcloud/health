<?php

declare(strict_types=1);

namespace OCA\Health\Controller;

use OCA\Health\AppInfo\Application;
use OCA\Health\Exception\GoalNotFoundException;
use OCA\Health\Exception\InvalidEntryException;
use OCA\Health\ResponseDefinitions;
use OCA\Health\Service\GoalProgressService;
use OCA\Health\Service\GoalService;
use OCA\Health\Service\GoalTargetRegistry;
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
 * @psalm-import-type HealthGoal from ResponseDefinitions
 * @psalm-import-type HealthGoalsPage from ResponseDefinitions
 * @psalm-import-type HealthGoalProgressPage from ResponseDefinitions
 * @psalm-suppress UnusedClass Controller routes are discovered by Nextcloud.
 */
class GoalsController extends OCSController {
	public function __construct(
		IRequest $request,
		private GoalService $goalService,
		private GoalProgressService $goalProgressService,
		private GoalTargetRegistry $goalTargetRegistry,
		private IUserSession $userSession,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * List the authenticated user's active and paused goals and the supported target registry
	 *
	 * Retired goal identities remain available internally for historical progress but are excluded from goal management.
	 *
	 * @return DataResponse<Http::STATUS_OK, HealthGoalsPage, array{}>
	 *
	 * 200: Goals and supported target definitions returned
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'GET', url: '/api/v2/goals')]
	public function index(): DataResponse {
		return new DataResponse([
			'goals' => $this->goalService->list($this->userId()),
			'targets' => $this->goalTargetRegistry->getDefinitions(),
		]);
	}

	/**
	 * Create one logical personal goal
	 *
	 * A user can have one logical goal for each target and period. Its first revision becomes effective in the current local period.
	 *
	 * @param string $targetKey Stable target identifier from the Goal Target registry.
	 * @param 'day'|'week'|'month'|'long_term' $period Goal period supported by that target.
	 * @param 'gte'|'lte' $comparator Goal direction. `gte` means at least; `lte` means at most, at or below.
	 * @param int|float $targetValue Canonical target value.
	 * @param bool $remindersEnabled Whether deterministic Gentle reminders are enabled.
	 * @return DataResponse<Http::STATUS_CREATED, HealthGoal, array{}>
	 * @throws OCSBadRequestException The target, period, direction, or value is unsupported.
	 *
	 * 201: Goal created
	 * 400: Invalid goal data or a duplicate logical goal
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'POST', url: '/api/v2/goals')]
	public function create(mixed $targetKey, mixed $period, mixed $comparator, mixed $targetValue, mixed $remindersEnabled = false): DataResponse {
		try {
			return new DataResponse($this->goalService->create($this->userId(), $targetKey, $period, $comparator, $targetValue, $remindersEnabled), Http::STATUS_CREATED);
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		}
	}

	/**
	 * Update a logical goal or its current local-period revision
	 *
	 * Editing target value or direction does not rewrite older periods. Changing target or period retires the old identity and creates the new identity.
	 *
	 * @param int $id Goal ID.
	 * @param string|null $targetKey Optional target identifier.
	 * @param 'day'|'week'|'month'|'long_term'|null $period Optional supported period.
	 * @param 'gte'|'lte'|null $comparator Optional goal direction.
	 * @param int|float|null $targetValue Optional canonical target value.
	 * @param bool|null $active Optional active or paused state.
	 * @param bool|null $remindersEnabled Optional Gentle reminder setting.
	 * @return DataResponse<Http::STATUS_OK, HealthGoal, array{}>
	 * @throws OCSBadRequestException The update fields are invalid.
	 * @throws OCSNotFoundException The goal is missing or belongs to another user.
	 *
	 * 200: Goal updated
	 * 400: Invalid goal data
	 * 404: Goal not found
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'PUT', url: '/api/v2/goals/{id}', requirements: ['id' => '\\d+'])]
	public function update(int $id, mixed $targetKey = null, mixed $period = null, mixed $comparator = null, mixed $targetValue = null, mixed $active = null, mixed $remindersEnabled = null): DataResponse {
		try {
			return new DataResponse($this->goalService->update($this->userId(), $id, $targetKey, $period, $comparator, $targetValue, $active, $remindersEnabled));
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		} catch (GoalNotFoundException $exception) {
			throw new OCSNotFoundException($exception->getMessage(), $exception);
		}
	}

	/**
	 * Retire a goal without deleting its historical revisions or past progress
	 *
	 * @param int $id Goal ID.
	 * @return DataResponse<Http::STATUS_OK, HealthGoal, array{}>
	 * @throws OCSNotFoundException The goal is missing or belongs to another user.
	 *
	 * 200: Goal retired
	 * 404: Goal not found
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'DELETE', url: '/api/v2/goals/{id}', requirements: ['id' => '\\d+'])]
	public function destroy(int $id): DataResponse {
		try {
			return new DataResponse($this->goalService->retire($this->userId(), $id));
		} catch (GoalNotFoundException $exception) {
			throw new OCSNotFoundException($exception->getMessage(), $exception);
		}
	}

	/**
	 * Evaluate all goals in one requested local period
	 *
	 * Boundaries are resolved in the authenticated user's Nextcloud timezone. Progress is derived from entries, measurements, and daily values; it is never persisted.
	 *
	 * @param 'day'|'week'|'month'|'long_term' $period Requested goal period.
	 * @param string|null $date Local YYYY-MM-DD date selecting a day, week, or month. Omit for `long_term`.
	 * @return DataResponse<Http::STATUS_OK, HealthGoalProgressPage, array{}>
	 * @throws OCSBadRequestException The period or date is invalid or in the future.
	 *
	 * 200: Goal progress returned
	 * 400: Invalid period or date
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'GET', url: '/api/v2/goals/progress')]
	public function progress(mixed $period, mixed $date = null): DataResponse {
		try {
			return new DataResponse(['goals' => $this->goalProgressService->list($this->userId(), $period, $date)]);
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		}
	}

	private function userId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new OCSException('Authentication required.', Http::STATUS_UNAUTHORIZED);
		}
		return $user->getUID();
	}
}
