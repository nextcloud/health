<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use DateTimeImmutable;
use DateTimeZone;
use OCA\Health\Db\SavedStatisticsView;
use OCA\Health\Db\SavedStatisticsViewMapper;
use OCA\Health\Exception\InvalidEntryException;
use OCA\Health\Exception\SavedStatisticsViewNotFoundException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * Stores validated, owner-scoped Statistics configurations. Derived Health data remains in StatisticsService.
 *
 * @psalm-import-type HealthSavedStatisticsView from \OCA\Health\ResponseDefinitions
 */
class SavedStatisticsViewService {
	private const MAX_TITLE_LENGTH = 120;
	private const MAX_ICON_LENGTH = 64;

	private DateTimeZone $utc;

	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
	public function __construct(
		private SavedStatisticsViewMapper $savedStatisticsViewMapper,
		private StatisticsService $statisticsService,
	) {
		$this->utc = new DateTimeZone('UTC');
	}

	/** @return list<HealthSavedStatisticsView> */
	public function list(string $userId): array {
		$this->requireUserId($userId);
		return array_map(fn (SavedStatisticsView $view): array => $this->format($view), $this->savedStatisticsViewMapper->findAllForUser($userId));
	}

	/** @return HealthSavedStatisticsView */
	public function get(string $userId, int $id): array {
		return $this->format($this->findForUser($userId, $id));
	}

	/** @return HealthSavedStatisticsView */
	public function create(string $userId, mixed $title, mixed $icon, mixed $metricKeys, mixed $period): array {
		$this->requireUserId($userId);
		$configuration = $this->validateConfiguration($userId, $metricKeys, $period);
		$now = new DateTimeImmutable('now', $this->utc);
		$view = new SavedStatisticsView();
		$view->setUserId($userId);
		$view->setTitle($this->validateTitle($title));
		$view->setIcon($this->validateIcon($icon));
		$view->setMetricKeys(implode(',', $configuration['metricKeys']));
		$view->setPeriod($configuration['period']);
		$view->setCreatedAt($now);
		$view->setUpdatedAt($now);

		return $this->format($this->savedStatisticsViewMapper->create($view));
	}

	/** @return HealthSavedStatisticsView */
	public function update(string $userId, int $id, mixed $title, mixed $icon, mixed $metricKeys, mixed $period): array {
		$view = $this->findForUser($userId, $id);
		$configuration = $this->validateConfiguration($userId, $metricKeys, $period);
		$view->setTitle($this->validateTitle($title));
		$view->setIcon($this->validateIcon($icon));
		$view->setMetricKeys(implode(',', $configuration['metricKeys']));
		$view->setPeriod($configuration['period']);
		$view->setUpdatedAt(new DateTimeImmutable('now', $this->utc));

		return $this->format($this->savedStatisticsViewMapper->updateForUser($view));
	}

	public function delete(string $userId, int $id): void {
		$this->savedStatisticsViewMapper->deleteForUser($this->findForUser($userId, $id));
	}

	/**
	 * @return array{
	 *   period: 'this_week'|'last_week'|'last_7_days'|'last_30_days'|'this_month'|'last_month'|'this_year'|'last_year',
	 *   metricKeys: list<string>
	 * }
	 */
	private function validateConfiguration(string $userId, mixed $metricKeys, mixed $period): array {
		if (!is_array($metricKeys) || $metricKeys === []) {
			throw new InvalidEntryException('Select at least one statistics metric.');
		}

		$keys = [];
		foreach ($metricKeys as $metricKey) {
			if (!is_string($metricKey)) {
				throw new InvalidEntryException('metricKeys must contain stable metric identifiers.');
			}
			$keys[] = $metricKey;
		}

		return $this->statisticsService->validateConfiguration($userId, $period, implode(',', $keys));
	}

	private function validateTitle(mixed $title): string {
		if (!is_string($title)) {
			throw new InvalidEntryException('A saved Statistics view title is required.');
		}
		$title = trim($title);
		if ($title === '' || mb_strlen($title) > self::MAX_TITLE_LENGTH || preg_match('/[\x00-\x1F\x7F]/u', $title) === 1) {
			throw new InvalidEntryException('The saved Statistics view title is invalid.');
		}
		return $title;
	}

	private function validateIcon(mixed $icon): string {
		if (!is_string($icon)) {
			throw new InvalidEntryException('A saved Statistics view icon is required.');
		}
		$icon = trim($icon);
		if ($icon === '' || mb_strlen($icon) > self::MAX_ICON_LENGTH || preg_match('/[\x00-\x1F\x7F]/u', $icon) === 1) {
			throw new InvalidEntryException('The saved Statistics view icon is invalid.');
		}
		return $icon;
	}

	private function requireUserId(string $userId): void {
		if ($userId === '') {
			throw new InvalidEntryException('An authenticated user is required.');
		}
	}

	private function findForUser(string $userId, int $id): SavedStatisticsView {
		$this->requireUserId($userId);
		try {
			return $this->savedStatisticsViewMapper->findForUser($id, $userId);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $exception) {
			throw new SavedStatisticsViewNotFoundException('Saved Statistics view not found.', 0, $exception);
		}
	}

	/** @return HealthSavedStatisticsView */
	private function format(SavedStatisticsView $view): array {
		if ($view->getUserId() === '') {
			throw new \LogicException('Saved Statistics view has no owner.');
		}
		$metricKeys = $view->getMetricKeys() === '' ? [] : explode(',', $view->getMetricKeys());
		/** @var 'this_week'|'last_week'|'last_7_days'|'last_30_days'|'this_month'|'last_month'|'this_year'|'last_year' $period */
		$period = $view->getPeriod();
		return [
			'id' => $view->getId(),
			'title' => $view->getTitle(),
			'icon' => $view->getIcon(),
			'metricKeys' => $metricKeys,
			'period' => $period,
			'createdAt' => $view->getCreatedAt()->format('Y-m-d\\TH:i:s\\Z'),
			'updatedAt' => $view->getUpdatedAt()->format('Y-m-d\\TH:i:s\\Z'),
		];
	}
}
