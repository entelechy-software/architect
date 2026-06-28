{{--
    Table shell — anonymous component wrapper around the engine card.

    Exists as an extension point: override this file (publish the package's
    views) to customise how every table is wrapped — a page-level container,
    extra toolbar slots, analytics hooks — without touching the engine itself.

    Default: pass-through — renders $slot unchanged.
--}}
{{ $slot }}
