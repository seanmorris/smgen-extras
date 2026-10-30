<?php

declare(strict_types=1);

namespace SmgenTimeline;

use DateTimeImmutable;
use RuntimeException;

/**
 * Compiles dated pages into a year, month, and day structure and renders the archive widget.
 */
final class Timeline
{
	/** Category name the prepare hook uses for day rollup pages. */
	public const DAY_CATEGORY = 'timeline-day';

	/**
	 * Compile one timeline menu from its resolved links.
	 *
	 * @param array<string, mixed> $menu Menu definition from SMGen.
	 * @param list<array<string, mixed>> $links Resolved menu links.
	 * @param array<string, mixed> $catalog Page catalog with pages keyed by source path.
	 * @param array<string, mixed> $context Compile context with a stampCreated callback.
	 * @return array<string, mixed> The menu with a `timeline` structure attached.
	 */
	public static function compile(array $menu, array $links, array $catalog, array $context): array
	{
		$pages = [];
		foreach($links as $link)
		{
			$source = $link['source'] ?? null;
			if(($link['type'] ?? null) !== 'page' || !is_string($source) || !isset($catalog['pages'][$source]))
			{
				throw new RuntimeException('Timeline menu ' . ($menu['name'] ?? '') . ' may contain only content pages.');
			}
			$date = Dates::resolve($catalog['pages'][$source], $context['stampCreated'] ?? null);
			$pages[] = [
				'id' => (string) $link['id'],
				'title' => (string) $link['title'],
				'path' => (string) $link['path'],
				'date' => $date['date'],
				'timestamp' => $date['timestamp'],
			];
		}
		$menu['timeline'] = self::structure($pages, self::rollups($catalog));
		return $menu;
	}

	/** Return the public stylesheet the widget needs when it has content; the script comes from the manifest. */
	public static function assets(array $menu): array
	{
		return empty($menu['timeline']['years']) ? [] : ['/smgen-timeline/timeline.css'];
	}

	/**
	 * Group pages newest first into years, months, and days.
	 *
	 * @param list<array<string, mixed>> $pages Pages with date and timestamp.
	 * @param array<string, string> $rollups Day rollup public paths keyed by calendar day.
	 * @return array{years: list<array<string, mixed>>} Nested structure for rendering.
	 */
	public static function structure(array $pages, array $rollups = []): array
	{
		usort($pages, static function(array $a, array $b): int {
			if($a['timestamp'] === $b['timestamp'])
			{
				return strcmp($a['path'], $b['path']);
			}
			return $b['timestamp'] <=> $a['timestamp'];
		});
		$years = [];
		foreach($pages as $page)
		{
			$year = substr($page['date'], 0, 4);
			$month = substr($page['date'], 0, 7);
			if(!isset($years[$year]))
			{
				$years[$year] = ['year' => $year, 'count' => 0, 'months' => []];
			}
			if(!isset($years[$year]['months'][$month]))
			{
				$date = DateTimeImmutable::createFromFormat('!Y-m', $month);
				$years[$year]['months'][$month] = [
					'month' => $month,
					'name' => $date === false ? $month : $date->format('F'),
					'count' => 0,
					'pages' => [],
					'days' => [],
				];
			}
			$day = $page['date'];
			unset($page['timestamp']);
			$years[$year]['count']++;
			$years[$year]['months'][$month]['count']++;
			$years[$year]['months'][$month]['pages'][] = $page;
			if(!isset($years[$year]['months'][$month]['days'][$day]))
			{
				$years[$year]['months'][$month]['days'][$day] = ['count' => 0, 'path' => $page['path']];
			}
			$years[$year]['months'][$month]['days'][$day]['count']++;
		}
		foreach($years as &$year)
		{
			foreach($year['months'] as &$month)
			{
				foreach($month['days'] as $day => &$entry)
				{
					if($entry['count'] > 1 && isset($rollups[$day]))
					{
						$entry['path'] = $rollups[$day];
						$entry['rollup'] = true;
					}
				}
				unset($entry);
			}
			unset($month);
			$year['months'] = array_values($year['months']);
		}
		unset($year);
		return ['years' => array_values($years)];
	}

	/**
	 * Find day rollup pages the prepare hook generated, keyed by calendar day.
	 *
	 * @param array<string, mixed> $catalog Page catalog.
	 * @return array<string, string> Public paths keyed by `Y-m-d`.
	 */
	public static function rollups(array $catalog): array
	{
		$rollups = [];
		foreach($catalog['pages'] ?? [] as $page)
		{
			$category = $page['metadata']['category'] ?? null;
			if(is_array($category) && ($category['name'] ?? null) === self::DAY_CATEGORY && is_string($category['value'] ?? null))
			{
				$rollups[$category['value']] = (string) $page['path'];
			}
		}
		return $rollups;
	}

	/**
	 * Render the archive widget: a month calendar and expandable year and month lists.
	 *
	 * @param array<string, mixed> $menu Compiled menu.
	 * @param string $idPath Region id path used for details identifiers.
	 * @param array<string, mixed> $context Render context with the current page path.
	 */
	public static function render(array $menu, string $idPath, array $context): void
	{
		$years = $menu['timeline']['years'] ?? [];
		if(!$years)
		{
			return;
		}
		$currentPath = (string) ($context['currentPage'] ?? '');
		$hasCurrentPage = false;
		foreach($years as $year)
		{
			$hasCurrentPage = $hasCurrentPage || self::yearContainsPath($year, $currentPath);
		}
		$prefix = $idPath === '' ? 'archive' : $idPath . '-archive';
		$title = (string) ($menu['label'] ?? 'Archive');

		?><section class="navigation-widget archive-widget" aria-label="<?=self::escape($title);?>">
		<h2 class="navigation-widget-title"><?=self::escape($title);?></h2>
		<?php self::renderCalendar(self::months($years), $currentPath, (string) ($menu['name'] ?? 'archive')); ?>
		<ul class="archive-timeline"><?php foreach($years as $yearIndex => $year):
			$yearCurrent = self::yearContainsPath($year, $currentPath);
			$yearOpen = $yearCurrent || (!$hasCurrentPage && $yearIndex === 0);
			$yearId = $prefix . '-' . $year['year'];
			?><li><details <?=$yearOpen ? 'open' : '';?> data-id-path="<?=self::escape($yearId);?>"<?=$yearCurrent ? ' data-contains-current="true"' : '';?>>
				<summary><span><?=self::escape($year['year']);?></span> <span class="archive-count">(<?=self::escape((string) $year['count']);?>)</span></summary>
				<ul><?php foreach($year['months'] ?? [] as $monthIndex => $month):
					$monthCurrent = self::monthContainsPath($month, $currentPath);
					$monthOpen = $monthCurrent || (!$hasCurrentPage && $yearIndex === 0 && $monthIndex === 0);
					$monthId = $yearId . '-' . substr($month['month'], 5, 2);
					?><li><details <?=$monthOpen ? 'open' : '';?> data-id-path="<?=self::escape($monthId);?>"<?=$monthCurrent ? ' data-contains-current="true"' : '';?>>
						<summary><span><?=self::escape($month['name']);?></span> <span class="archive-count">(<?=self::escape((string) $month['count']);?>)</span></summary>
						<ul><?php foreach($month['pages'] ?? [] as $page): ?>
							<li><a href="<?=self::escape(self::url($page['path']));?>" <?=($page['path'] ?? '') === $currentPath ? 'class="active-link" aria-current="page"' : '';?>><time datetime="<?=self::escape($page['date']);?>"><?=self::escape((string) (int) substr($page['date'], 8, 2));?></time><span><?=self::escape($page['title']);?></span></a></li>
						<?php endforeach; ?></ul>
					</details></li>
				<?php endforeach; ?></ul>
			</details></li>
		<?php endforeach; ?></ul>
	</section><?php
	}

	/** Public path of the calendar partial for one month of one menu. */
	public static function partialPath(string $menuName, string $month): string
	{
		return '/smgen-timeline/calendar/' . $menuName . '/' . $month . '.html';
	}

	/**
	 * Render every month's calendar partial for a compiled menu.
	 *
	 * The page embeds only one month; the arrows and year chips fetch these partials
	 * and swap them in place, so a long archive costs each page a single month.
	 *
	 * @param array<string, mixed> $menu Compiled menu with a `timeline` structure.
	 * @return array<string, string> Partial HTML keyed by public path.
	 */
	public static function partials(array $menu): array
	{
		$months = self::months($menu['timeline']['years'] ?? []);
		$menuName = (string) ($menu['name'] ?? 'archive');
		$partials = [];
		foreach($months as $index => $month)
		{
			$partials[self::partialPath($menuName, $month['month'])] = self::capture(static function() use ($months, $index, $menuName): void {
				self::renderCalendarMonth($months, $index, '', $menuName);
			});
		}
		return $partials;
	}

	/**
	 * Render the calendar for the month holding the current page, or the newest month.
	 *
	 * @param list<array<string, mixed>> $months Months newest first.
	 */
	private static function renderCalendar(array $months, string $currentPath, string $menuName): void
	{
		if(!$months)
		{
			return;
		}
		$activeIndex = 0;
		foreach($months as $index => $month)
		{
			if(self::monthContainsPath($month, $currentPath))
			{
				$activeIndex = $index;
				break;
			}
		}
		?><div class="archive-calendar" data-calendar-menu="<?=self::escape($menuName);?>"><?php self::renderCalendarMonth($months, $activeIndex, $currentPath, $menuName); ?></div><?php
	}

	/**
	 * Render one month: heading with step links, the day grid, and year chips.
	 *
	 * Step links and year chips point at a page in the target month for visitors
	 * without JavaScript and carry the partial to fetch for everyone else.
	 *
	 * @param list<array<string, mixed>> $months Months newest first.
	 */
	private static function renderCalendarMonth(array $months, int $index, string $currentPath, string $menuName): void
	{
		$month = $months[$index];
		$monthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $month['month'] . '-01');
		if($monthDate === false)
		{
			return;
		}
		$daysInMonth = (int) $monthDate->format('t');
		$startOffset = (int) $monthDate->format('w');
		$weeks = (int) ceil(($startOffset + $daysInMonth) / 7);
		$pagesByDay = [];
		foreach($month['pages'] ?? [] as $page)
		{
			$pagesByDay[(int) substr($page['date'], 8, 2)][] = $page;
		}
		$weekdays = [['Sunday', 'Su'], ['Monday', 'Mo'], ['Tuesday', 'Tu'], ['Wednesday', 'We'], ['Thursday', 'Th'], ['Friday', 'Fr'], ['Saturday', 'Sa']];
		$heading = $month['name'] . ' ' . substr($month['month'], 0, 4);
		$older = $months[$index + 1] ?? null;
		$newer = $months[$index - 1] ?? null;
		$newestByYear = [];
		foreach($months as $candidate)
		{
			$newestByYear[substr($candidate['month'], 0, 4)] ??= $candidate;
		}
		?>
		<div class="archive-calendar-heading">
			<?php if($older): ?><?php self::renderCalendarStep($older, 'prev', '&#8249;', $menuName); ?><?php else: ?><span aria-hidden="true"></span><?php endif; ?>
			<strong aria-live="polite"><?=self::escape($heading);?></strong>
			<?php if($newer): ?><?php self::renderCalendarStep($newer, 'next', '&#8250;', $menuName); ?><?php else: ?><span aria-hidden="true"></span><?php endif; ?>
		</div>
		<table>
			<caption class="visually-hidden"><?=self::escape($heading);?> publication calendar</caption>
			<thead><tr><?php foreach($weekdays as [$name, $short]): ?><th scope="col"><abbr title="<?=self::escape($name);?>"><?=self::escape($short);?></abbr></th><?php endforeach; ?></tr></thead>
			<tbody><?php
			for($week = 0; $week < $weeks; $week++)
			{
				?><tr><?php
				for($weekday = 0; $weekday < 7; $weekday++)
				{
					$day = ($week * 7) + $weekday - $startOffset + 1;
					if($day < 1 || $day > $daysInMonth)
					{
						?><td aria-hidden="true"></td><?php
						continue;
					}
					$dayPages = $pagesByDay[$day] ?? [];
					if(!$dayPages)
					{
						?><td><span><?=self::escape((string) $day);?></span></td><?php
						continue;
					}
					$count = count($dayPages);
					$dayKey = $month['month'] . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
					$entry = $month['days'][$dayKey] ?? ['path' => $dayPages[0]['path'], 'count' => $count];
					$label = $monthDate->setDate((int) $monthDate->format('Y'), (int) $monthDate->format('m'), $day)->format('F j, Y');
					$label .= ': ' . $count . ($count === 1 ? ' post' : ' posts');
					$isCurrent = $entry['path'] === $currentPath;
					foreach($dayPages as $page)
					{
						$isCurrent = $isCurrent || ($page['path'] ?? '') === $currentPath;
					}
					?><td class="has-posts<?=!empty($entry['rollup']) ? ' has-rollup' : '';?>"><a href="<?=self::escape(self::url($entry['path']));?>" aria-label="<?=self::escape($label);?>" <?=$isCurrent ? 'aria-current="date"' : '';?>><?=self::escape((string) $day);?><?php if($count > 1): ?><span class="archive-day-count" aria-hidden="true"><?=self::escape((string) $count);?></span><?php endif; ?></a></td><?php
				}
				?></tr><?php
			}
			?></tbody>
		</table>
		<?php if(count($newestByYear) > 1): ?>
		<p class="archive-calendar-years" aria-label="Jump to a year"><?php foreach($newestByYear as $year => $newest):
			$isShown = substr($month['month'], 0, 4) === (string) $year;
			?><a href="<?=self::escape(self::url(self::monthEntryPath($newest)));?>" data-calendar="<?=self::escape(self::url(self::partialPath($menuName, $newest['month'])));?>" aria-label="Show <?=self::escape($newest['name'] . ' ' . $year);?>"<?=$isShown ? ' aria-current="true"' : '';?>><?=self::escape((string) $year);?></a><?php endforeach; ?></p>
		<?php endif; ?>
<?php
	}

	/** Render a previous or next month step: a link into that month carrying its partial. */
	private static function renderCalendarStep(array $target, string $rel, string $glyph, string $menuName): void
	{
		$label = 'Show ' . $target['name'] . ' ' . substr($target['month'], 0, 4);
		?><a rel="<?=$rel;?>" class="archive-calendar-step" href="<?=self::escape(self::url(self::monthEntryPath($target)));?>" data-calendar="<?=self::escape(self::url(self::partialPath($menuName, $target['month'])));?>" aria-label="<?=self::escape($label);?>"><?=$glyph;?></a><?php
	}

	/** Public path a month's step link falls back to: its newest day, post or rollup. */
	private static function monthEntryPath(array $month): string
	{
		foreach($month['days'] ?? [] as $day)
		{
			if(isset($day['path']))
			{
				return (string) $day['path'];
			}
		}
		return (string) ($month['pages'][0]['path'] ?? '/');
	}

	/** Run a renderer and return what it printed. */
	private static function capture(callable $callback): string
	{
		ob_start();
		try
		{
			$callback();
		}
		finally
		{
			$html = (string) ob_get_clean();
		}
		return $html;
	}

	/** Flatten the years into one newest-first month list. */
	private static function months(array $years): array
	{
		$months = [];
		foreach($years as $year)
		{
			foreach($year['months'] ?? [] as $month)
			{
				$months[] = $month;
			}
		}
		return $months;
	}

	/** Report whether a month holds the current page. */
	private static function monthContainsPath(array $month, string $path): bool
	{
		foreach($month['pages'] ?? [] as $page)
		{
			if(($page['path'] ?? '') === $path)
			{
				return true;
			}
		}
		foreach($month['days'] ?? [] as $day)
		{
			if(($day['path'] ?? '') === $path)
			{
				return true;
			}
		}
		return false;
	}

	/** Report whether a year holds the current page. */
	private static function yearContainsPath(array $year, string $path): bool
	{
		foreach($year['months'] ?? [] as $month)
		{
			if(self::monthContainsPath($month, $path))
			{
				return true;
			}
		}
		return false;
	}

	/** Site base URL used when SMGen's asset helper is not loaded, as in build hooks. */
	private static string $baseUrl = '';

	/** Set the base URL for links rendered outside SMGen templates. */
	public static function setBaseUrl(string $baseUrl): void
	{
		self::$baseUrl = rtrim($baseUrl, '/');
	}

	/** Resolve a public path through SMGen's asset helper when it is loaded, else the base URL. */
	private static function url(string $path): string
	{
		if(function_exists('resolveAssetUrl'))
		{
			return (string) resolveAssetUrl($path);
		}
		return self::$baseUrl . '/' . ltrim($path, '/');
	}

	/** Escape text for HTML output. */
	private static function escape(string $value): string
	{
		return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
}
