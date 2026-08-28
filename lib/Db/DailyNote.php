<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use DateTimeImmutable;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/** @psalm-suppress PropertyNotSetInConstructor The inherited ID is assigned by QBMapper. */
class DailyNote extends Entity {
	protected ?string $userId = null;
	protected ?string $localDate = null;
	protected ?string $content = null;
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

	public function setUserId(string $userId): void {
		$this->setter('userId', [$userId]);
	}

	public function getLocalDate(): string {
		return $this->localDate ?? '';
	}

	public function setLocalDate(string $localDate): void {
		$this->setter('localDate', [$localDate]);
	}

	public function getContent(): string {
		return $this->content ?? '';
	}

	public function setContent(string $content): void {
		$this->setter('content', [$content]);
	}

	public function getCreatedAt(): DateTimeImmutable {
		if ($this->createdAt === null) {
			throw new \LogicException('createdAt has not been set.');
		}

		return $this->createdAt;
	}

	public function setCreatedAt(DateTimeImmutable $createdAt): void {
		$this->setter('createdAt', [$createdAt]);
	}

	public function getUpdatedAt(): DateTimeImmutable {
		if ($this->updatedAt === null) {
			throw new \LogicException('updatedAt has not been set.');
		}

		return $this->updatedAt;
	}

	public function setUpdatedAt(DateTimeImmutable $updatedAt): void {
		$this->setter('updatedAt', [$updatedAt]);
	}
}
