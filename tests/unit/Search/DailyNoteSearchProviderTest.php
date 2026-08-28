<?php

declare(strict_types=1);

namespace OCA\Health\Tests\Unit\Search;

use DateTimeInterface;
use DateTimeZone;
use OCA\Health\Db\DailyNote;
use OCA\Health\Db\DailyNoteMapper;
use OCA\Health\Search\DailyNoteSearchProvider;
use OCA\Health\Service\ConfigurationService;
use OCP\IDateTimeZone;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\ISearchQuery;
use PHPUnit\Framework\TestCase;

class DailyNoteSearchProviderTest extends TestCase {
	public function testDisabledPreferenceReturnsNoResultsWithoutQueryingNotes(): void {
		$configuration = $this->createMock(ConfigurationService::class);
		$configuration->expects(self::once())->method('isDailyNoteSearchEnabled')->with('alice')->willReturn(false);
		$mapper = $this->createMock(DailyNoteMapper::class);
		$mapper->expects(self::never())->method('findMatchingForUser');

		$result = $this->provider($configuration, $mapper)->search($this->user('alice'), $this->query('calm', 10));

		self::assertFalse($result->jsonSerialize()['isPaginated']);
		self::assertSame([], $result->jsonSerialize()['entries']);
	}

	public function testEnabledSearchUsesOwnedTokensAndReturnsPlainTextSnippet(): void {
		$configuration = $this->createMock(ConfigurationService::class);
		$configuration->method('isDailyNoteSearchEnabled')->with('alice')->willReturn(true);
		$mapper = $this->createMock(DailyNoteMapper::class);
		$mapper->expects(self::once())->method('findMatchingForUser')
			->with('alice', ['CALM', 'walk'], null, 3)
			->willReturn([$this->note('alice', '2026-08-20', '<b>Calm</b> walk after lunch.')]);

		$result = $this->provider($configuration, $mapper)->search($this->user('alice'), $this->query("  CALM\nwalk  ", 2));
		/** @var array{entries: list<object>, isPaginated: bool} $serialized */
		$serialized = $result->jsonSerialize();
		/** @var array{title: string, subline: string, resourceUrl: string} $entry */
		$entry = $serialized['entries'][0]->jsonSerialize();

		self::assertFalse($serialized['isPaginated']);
		self::assertSame('Daily Note – Thursday, 20 August 2026', $entry['title']);
		self::assertStringNotContainsString('{date}', $entry['title']);
		self::assertStringContainsString('Thursday, 20 August 2026', $entry['title']);
		self::assertSame('Calm walk after lunch.', $entry['subline']);
		self::assertStringNotContainsString('<', $entry['subline']);
		self::assertSame('https://cloud.test/apps/health/journal/2026-08-20', $entry['resourceUrl']);
	}

	public function testSearchReturnsDateCursorWhenMoreOwnedNotesExist(): void {
		$configuration = $this->createMock(ConfigurationService::class);
		$configuration->method('isDailyNoteSearchEnabled')->willReturn(true);
		$mapper = $this->createMock(DailyNoteMapper::class);
		$mapper->method('findMatchingForUser')->willReturn([
			$this->note('alice', '2026-08-20', 'Calm day'),
			$this->note('alice', '2026-08-19', 'Calm walk'),
			$this->note('alice', '2026-08-18', 'Calm evening'),
		]);

		$result = $this->provider($configuration, $mapper)->search($this->user('alice'), $this->query('calm', 2));
		$serialized = $result->jsonSerialize();

		self::assertTrue($serialized['isPaginated']);
		self::assertSame('2026-08-19', $serialized['cursor']);
		self::assertCount(2, $serialized['entries']);
	}

	public function testLongUnicodeSnippetIsBoundedAndCentredOnTheFirstMatch(): void {
		$configuration = $this->createMock(ConfigurationService::class);
		$configuration->method('isDailyNoteSearchEnabled')->willReturn(true);
		$mapper = $this->createMock(DailyNoteMapper::class);
		$mapper->method('findMatchingForUser')->willReturn([
			$this->note('alice', '2026-08-20', str_repeat('ä', 180) . ' focus ' . str_repeat('ö', 180)),
		]);

		$result = $this->provider($configuration, $mapper)->search($this->user('alice'), $this->query('FOCUS', 10));
		/** @var array{subline: string} $entry */
		$entry = $result->jsonSerialize()['entries'][0]->jsonSerialize();

		self::assertStringStartsWith('…', $entry['subline']);
		self::assertStringEndsWith('…', $entry['subline']);
		self::assertStringContainsString('focus', $entry['subline']);
		self::assertLessThanOrEqual(162, mb_strlen($entry['subline'], 'UTF-8'));
	}

	private function provider(ConfigurationService $configuration, DailyNoteMapper $mapper): DailyNoteSearchProvider {
		$timeZone = $this->createMock(IDateTimeZone::class);
		$timeZone->method('getTimeZone')->willReturn(new DateTimeZone('Europe/Berlin'));
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static function (string $message, array $parameters = []): string {
			return $parameters === [] ? $message : vsprintf($message, array_values($parameters));
		});
		$l10n->method('l')->willReturnCallback(static function (string $type, DateTimeInterface $date): string {
			return $date->format('l, d F Y');
		});
		$url = $this->createMock(IURLGenerator::class);
		$url->method('imagePath')->willReturn('/apps/health/img/app.svg');
		$url->method('getAbsoluteURL')->willReturnCallback(static fn (string $path): string => 'https://cloud.test' . $path);
		$url->method('linkToRouteAbsolute')->willReturnCallback(static fn (string $route, array $parameters): string => 'https://cloud.test/apps/health/journal/' . $parameters['date']);

		return new DailyNoteSearchProvider($configuration, $mapper, $timeZone, $l10n, $url);
	}

	private function note(string $userId, string $date, string $content): DailyNote {
		$note = new DailyNote();
		$note->setUserId($userId);
		$note->setLocalDate($date);
		$note->setContent($content);
		return $note;
	}

	private function user(string $userId): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($userId);
		return $user;
	}

	private function query(string $term, int $limit): ISearchQuery {
		$query = $this->createMock(ISearchQuery::class);
		$query->method('getTerm')->willReturn($term);
		$query->method('getLimit')->willReturn($limit);
		$query->method('getCursor')->willReturn(null);
		return $query;
	}
}
