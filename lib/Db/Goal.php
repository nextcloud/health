<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use DateTimeImmutable;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/** @psalm-suppress PropertyNotSetInConstructor The ID is populated by Nextcloud's Entity/Mapper lifecycle. */
class Goal extends Entity {
	protected ?string $userId = null;
	protected ?string $targetKey = null;
	protected ?string $period = null;
	protected ?bool $active = null;
	protected ?bool $remindersEnabled = null;
	protected ?string $reminderPolicy = null;
	protected ?DateTimeImmutable $retiredAt = null;
	protected ?DateTimeImmutable $createdAt = null;
	protected ?DateTimeImmutable $updatedAt = null;

	public function __construct() {
		$this->addType('id', Types::BIGINT);
		$this->addType('active', Types::BOOLEAN);
		$this->addType('remindersEnabled', Types::BOOLEAN);
		$this->addType('retiredAt', Types::DATETIME_IMMUTABLE);
		$this->addType('createdAt', Types::DATETIME_IMMUTABLE);
		$this->addType('updatedAt', Types::DATETIME_IMMUTABLE);
	}

	public function getUserId(): string {
		return $this->userId ?? '';
	}
	public function setUserId(string $value): void {
		$this->setter('userId', [$value]);
	}
	public function getTargetKey(): string {
		return $this->targetKey ?? '';
	}
	public function setTargetKey(string $value): void {
		$this->setter('targetKey', [$value]);
	}
	public function getPeriod(): string {
		return $this->period ?? '';
	}
	public function setPeriod(string $value): void {
		$this->setter('period', [$value]);
	}
	public function isActive(): bool {
		return $this->active ?? false;
	}
	public function setActive(bool $value): void {
		$this->setter('active', [$value]);
	}
	public function isRemindersEnabled(): bool {
		return $this->remindersEnabled ?? false;
	}
	public function setRemindersEnabled(bool $value): void {
		$this->setter('remindersEnabled', [$value]);
	}
	public function getReminderPolicy(): string {
		return $this->reminderPolicy ?? 'gentle';
	}
	public function setReminderPolicy(string $value): void {
		$this->setter('reminderPolicy', [$value]);
	}
	public function getRetiredAt(): ?DateTimeImmutable {
		return $this->retiredAt;
	}
	public function setRetiredAt(?DateTimeImmutable $value): void {
		$this->setter('retiredAt', [$value]);
	}
	public function getCreatedAt(): DateTimeImmutable {
		return $this->createdAt ?? throw new \LogicException('createdAt has not been set.');
	}
	public function setCreatedAt(DateTimeImmutable $value): void {
		$this->setter('createdAt', [$value]);
	}
	public function getUpdatedAt(): DateTimeImmutable {
		return $this->updatedAt ?? throw new \LogicException('updatedAt has not been set.');
	}
	public function setUpdatedAt(DateTimeImmutable $value): void {
		$this->setter('updatedAt', [$value]);
	}
}
