{{--
    Dispatcher for an ArchitectEntry: resolves its declared view and
    passes it the entry plus the record it should resolve values from.

    Variables:
        $item    Entelechy\Architect\Content\Contracts\ArchitectEntry
        $record  mixed — the Eloquent model/array this entry reads from
--}}
@include($item->getViewName(), ['entry' => $item, 'record' => $record])
