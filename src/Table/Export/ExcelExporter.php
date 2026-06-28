<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Export;

use Carbon\CarbonImmutable;
use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Permissions\FieldVisibilityFilter;
use Entelechy\Architect\Table\QueryContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Streams a true .xlsx export of a TableBuilder's current view.
 *
 * Mirrors CsvStreamExporter exactly (same QueryContext honouring, same
 * FieldVisibilityFilter, same MAX_ROWS / PAGE_SIZE) — only the output
 * format differs. Uses maatwebsite/excel under the hood.
 *
 * For very large exports (> a few thousand rows) consider routing to a
 * queued job; this exporter buffers all rows in memory.
 */
final class ExcelExporter
{
    public const MAX_ROWS = 5000;

    public const PAGE_SIZE = 500;

    /**
     * @param  array<int, int>|null  $selectedIds
     */
    public function stream(
        ArchitectTableDefinition $definition,
        QueryContext $context,
        ?array $selectedIds,
        ?Authenticatable $user,
    ): BinaryFileResponse {
        $dataModel = app($definition->dataModelClass);
        if (! $dataModel instanceof ArchitectDataModel) {
            throw new \LogicException(
                'TableBuilder export: dataModelClass must implement ArchitectDataModel'
            );
        }

        $visibility = app(FieldVisibilityFilter::class);
        $columns = $visibility->visibleColumns($user, $definition);
        $allowedFlip = $visibility->allowedKeysForRow($columns);
        $selectedFlip = $selectedIds !== null && $selectedIds !== []
            ? array_flip($selectedIds)
            : null;

        // Collect rows in memory (capped at MAX_ROWS).
        $headings = [];
        foreach ($columns as $column) {
            $headings[] = $column->getLabel();
        }

        $rows = [];
        $written = 0;

        foreach (ExportRowIterator::iterate($dataModel, $context, self::MAX_ROWS) as $row) {
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            if ($selectedFlip !== null && ! isset($selectedFlip[$id])) {
                continue;
            }

            $stripped = $visibility->stripRowUsingAllowed($row, $allowedFlip);

            $line = [];
            foreach ($columns as $column) {
                $value = $stripped[$column->getKey()] ?? '';
                if (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                } elseif (is_array($value)) {
                    $value = json_encode($value);
                }
                $line[] = $value;
            }
            $rows[] = $line;
            $written++;
            if ($written >= self::MAX_ROWS) {
                break;
            }
        }

        $sheet = new class($headings, $rows) implements FromCollection, WithHeadings
        {
            /** @var list<string> */
            private array $headings;

            /** @var list<list<mixed>> */
            private array $rows;

            /**
             * @param  list<string>  $headings
             * @param  list<list<mixed>>  $rows
             */
            public function __construct(array $headings, array $rows)
            {
                $this->headings = $headings;
                $this->rows = $rows;
            }

            /** @return list<string> */
            public function headings(): array
            {
                return $this->headings;
            }

            /** @return Collection<int, list<mixed>> */
            public function collection(): Collection
            {
                return collect($this->rows);
            }
        };

        return Excel::download($sheet, self::filename($definition), ExcelType::XLSX);
    }

    private static function filename(ArchitectTableDefinition $definition): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($definition->title ?? 'export')) ?? 'export';
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'export';
        }

        return $slug.'-'.CarbonImmutable::now('UTC')->format('Ymd-His').'.xlsx';
    }
}
