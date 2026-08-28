<?php

declare(strict_types = 1);

/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use pool\classes\Core\Input\Input;
use pool\classes\Database\Operator;
use pool\classes\Exception\LogicException;
use pool\guis\GUI_Table\GUI_Table;

final class GUI_TableTimezoneTest extends TestCase
{
    #[Test]
    public function tableFiltersUseTheConfiguredTimezones(): void
    {
        $table = new GUI_TableTimezoneStub();
        $table->setDateTimezones('Europe/Berlin', 'America/New_York');

        [$remainingFilter, $dateFilters] = $table->extractDateFilters(
            '{"category":"Example","updatedDateTime":"2026-07-20"}',
        );

        $this->assertSame('{"category":"Example"}', $remainingFilter);
        $this->assertSame([
            ['Example.updatedDateTime', Operator::greaterEqual, '2026-07-20T06:00:00+02:00'],
            ['Example.updatedDateTime', Operator::less, '2026-07-21T06:00:00+02:00'],
        ], array_map(
            static fn(array $filter): array => [$filter[0], $filter[1], $filter[2]->format(DATE_ATOM)],
            $dateFilters,
        ));
    }

    #[Test]
    public function explicitDateTimeRangeFilterUsesTheConfiguredTimezones(): void
    {
        $table = new GUI_TableTimezoneStub();
        $table->setDateTimezones('Europe/Berlin', 'America/New_York');

        $dateFilters = $table->buildDateTimeRangeFilter('createdDateTime', '2026-03-08', '2026-03-08');

        $this->assertSame([
            ['Example.createdDateTime', Operator::greaterEqual, '2026-03-08T06:00:00+01:00'],
            ['Example.createdDateTime', Operator::less, '2026-03-09T05:00:00+01:00'],
        ], array_map(
            static fn(array $filter): array => [$filter[0], $filter[1], $filter[2]->format(DATE_ATOM)],
            $dateFilters,
        ));
    }

    #[Test]
    public function explicitDateTimeRangeFilterRequiresConfiguredTimezones(): void
    {
        $table = new GUI_TableTimezoneStub();

        $this->expectException(LogicException::class);
        $table->buildDateTimeRangeFilter('createdDateTime', '2026-03-08', '2026-03-08');
    }

    #[Test]
    public function emptyExplicitDateTimeRangeDoesNotRequireConfiguredTimezones(): void
    {
        $table = new GUI_TableTimezoneStub();

        $this->assertSame([], $table->buildDateTimeRangeFilter('createdDateTime', null, ''));
    }

    #[Test]
    public function explicitDateTimeRangeRejectsUnknownOrNonDateTimeFields(): void
    {
        $table = new GUI_TableTimezoneStub();
        $table->setDateTimezones('Europe/Berlin', 'America/New_York');

        $this->expectException(InvalidArgumentException::class);
        $table->buildDateTimeRangeFilter('category', '2026-03-08', '2026-03-08');
    }

    #[Test]
    public function invalidDateIsRejected(): void
    {
        $table = new GUI_TableTimezoneStub();
        $table->setDateTimezones('Europe/Berlin', 'Europe/Berlin');

        $this->expectException(InvalidArgumentException::class);
        $table->extractDateFilters('{"updatedDateTime":"0"}');
    }

    #[Test]
    public function invalidTimezoneIsRejectedWhenEnablingTheFeature(): void
    {
        $table = new GUI_TableTimezoneStub();

        $this->expectException(InvalidArgumentException::class);
        $table->setDateTimezones('Europe/Berlin', 'invalid');
    }

    #[Test]
    public function dateFiltersRemainUnchangedWithoutConfiguredTimezones(): void
    {
        $table = new GUI_TableTimezoneStub();

        $this->assertSame(
            ['{"updatedDateTime":"2026-07-20"}', []],
            $table->extractDateFilters('{"updatedDateTime":"2026-07-20"}'),
        );
    }
}

class GUI_TableTimezoneStub extends GUI_Table
{
    public function __construct()
    {
        $this->Input = new Input();
        $this->Input->setVars(['columns' => [
            [
                'field' => 'category',
                'dbColumn' => 'Example.category',
                'searchable' => true,
            ],
            [
                'field' => 'updatedDateTime',
                'dbColumn' => 'Example.updatedDateTime',
                'poolType' => 'date.time',
                'filterControl' => 'datepicker',
                'searchable' => true,
            ],
            [
                'field' => 'createdDateTime',
                'dbColumn' => 'Example.createdDateTime',
                'poolType' => 'date.time',
                'filterControl' => 'datepicker',
                'searchable' => true,
            ],
        ]]);
    }

    public function extractDateFilters(string $filter): array
    {
        return $this->extractTimezoneTableDateFilters($filter);
    }
}
