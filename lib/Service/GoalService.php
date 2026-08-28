<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use DateTimeImmutable;
use DateTimeZone;
use OCA\Health\Db\Goal;
use OCA\Health\Db\GoalMapper;
use OCA\Health\Db\GoalRevision;
use OCA\Health\Db\GoalRevisionMapper;
use OCA\Health\Exception\GoalNotFoundException;
use OCA\Health\Exception\InvalidEntryException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IDateTimeZone;

/**
 * @psalm-import-type HealthGoal from \OCA\Health\ResponseDefinitions
 */
class GoalService {
	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
	private DateTimeZone $utc;

	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud dependency injection. */
	public function __construct(
		private GoalMapper $goalMapper,
		private GoalRevisionMapper $goalRevisionMapper,
		private GoalTargetRegistry $goalTargetRegistry,
		private IDateTimeZone $dateTimeZone,
	) {
		$this->utc = new DateTimeZone('UTC');
	}

	/** @return list<HealthGoal> */
	public function list(string $userId): array {
		return array_map(fn (Goal $goal): array => $this->format($goal, $this->currentRevision($goal)), $this->goalMapper->findManageableForUser($userId));
	}

	/** @return HealthGoal */
	public function create(string $userId, mixed $targetKey, mixed $period, mixed $comparator, mixed $targetValue, mixed $remindersEnabled = false): array {
		$target = $this->goalTargetRegistry->getDefinition($targetKey);
		$targetKey = $target['targetKey'];
		$period = $this->goalTargetRegistry->validatePeriod($targetKey, $period);
		$comparator = $this->goalTargetRegistry->validateComparator($targetKey, $comparator);
		$targetValue = $this->goalTargetRegistry->validateTargetValue($targetKey, $targetValue);
		if (!is_bool($remindersEnabled)) {
			throw new InvalidEntryException('remindersEnabled must be a boolean.');
		}
		$this->assertIdentityAvailable($userId, $targetKey, $period);
		$now = new DateTimeImmutable('now', $this->utc);
		$goal = new Goal();
		$goal->setUserId($userId);
		$goal->setTargetKey($targetKey);
		$goal->setPeriod($period);
		$goal->setActive(true);
		$goal->setRemindersEnabled($remindersEnabled);
		$goal->setReminderPolicy('gentle');
		$goal->setCreatedAt($now);
		$goal->setUpdatedAt($now);
		$goal = $this->goalMapper->create($goal);
		$revision = $this->newRevision($goal->getId(), $comparator, $targetValue, $this->effectiveFrom($userId, $period), $now);
		return $this->format($goal, $this->goalRevisionMapper->create($revision));
	}

	/** @return HealthGoal */
	public function update(string $userId, int $id, mixed $targetKey = null, mixed $period = null, mixed $comparator = null, mixed $targetValue = null, mixed $active = null, mixed $remindersEnabled = null): array {
		$goal = $this->findForUser($userId, $id);
		if ($goal->getRetiredAt() !== null) {
			throw new InvalidEntryException('Retired goals cannot be updated.');
		}
		$currentRevision = $this->currentRevision($goal);
		$newTargetKey = $targetKey === null ? $goal->getTargetKey() : $this->goalTargetRegistry->getDefinition($targetKey)['targetKey'];
		$newPeriod = $period === null ? $goal->getPeriod() : $this->goalTargetRegistry->validatePeriod($newTargetKey, $period);
		$newComparator = $comparator === null ? $currentRevision->getComparator() : $this->goalTargetRegistry->validateComparator($newTargetKey, $comparator);
		$newTargetValue = $targetValue === null ? (float)$currentRevision->getTargetValue() : $this->goalTargetRegistry->validateTargetValue($newTargetKey, $targetValue);
		if ($active !== null && !is_bool($active)) {
			throw new InvalidEntryException('active must be a boolean.');
		}
		if ($remindersEnabled !== null && !is_bool($remindersEnabled)) {
			throw new InvalidEntryException('remindersEnabled must be a boolean.');
		}

		if ($newTargetKey !== $goal->getTargetKey() || $newPeriod !== $goal->getPeriod()) {
			$this->assertIdentityAvailable($userId, $newTargetKey, $newPeriod);
			$this->retireGoal($goal);
			return $this->create($userId, $newTargetKey, $newPeriod, $newComparator, $newTargetValue, $remindersEnabled ?? $goal->isRemindersEnabled());
		}

		$now = new DateTimeImmutable('now', $this->utc);
		$goal->setActive($active ?? $goal->isActive());
		$goal->setRemindersEnabled($remindersEnabled ?? $goal->isRemindersEnabled());
		$goal->setUpdatedAt($now);
		$goal = $this->goalMapper->updateForUser($goal);
		if ($comparator !== null || $targetValue !== null) {
			$currentRevision = $this->revise($userId, $goal, $newComparator, $newTargetValue, $now);
		}
		return $this->format($goal, $currentRevision);
	}

	/** @return HealthGoal */
	public function retire(string $userId, int $id): array {
		$goal = $this->findForUser($userId, $id);
		$this->retireGoal($goal);
		return $this->format($goal, $this->currentRevision($goal));
	}

	private function retireGoal(Goal $goal): void {
		$now = new DateTimeImmutable('now', $this->utc);
		$goal->setActive(false);
		$goal->setRemindersEnabled(false);
		$goal->setRetiredAt($now);
		$goal->setUpdatedAt($now);
		$this->goalMapper->updateForUser($goal);
	}

	private function revise(string $userId, Goal $goal, string $comparator, float $targetValue, DateTimeImmutable $now): GoalRevision {
		$effectiveFrom = $this->effectiveFrom($userId, $goal->getPeriod(), $now);
		try {
			$revision = $this->goalRevisionMapper->findForGoalEffectiveFrom($goal->getId(), $effectiveFrom);
			$revision->setComparator($comparator);
			$revision->setTargetValue((string)$targetValue);
			$revision->setUpdatedAt($now);
			return $this->goalRevisionMapper->updateForGoal($revision);
		} catch (DoesNotExistException|MultipleObjectsReturnedException) {
			$current = $this->currentRevision($goal);
			$current->setEffectiveTo((new DateTimeImmutable($effectiveFrom, $this->dateTimeZone->getTimeZone(false, $userId)))->modify('-1 day')->format('Y-m-d'));
			$current->setUpdatedAt($now);
			$this->goalRevisionMapper->updateForGoal($current);
			return $this->goalRevisionMapper->create($this->newRevision($goal->getId(), $comparator, $targetValue, $effectiveFrom, $now));
		}
	}

	private function newRevision(int $goalId, string $comparator, float $targetValue, string $effectiveFrom, DateTimeImmutable $now): GoalRevision {
		$revision = new GoalRevision();
		$revision->setGoalId($goalId);
		$revision->setComparator($comparator);
		$revision->setTargetValue((string)$targetValue);
		$revision->setSecondaryTargetValue(null);
		$revision->setEffectiveFrom($effectiveFrom);
		$revision->setEffectiveTo(null);
		$revision->setCreatedAt($now);
		$revision->setUpdatedAt($now);
		return $revision;
	}

	private function effectiveFrom(string $userId, string $period, ?DateTimeImmutable $now = null): string {
		$local = ($now ?? new DateTimeImmutable('now', $this->utc))->setTimezone($this->dateTimeZone->getTimeZone(false, $userId))->setTime(0, 0);
		return match ($period) {
			'day', 'long_term' => $local->format('Y-m-d'),
			'week' => $local->modify('monday this week')->format('Y-m-d'),
			'month' => $local->modify('first day of this month')->format('Y-m-d'),
			default => throw new \LogicException('Unsupported goal period.'),
		};
	}

	private function findForUser(string $userId, int $id): Goal {
		try {
			return $this->goalMapper->findForUser($id, $userId);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $exception) {
			throw new GoalNotFoundException('Goal not found.', 0, $exception);
		}
	}

	/**
	 * The database unique index is the final concurrent-write protection. This check
	 * keeps an invalid target/period edit from retiring the original goal first.
	 */
	private function assertIdentityAvailable(string $userId, string $targetKey, string $period): void {
		try {
			$this->goalMapper->findForIdentity($userId, $targetKey, $period);
		} catch (DoesNotExistException) {
			return;
		} catch (MultipleObjectsReturnedException $exception) {
			throw new \LogicException('Goal identity is not unique.', 0, $exception);
		}

		throw new InvalidEntryException('A goal for this target and period already exists.');
	}

	private function currentRevision(Goal $goal): GoalRevision {
		try {
			return $this->goalRevisionMapper->findCurrentForGoal($goal->getId());
		} catch (DoesNotExistException|MultipleObjectsReturnedException $exception) {
			throw new \LogicException('Goal is missing its current revision.', 0, $exception);
		}
	}

	/** @return HealthGoal */
	private function format(Goal $goal, GoalRevision $revision): array {
		$period = $goal->getPeriod();
		/** @var 'day'|'week'|'month'|'long_term' $period */
		$reminderPolicy = $goal->getReminderPolicy();
		/** @var 'gentle' $reminderPolicy */
		$comparator = $revision->getComparator();
		/** @var 'gte'|'lte' $comparator */
		/** @var HealthGoal $formatted */
		$formatted = [
			'id' => $goal->getId(),
			'targetKey' => $goal->getTargetKey(),
			'period' => $period,
			'active' => $goal->isActive(),
			'remindersEnabled' => $goal->isRemindersEnabled(),
			'reminderPolicy' => $reminderPolicy,
			'retiredAt' => $goal->getRetiredAt()?->setTimezone($this->utc)->format('Y-m-d\TH:i:s\Z'),
			'createdAt' => $goal->getCreatedAt()->setTimezone($this->utc)->format('Y-m-d\TH:i:s\Z'),
			'updatedAt' => $goal->getUpdatedAt()->setTimezone($this->utc)->format('Y-m-d\TH:i:s\Z'),
			'currentRevision' => [
				'id' => $revision->getId(),
				'comparator' => $comparator,
				'targetValue' => (float)$revision->getTargetValue(),
				'secondaryTargetValue' => $revision->getSecondaryTargetValue() === null ? null : (float)$revision->getSecondaryTargetValue(),
				'effectiveFrom' => $revision->getEffectiveFrom(),
				'effectiveTo' => $revision->getEffectiveTo(),
			],
		];
		return $formatted;
	}
}
