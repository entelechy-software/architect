<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $definition->title ?? 'Table' }} - Print View</title>
    <style>
        @media print {
            @page {
                margin: 1cm;
            }
            body {
                margin: 0;
                font-size: 12pt;
            }
            .no-print {
                display: none !important;
            }
        }
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            font-size: 18pt;
            margin-bottom: 20px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .print-header {
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .print-date {
            font-size: 10pt;
            color: #666;
        }
        .print-button {
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #206bc4;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .print-button:hover {
            background-color: #1a569d;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10pt;
        }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .badge-info { background-color: #d1ecf1; color: #0c5460; }
        .badge-secondary { background-color: #e2e3e5; color: #383d41; }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">
            🖨️ Print Table
        </button>
        <button class="print-button" onclick="window.close()" style="background-color: #666;">
            ✕ Close
        </button>
    </div>

    <div class="print-header">
        @if ($definition->title)
            <h1>{{ $definition->title }}</h1>
        @endif
        <div class="print-date">
            Generated: {{ now()->format('d M Y, H:i') }} by {{ auth('admin')->user()?->name ?? 'Unknown User' }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ $column->getLabel() }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($columns as $column)
                        @php $value = $row[$column->key()] ?? ''; @endphp
                        <td>
                            @if ($column->isBadge())
                                @php
                                    $key = is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value;
                                    $colour = $column->getColors()[$key] ?? 'secondary';
                                    $badgeClass = match ($colour) {
                                        'success' => 'badge-success',
                                        'warning' => 'badge-warning',
                                        'danger' => 'badge-danger',
                                        'info' => 'badge-info',
                                        default => 'badge-secondary',
                                    };
                                @endphp
                                <span class="arch-badge {{ $badgeClass }}">
                                    {{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}
                                </span>
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" style="text-align: center; color: #666;">
                        No records to display.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        // Auto-trigger print dialog when page loads (for programmatic access)
        if (window.location.hash === '#autoprint') {
            window.onload = () => window.print();
        }
    </script>
</body>
</html>
