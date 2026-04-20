{{-- CatchSectionsByRank の $section（heading / rank / fallback_flat）用。順位があればメダル（1〜3）または番号（4以下）＋名称 --}}
@props([
    'section' => [],
    'headingTag' => 'h3',
])
@php
    $tag = in_array($headingTag, ['h3', 'h4'], true) ? $headingTag : 'h3';
    $rankNum = $section['rank'] ?? null;
    $fullHeading = (string) ($section['heading'] ?? '');
    $titleBody = $fullHeading;
    if ($rankNum !== null && $fullHeading !== '') {
        $prefix = $rankNum.'位 ';
        if (str_starts_with($fullHeading, $prefix)) {
            $titleBody = substr($fullHeading, strlen($prefix));
        }
    }
@endphp
@if (! ($section['fallback_flat'] ?? false))
    <{{ $tag }} class="kfc-heading-4 flex flex-wrap items-center gap-2 sm:gap-3">
        @if ($rankNum !== null)
            <span class="inline-flex shrink-0 items-center self-center" aria-hidden="true">
                <x-rank-medal :rank="$rankNum" />
            </span>
        @endif
        <span class="min-w-0 flex-1 break-words">{{ $rankNum !== null ? $titleBody : $fullHeading }}</span>
    </{{ $tag }}>
@endif
