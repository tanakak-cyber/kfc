@extends('layouts.admin')

@section('title', '釣果承認')

@section('content')
    <h1 class="kfc-page-title">未承認釣果</h1>
    <p class="kfc-muted mt-2">写真と数値を確認して承認または却下してください。</p>
    <div class="mt-8 space-y-6">
        @forelse ($catches as $catch)
            <div class="kfc-card-sm sm:flex sm:gap-6">
                @php
                    $pendingUrls = $catch->images->map(fn ($im) => asset('storage/'.$im->path))->values()->all();
                @endphp
                @if (count($pendingUrls) > 0)
                    <div class="flex shrink-0 flex-wrap gap-2 sm:max-w-md">
                        @foreach ($catch->images as $idx => $im)
                            @php $pendingImgUrl = asset('storage/'.$im->path); @endphp
                            <img
                                src="{{ $pendingImgUrl }}"
                                alt=""
                                class="h-36 w-36 cursor-pointer rounded-xl object-cover shadow-sm ring-1 ring-zinc-200/80 transition hover:opacity-95 sm:h-40 sm:w-40"
                                role="button"
                                tabindex="0"
                                onclick='window.kfcOpenImageLightbox(@json($pendingUrls), {{ $idx }})'
                                onkeydown='if(event.key==="Enter"||event.key===" "){event.preventDefault();window.kfcOpenImageLightbox(@json($pendingUrls), {{ $idx }});}'
                            >
                        @endforeach
                    </div>
                @endif
                <div class="mt-4 flex-1 text-sm sm:mt-0">
                    <p class="font-semibold text-zinc-900">{{ $catch->player->displayLabel() }}</p>
                    <p class="mt-1 text-zinc-600"><span class="tabular-nums">{{ $catch->length_cm }}</span> cm / <span class="tabular-nums">{{ $catch->weight_kg }}</span> kg</p>
                    <p class="mt-2 text-zinc-500">{{ $catch->gameMatch->title }} — {{ $catch->team?->name ?? '個人戦' }}</p>
                    <p class="text-xs text-zinc-400">{{ $catch->created_at->format('Y/m/d H:i') }}</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <form method="post" action="{{ route('admin.catches.approve', $catch) }}">
                            @csrf
                            <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-950/20 transition hover:bg-emerald-700">承認</button>
                        </form>
                        <form method="post" action="{{ route('admin.catches.reject', $catch) }}">
                            @csrf
                            <button type="submit" class="rounded-xl border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-50">却下</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <p class="kfc-muted">未承認の釣果はありません。</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $catches->links() }}</div>
@endsection
