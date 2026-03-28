<?php

namespace App\Http\Controllers;

use App\Enums\SurveyDateAnswerStatus;
use App\Enums\SurveyStatus;
use App\Models\MatchSurvey;
use App\Models\MatchSurveyAnswer;
use App\Models\MatchSurveyDateAnswer;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SurveyController extends Controller
{
    public function show(string $token): View
    {
        $survey = MatchSurvey::query()
            ->where('survey_token', $token)
            ->with(['season', 'dates', 'fields'])
            ->firstOrFail();

        $players = Player::query()->orderBy('name')->get();

        return view('survey.show', compact('survey', 'players'));
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $survey = MatchSurvey::query()
            ->where('survey_token', $token)
            ->with(['dates', 'fields'])
            ->firstOrFail();

        if ($survey->status !== SurveyStatus::Open) {
            return back()->withErrors(['survey' => 'このアンケートは現在回答を受け付けていません。']);
        }

        $dateIds = $survey->dates->pluck('id')->map(fn ($id) => (string) $id)->all();

        $rules = [
            'player_id' => ['required', 'exists:players,id'],
            'selected_field_id' => [
                'required',
                'integer',
                Rule::exists('match_survey_fields', 'id')->where('survey_id', $survey->id),
            ],
            'date_statuses' => ['required', 'array'],
        ];

        foreach ($survey->dates as $d) {
            $rules['date_statuses.'.$d->id] = ['required', Rule::in(['yes', 'no'])];
        }

        $validated = $request->validate($rules);

        foreach ($dateIds as $did) {
            if (! array_key_exists($did, $validated['date_statuses'])) {
                return back()->withErrors(['date_statuses' => 'すべての候補日について ○ または × を選択してください。'])->withInput();
            }
        }

        $answer = MatchSurveyAnswer::query()->updateOrCreate(
            [
                'survey_id' => $survey->id,
                'player_id' => (int) $validated['player_id'],
            ],
            [
                'selected_field_id' => (int) $validated['selected_field_id'],
            ]
        );

        $answer->dateAnswers()->delete();

        foreach ($validated['date_statuses'] as $dateId => $raw) {
            $status = $raw === 'yes' ? SurveyDateAnswerStatus::Yes : SurveyDateAnswerStatus::No;
            MatchSurveyDateAnswer::query()->create([
                'answer_id' => $answer->id,
                'date_id' => (int) $dateId,
                'status' => $status,
            ]);
        }

        return back()->with('status', '回答を保存しました。変更する場合は同じ選手で再度送信すると上書きされます。');
    }
}
