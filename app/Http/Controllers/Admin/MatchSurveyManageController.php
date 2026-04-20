<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MatchType;
use App\Enums\SurveyDateAnswerStatus;
use App\Enums\SurveyStatus;
use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\MatchParticipant;
use App\Models\MatchSurvey;
use App\Models\MatchSurveyDate;
use App\Models\MatchSurveyField;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Services\AutoTeamBuilderService;
use App\Services\MatchResultSyncService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MatchSurveyManageController extends Controller
{
    public function __construct(
        private MatchResultSyncService $matchResults,
        private AutoTeamBuilderService $autoTeamBuilder
    ) {}

    public function index(): View
    {
        $surveys = MatchSurvey::query()
            ->with('season')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.match_surveys.index', compact('surveys'));
    }

    public function create(): View
    {
        $seasons = Season::query()->orderByDesc('starts_on')->get();

        return view('admin.match_surveys.create', compact('seasons'));
    }

    public function store(Request $request): RedirectResponse
    {
        $dates = array_values(array_filter($request->input('dates', []), fn ($d) => filled($d)));
        $fieldNames = array_values(array_filter(
            array_map('trim', $request->input('field_names', [])),
            fn ($n) => $n !== ''
        ));
        $request->merge(['dates' => $dates, 'field_names' => $fieldNames]);

        $validated = $request->validate([
            'season_id' => ['required', 'exists:seasons,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'dates' => ['required', 'array', 'min:1'],
            'dates.*' => ['required', 'date'],
            'field_names' => ['required', 'array', 'min:1'],
            'field_names.*' => ['required', 'string', 'max:255'],
        ], [
    'dates.required' => '日付を入力してください',
    'dates.*.required' => '日付を入力してください',
    'field_names.required' => '項目名を入力してください',
    'field_names.*.required' => '項目名を入力してください',
]);

        $survey = DB::transaction(function () use ($validated): MatchSurvey {
            $survey = MatchSurvey::query()->create([
                'season_id' => (int) $validated['season_id'],
                'title' => $validated['title'] ?: null,
                'description' => $validated['description'] ?: null,
                'survey_token' => Str::random(32),
                'status' => SurveyStatus::Open,
            ]);

            foreach ($validated['dates'] as $onDate) {
                $survey->dates()->create(['on_date' => $onDate]);
            }

            foreach ($validated['field_names'] as $name) {
                $survey->fields()->create(['field_name' => $name]);
            }

            return $survey;
        });

        return redirect()
            ->route('admin.match-surveys.show', $survey)
            ->with('status', 'アンケートを作成しました。');
    }

    public function show(MatchSurvey $match_survey): View
    {
        $survey = $match_survey->load([
            'season',
            'dates',
            'fields',
            'createdMatch',
            'answers.player',
            'answers.selectedField',
            'answers.dateAnswers.surveyDate',
        ]);

        $dateYesCounts = [];
        foreach ($survey->dates as $d) {
            $dateYesCounts[$d->id] = $d->dateAnswers()
                ->where('status', SurveyDateAnswerStatus::Yes)
                ->count();
        }

        $fieldPickCounts = [];
        foreach ($survey->fields as $f) {
            $fieldPickCounts[$f->id] = $survey->answers()
                ->where('selected_field_id', $f->id)
                ->count();
        }

        $playersWithYesForDate = [];
        foreach ($survey->dates as $d) {
            $answerIds = $d->dateAnswers()
                ->where('status', SurveyDateAnswerStatus::Yes)
                ->pluck('answer_id');
            $playersWithYesForDate[$d->id] = $survey->answers()
                ->whereIn('id', $answerIds)
                ->pluck('player_id')
                ->unique()
                ->values()
                ->all();
        }

        $allPlayers = Player::query()->orderBy('name')->get();

        return view('admin.match_surveys.show', compact(
            'survey',
            'dateYesCounts',
            'fieldPickCounts',
            'playersWithYesForDate',
            'allPlayers'
        ));
    }

    public function destroy(MatchSurvey $match_survey): RedirectResponse
    {
        $match_survey->delete();

        return redirect()
            ->route('admin.match-surveys.index')
            ->with('status', 'アンケートを削除しました。');
    }

    public function close(MatchSurvey $match_survey): RedirectResponse
    {
        $survey = $match_survey;

        if ($survey->status === SurveyStatus::Finalized) {
            return back()->withErrors(['survey' => '確定済みのアンケートは変更できません。']);
        }

        $survey->update(['status' => SurveyStatus::Closed]);

        return back()->with('status', 'アンケートを「受付終了」にしました。');
    }

    public function reopen(MatchSurvey $match_survey): RedirectResponse
    {
        $survey = $match_survey;

        if ($survey->status === SurveyStatus::Finalized) {
            return back()->withErrors(['survey' => '確定済みのアンケートは再開できません。']);
        }

        $survey->update(['status' => SurveyStatus::Open]);

        return back()->with('status', 'アンケートを「受付中」に戻しました。');
    }

    public function finalize(Request $request, MatchSurvey $match_survey): RedirectResponse
    {
        $survey = $match_survey;

        $validated = $request->validate([
            'match_survey_date_id' => [
                'required',
                'integer',
                Rule::exists('match_survey_dates', 'id')->where('survey_id', $survey->id),
            ],
            'match_survey_field_id' => [
                'required',
                'integer',
                Rule::exists('match_survey_fields', 'id')->where('survey_id', $survey->id),
            ],
            'match_type' => ['required', Rule::in(array_map(fn (MatchType $t) => $t->value, MatchType::cases()))],
            'match_title' => ['required', 'string', 'max:255'],
            'held_time' => ['required', 'date_format:H:i'],
            'player_ids' => ['required', 'array', 'min:1'],
            'player_ids.*' => ['integer', 'exists:players,id'],
            'auto_form_teams' => ['sometimes', 'boolean'],
        ]);

        $dateRow = MatchSurveyDate::query()
            ->where('survey_id', $survey->id)
            ->where('id', $validated['match_survey_date_id'])
            ->firstOrFail();

        $fieldRow = MatchSurveyField::query()
            ->where('survey_id', $survey->id)
            ->where('id', $validated['match_survey_field_id'])
            ->firstOrFail();

        $heldAt = Carbon::parse($dateRow->on_date->format('Y-m-d').' '.$validated['held_time'].':00');

        $matchType = MatchType::from($validated['match_type']);
        $playerIds = array_values(array_unique(array_map('intval', $validated['player_ids'])));
        $autoFormTeams = $matchType === MatchType::Team && $request->boolean('auto_form_teams');
        $hadCreatedMatch = $survey->created_match_id !== null;

        $match = DB::transaction(function () use ($survey, $fieldRow, $heldAt, $matchType, $validated, $playerIds, $autoFormTeams): GameMatch {
            $match = GameMatch::query()->create([
                'season_id' => $survey->season_id,
                'match_type' => $matchType,
                'title' => $validated['match_title'],
                'start_datetime' => $heldAt,
                'end_datetime' => null,
                'field' => $fieldRow->field_name,
                'launch_shop' => null,
                'rules' => null,
                'is_finalized' => false,
            ]);

            if ($matchType === MatchType::Individual) {
                foreach ($playerIds as $pid) {
                    MatchParticipant::query()->create([
                        'match_id' => $match->id,
                        'player_id' => $pid,
                        'is_present' => true,
                        'entry_token' => Str::random(32),
                    ]);
                }
            }

            if ($matchType === MatchType::Team && $autoFormTeams) {
                $players = Player::query()->whereIn('id', $playerIds)->get();
                $sorted = $this->autoTeamBuilder->orderPlayersBySeasonStanding($players, (int) $survey->season_id);
                $groups = $this->autoTeamBuilder->pairPlayersIntoTeams($sorted);

                foreach ($groups as $index => $memberIds) {
                    $team = Team::query()->create([
                        'match_id' => $match->id,
                        'name' => 'チーム '.($index + 1),
                        'entry_token' => Str::random(32),
                    ]);
                    $team->players()->sync($memberIds);
                }
            }

            $survey->update([
                'status' => SurveyStatus::Finalized,
                'created_match_id' => $match->id,
            ]);

            return $match;
        });

        $this->matchResults->syncMatch($match, true);

        $suffix = $hadCreatedMatch ? ' 以前に作成した試合は試合一覧にそのまま残っています。' : '';

        if ($matchType === MatchType::Team && $autoFormTeams) {
            $message = '試合を作成し、成績順に基づきチームを自動編成しました。編集画面からチーム名・メンバーを調整できます。'.$suffix;
        } elseif ($matchType === MatchType::Team) {
            $message = '試合を作成しました。チーム戦のため、試合編集からチームを登録してください。'.$suffix;
        } else {
            $message = '試合を作成し、出席者分の参加者・投稿URLを発行しました。'.$suffix;
        }

        return redirect()
            ->route('admin.matches.edit', $match)
            ->with('status', $message);
    }
}
