<?php

declare(strict_types=1);

namespace Commerce\Iam\Http\Controllers\Admin;

use Commerce\Iam\Models\Team;
use Commerce\Iam\Models\User;
use Commerce\Iam\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class TeamController extends Controller
{
    public function __construct(private readonly TeamService $teams) {}

    public function index(): View
    {
        return view('iam::admin.teams.index', [
            'items' => Team::query()->withCount('users')->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('iam::admin.teams.create', [
            'statuses' => config('iam.statuses', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $team = $this->teams->create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('iam_teams', 'slug')],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', Rule::in(array_keys(config('iam.statuses', [])))],
        ]));

        return redirect()->route('admin.iam.teams.edit', $team)->with('status', 'Team created.');
    }

    public function edit(Team $team): View
    {
        return view('iam::admin.teams.edit', [
            'item' => $team->load('users'),
            'users' => User::query()->orderBy('name')->get(),
            'statuses' => config('iam.statuses', []),
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $this->teams->update($team, $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('iam_teams', 'slug')->ignore($team->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', Rule::in(array_keys(config('iam.statuses', [])))],
        ]));

        return redirect()->route('admin.iam.teams.edit', $team)->with('status', 'Team saved.');
    }

    public function addMember(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', 'string', Rule::in(['member', 'admin'])],
        ]);

        $user = User::query()->findOrFail((int) $data['user_id']);
        $this->teams->addMember($team, $user, $data['role']);

        return redirect()->route('admin.iam.teams.edit', $team)->with('status', 'Member added.');
    }

    public function removeMember(Team $team, User $user): RedirectResponse
    {
        $this->teams->removeMember($team, $user);

        return redirect()->route('admin.iam.teams.edit', $team)->with('status', 'Member removed.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->teams->delete($team);

        return redirect()->route('admin.iam.teams.index')->with('status', 'Team deleted.');
    }
}
