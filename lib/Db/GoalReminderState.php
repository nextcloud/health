<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use DateTimeImmutable;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

class GoalReminderState extends Entity {
	protected ?int $goalId = null;
	protected ?string $periodKey = null;
	protected ?DateTimeImmutable $lastNotificationAt = null;
	protected ?int $notificationCount = null;
	protected ?string $lastNotificationReason = null;
	protected ?DateTimeImmutable $updatedAt = null;

	public function __construct() {
		$this->addType('id', Types::BIGINT);
		$this->addType('goalId', Types::BIGINT);
		$this->addType('lastNotificationAt', Types::DATETIME_IMMUTABLE);
		$this->addType('notificationCount', Types::INTEGER);
		$this->addType('updatedAt', Types::DATETIME_IMMUTABLE);
	}

	public function getGoalId(): int {
		return $this->goalId ?? 0;
	}
	public function setGoalId(int $value): void {
		$this->setter('goalId', [$value]);
	}
	public function getPeriodKey(): string {
		return $this->periodKey ?? '';
	}
	public function setPeriodKey(string $value): void {
		$this->setter('periodKey', [$value]);
	}
	public function getLastNotificationAt(): ?DateTimeImmutable {
		return $this->lastNotificationAt;
	}
	public function setLastNotificationAt(?DateTimeImmutable $value): void {
		$this->setter('lastNotificationAt', [$value]);
	}
	public function getNotificationCount(): int {
		return $this->notificationCount ?? 0;
	}
	public function setNotificationCount(int $value): void {
		$this->setter('notificationCount', [$value]);
	}
	public function getLastNotificationReason(): ?string {
		return $this->lastNotificationReason;
	}
	public function setLastNotificationReason(?string $value): void {
		$this->setter('lastNotificationReason', [$value]);
	}
	public function getUpdatedAt(): DateTimeImmutable {
		return $this->updatedAt ?? throw new \LogicException('updatedAt has not been set.');
	}
	public function setUpdatedAt(DateTimeImmutable $value): void {
		$this->setter('updatedAt', [$value]);
	}
}
