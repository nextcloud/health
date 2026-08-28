<?php

declare(strict_types=1);

namespace OCA\Health\Search;

use DateTime;
use DateTimeImmutable;
use OCA\Health\AppInfo\Application;
use OCA\Health\Db\DailyNote;
use OCA\Health\Db\DailyNoteMapper;
use OCA\Health\Service\ConfigurationService;
use OCP\IDateTimeZone;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud search-provider discovery. */
class DailyNoteSearchProvider implements IProvider {
	private const SNIPPET_LENGTH = 160;

	/** @psalm-suppress PossiblyUnusedMethod Instantiated through Nextcloud search-provider discovery. */
	public function __construct(
		private ConfigurationService $configurationService,
		private DailyNoteMapper $dailyNoteMapper,
		private IDateTimeZone $dateTimeZone,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getId(): string {
		return 'health-daily-notes';
	}

	public function getName(): string {
		return $this->l10n->t('Health');
	}

	public function getOrder(string $route, array $routeParameters): ?int {
		return str_starts_with($route, Application::APP_ID . '.') ? -1 : 20;
	}

	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$userId = $user->getUID();
		if (!$this->configurationService->isDailyNoteSearchEnabled($userId)) {
			return SearchResult::complete($this->getName(), []);
		}

		$tokens = $this->tokens($query->getTerm());
		$limit = max(0, $query->getLimit());
		if ($tokens === [] || $limit === 0) {
			return SearchResult::complete($this->getName(), []);
		}

		$cursor = $query->getCursor();
		$cursor = is_string($cursor) && $this->isDate($cursor) ? $cursor : null;
		$notes = $this->dailyNoteMapper->findMatchingForUser($userId, $tokens, $cursor, $limit + 1);
		$hasNextPage = count($notes) > $limit;
		$notes = array_slice($notes, 0, $limit);
		$entries = array_map(fn (DailyNote $note): SearchResultEntry => $this->entry($note, $tokens), $notes);

		if (!$hasNextPage || $notes === []) {
			return SearchResult::complete($this->getName(), $entries);
		}

		return SearchResult::paginated($this->getName(), $entries, $notes[array_key_last($notes)]->getLocalDate());
	}

	/** @param list<string> $tokens */
	private function entry(DailyNote $note, array $tokens): SearchResultEntry {
		$date = new DateTimeImmutable($note->getLocalDate() . ' 12:00:00', $this->dateTimeZone->getTimeZone(false, $note->getUserId()));
		$localizedDate = (string)$this->l10n->l('date', DateTime::createFromImmutable($date), ['width' => 'full']);
		$icon = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath(Application::APP_ID, 'app.svg'));

		return new SearchResultEntry(
			$icon,
			$this->l10n->t('Daily Note – %s', [$localizedDate]),
			$this->snippet($note->getContent(), $tokens),
			$this->urlGenerator->linkToRouteAbsolute('health.page.journal', ['date' => $note->getLocalDate()]),
			'icon-health',
		);
	}

	/** @return list<string> */
	private function tokens(string $term): array {
		$parts = preg_split('/\s+/u', trim($term));
		return $parts === false ? [] : array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));
	}

	/** @param list<string> $tokens */
	private function snippet(string $content, array $tokens): string {
		$text = trim((string)preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
		if ($text === '') {
			return '';
		}

		$match = 0;
		foreach ($tokens as $token) {
			$position = mb_stripos($text, $token, 0, 'UTF-8');
			if ($position !== false) {
				$match = $position;
				break;
			}
		}

		$length = mb_strlen($text, 'UTF-8');
		if ($length <= self::SNIPPET_LENGTH) {
			return $text;
		}

		$start = max(0, $match - intdiv(self::SNIPPET_LENGTH, 2));
		$end = min($length, $start + self::SNIPPET_LENGTH);
		$start = max(0, $end - self::SNIPPET_LENGTH);
		$fragment = trim(mb_substr($text, $start, $end - $start, 'UTF-8'));

		return ($start > 0 ? '…' : '') . $fragment . ($end < $length ? '…' : '');
	}

	private function isDate(string $value): bool {
		return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $matches) === 1
			&& checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1]);
	}
}
