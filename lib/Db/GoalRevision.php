<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use DateTimeImmutable;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

class GoalRevision extends Entity {
	protected ?int $goalId = null;
	protected ?string $comparator = null;
	protected ?string $targetValue = null;
	protected ?string $secondaryTargetValue = null;
	protected ?string $effectiveFrom = null;
	protected ?string $effectiveTo = null;
	protected ?DateTimeImmutable $createdAt = null;
	protected ?DateTimeImmutable $updatedAt = null;

	public function __construct() {
		$this->addType('id', Types::BIGINT);
		$this->addType('goalId', Types::BIGINT);
		$this->addType('targetValue', Types::DECIMAL);
		$this->addType('secondaryTargetValue', Types::DECIMAL);
		$this->addType('createdAt', Types::DATETIME_IMMUTABLE);
		$this->addType('updatedAt', Types::DATETIME_IMMUTABLE);
	}

	public function getGoalId(): int {
		return $this->goalId ?? 0;
	}
	public function setGoalId(int $value): void {
		$this->setter('goalId', [$value]);
	}
	public function getComparator(): string {
		return $this->comparator ?? '';
	}
	public function setComparator(string $value): void {
		$this->setter('comparator', [$value]);
	}
	public function getTargetValue(): string {
		return $this->targetValue ?? '';
	}
	public function setTargetValue(string $value): void {
		$this->setter('targetValue', [$value]);
	}
	public function getSecondaryTargetValue(): ?string {
		return $this->secondaryTargetValue;
	}
	public function setSecondaryTargetValue(?string $value): void {
		$this->setter('secondaryTargetValue', [$value]);
	}
	public function getEffectiveFrom(): string {
		return $this->effectiveFrom ?? '';
	}
	public function setEffectiveFrom(string $value): void {
		$this->setter('effectiveFrom', [$value]);
	}
	public function getEffectiveTo(): ?string {
		return $this->effectiveTo;
	}
	public function setEffectiveTo(?string $value): void {
		$this->setter('effectiveTo', [$value]);
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
