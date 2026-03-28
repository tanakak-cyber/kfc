@extends('layouts.admin')

@section('title', '出欠アンケート')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="kfc-page-title">出欠アンケート</h1>
            <p class="kfc-muted mt-1">仮試合の日程・フィールド希望を集計し、確定時に本番の試合を自動作成します。</p>
        </div>
        <a href="{{ route('admin.match-surveys.create') }}" class="kfc-btn-primary">新規作成</a>
    </div>

    <div class="kfc-table-shell mt-8 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="kfc-thead">
                <tr>
                    <th class="px-4 py-3">タイトル</th>
                    <th class="px-4 py-3">シーズン</th>
                    <th class="px-4 py-3">状態</th>
                    <th class="px-4 py-3">回答URL</th>
                    <th class="px-4 py-3 text-right">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($surveys as $s)
                    <tr class="kfc-trow">
                        <td class="px-4 py-3 font-medium text-zinc-900">{{ $s->title ?: '（無題）' }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $s->season->name }}</td>
                        <td class="px-4 py-3">
                            <span class="kfc-badge">{{ $s->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('survey.show', $s->survey_token) }}" class="kfc-link break-all text-xs" target="_blank" rel="noopener noreferrer">{{ url('/survey/'.$s->survey_token) }}</a>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.match-surveys.show', $s) }}" class="kfc-link">詳細</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 kfc-muted">アンケートはまだありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $surveys->links() }}</div>
@endsection
