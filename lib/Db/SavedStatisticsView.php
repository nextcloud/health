<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use DateTimeImmutable;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/** @psalm-suppress PropertyNotSetInConstructor The ID is populated by Nextcloud's Entity/Mapper lifecycle. */
class SavedStatisticsView extends Entity {
	protected ?string $userId = null;
	protected ?string $title = null;
	protected ?string $icon = null;
	protected ?string $metricKeys = null;
	protected ?string $period = null;
	protected ?DateTimeImmutable $createdAt = null;
	protected ?DateTimeImmutable $updatedAt = null;

	public function __construct() {
		$this->addType('id', Types::BIGINT);
		$this->addType('createdAt', Types::DATETIME_IMMUTABLE);
		$this->addType('updatedAt', Types::DATETIME_IMMUTABLE);
	}

	public function getUserId(): string {
		return $this->userId ?? '';
	}
	public function setUserId(string $value): void {
		$this->setter('userId', [$value]);
	}
	public function getTitle(): string {
		return $this->title ?? '';
	}
	public function setTitle(string $value): void {
		$this->setter('title', [$value]);
	}
	public function getIcon(): string {
		return $this->icon ?? '';
	}
	public function setIcon(string $value): void {
		$this->setter('icon', [$value]);
	}
	public function getMetricKeys(): string {
		return $this->metricKeys ?? '';
	}
	public function setMetricKeys(string $value): void {
		$this->setter('metricKeys', [$value]);
	}
	public function getPeriod(): string {
		return $this->period ?? '';
	}
	public function setPeriod(string $value): void {
		$this->setter('period', [$value]);
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
