<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthorizedRole
{
    public const UNAUTHORIZED_ACTION_MESSAGE = 'Unauthorized action.';

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user->hasRole(['Leader'])) {
            $this->handleStudent($request, $user);
            $this->handleLeader($request, $user);
            $this->handleCompany($request, $user);
            $this->handleAssessment($request, $user);
        }
        if ($user->hasRole(['Trainer'])) {
            // $this->handleTrainerForStudent($request, $user);
            // $this->handleTrainer($request, $user);
        }

        return $next($request);
    }

    private function handleStudent(Request $request, $user)
    {
        $student = $request->route('student');
        if (!empty($student)) {
            $leader = $student->leaders()->first();
            if ($student->companies()->count() < 1 && $user->id === $leader->id) {
                $company = $user->companies()->first();
                $student->companies()->sync($company->id);
            }
            if ($student->isRelatedCompany()->where('id', $student->id)->count() < 1) {
                abort(403, self::UNAUTHORIZED_ACTION_MESSAGE);
            }
        }
    }

    private function handleLeader(Request $request, $user)
    {
        $leader = $request->route('leader');
        if (!empty($leader) && $leader->id !== $user->id) {
            abort(403, self::UNAUTHORIZED_ACTION_MESSAGE);
        }
    }

    private function handleCompany(Request $request, $user)
    {
        $company = $request->route('company');
        if (!empty($company) && $company->leaders()->where('id', $user->id)->count() < 1) {
            abort(403, self::UNAUTHORIZED_ACTION_MESSAGE);
        }
    }

    private function handleTrainerForStudent(Request $request, $user)
    {
        $student = $request->route('student');
        if (!empty($student)) {
            $trainer = $student->trainers()->first();

            if ($user->id !== $trainer->id) {
                abort(403, self::UNAUTHORIZED_ACTION_MESSAGE);
            }
        }
    }

    private function handleTrainer(Request $request, $user)
    {
        $trainer = $request->route('trainer');
        if (!empty($trainer) && $trainer->id !== $user->id) {
            abort(403, self::UNAUTHORIZED_ACTION_MESSAGE);
        }
    }

    private function handleAssessment(Request $request, $user)
    {
        $assessment = $request->route('assessment');
        if (!empty($assessment)) {
            $student = User::find($assessment->user_id);
            if (!empty($student)) {
                $leader = $student->leaders()->first();
                if ($user->id !== $leader->id) {
                    abort(403, self::UNAUTHORIZED_ACTION_MESSAGE);
                }
            }
        }
    }
}
