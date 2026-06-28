{{--
    Wraps a form input and adds an error ring when :valid="false".

    Distinct from <x-architect::field-wrapper> (Forms\Fields\* — renders the
    full label/hint/error block around a field). This is the smaller
    primitive used directly inside Table field partials, which render their
    own label markup separately.
--}}
@props(['valid' => true])
<div {{ $attributes->class([
    'arch-input-wrapper',
]) }} data-invalid="{{ $valid ? 'false' : 'true' }}">{{ $slot }}</div>
