@props([
    'positionClass' => '',
    'wrap' => true,
])

@if ($wrap)
    <div {{ $attributes->class(['arch-card']) }}>
        <div @class([
            'arch-navigator-shell',
            $positionClass,
        ])>
            {{ $slot }}
        </div>
    </div>
@else
    <div @class([
        'arch-navigator-shell',
        $positionClass,
    ])>
        {{ $slot }}
    </div>
@endif
