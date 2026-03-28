@extends('layouts.admin')

@section('title', '釣果承認')

@section('content')
    <h1 class="text-2xl font-bold">未承認釣果</h1>
    <div class="mt-6 space-y-6">
        @forelse ($catches as $catch)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row">
                    @php $img = $catch->images->first(); @endphp
                    @if ($img)
                        @php $pendingImgUrl = asset('storage/'.$img->path); @endphp
                        <img
                            src="{{ $pendingImgUrl }}"
                            alt=""
                            class="h-40 w-full cursor-pointer rounded-lg object-cover hover:opacity-95 sm:w-56"
                            role="button"
                            tabindex="0"
                            onclick="window.kfcOpenImageLightbox({{ json_encode($pendingImgUrl) }})"
                            onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.kfcOpenImageLightbox({{ json_encode($pendingImgUrl) }});}"
                        >
                    @endif
                    <div class="flex-1 text-sm">
                        <p class="font-semibold">{{ $catch->player->displayLabel() }}</p>
                        <p class="text-slate-600">{{ $catch->length_cm }} cm / {{ $catch->weight_kg }} kg</p>
                        <p class="mt-1 text-slate-500">{{ $catch->gameMatch->title }} — {{ $catch->team->name }}</p>
                        <p class="text-xs text-slate-400">{{ $catch->created_at->format('Y/m/d H:i') }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <form method="post" action="{{ route('admin.catches.approve', $catch) }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-emerald-700 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-800">承認</button>
                            </form>
                            <form method="post" action="{{ route('admin.catches.reject', $catch) }}">
                                @csrf
                                <button type="submit" class="rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50">却下</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">未承認の釣果はありません。</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $catches->links() }}</div>
@endsection
