<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\LogResource;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::with(['user'])->orderByDesc('created_at');

        if ($request->filled('actor_type')) {
            $actorType = $request->string('actor_type')->toString();
            if (in_array($actorType, [ActivityLog::ACTOR_ADMIN, ActivityLog::ACTOR_USER, ActivityLog::ACTOR_GUEST], true)) {
                $query->where('actor_type', $actorType);
            }
        }

        if ($request->filled('admin_id') && $request->integer('admin_id') > 0) {
            $query->where('user_id', $request->integer('admin_id'))
                ->where('actor_type', ActivityLog::ACTOR_ADMIN);
        } elseif ($request->filled('user_id') && $request->integer('user_id') > 0) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->string('target_type')->toString());
        }

        if ($request->filled('target_id') && $request->integer('target_id') > 0) {
            $query->where('target_id', $request->integer('target_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->string('date')->toString());
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->toString().'%';
            $numericId = ctype_digit(ltrim($request->string('q')->toString(), '#'))
                ? (int) ltrim($request->string('q')->toString(), '#')
                : null;

            $query->where(function ($builder) use ($term, $numericId) {
                $builder->where('action', 'like', $term)
                    ->orWhere('target_type', 'like', $term)
                    ->orWhere('ip', 'like', $term)
                    ->orWhere('details', 'like', $term);

                if ($numericId !== null && $numericId > 0) {
                    $builder->orWhere('user_id', $numericId)
                        ->orWhere('target_id', $numericId);
                }

                $builder->orWhereHas('user', function ($userQuery) use ($term) {
                    $userQuery->where('email', 'like', $term)
                        ->orWhere('prenom', 'like', $term)
                        ->orWhere('nom', 'like', $term);
                });
            });
        }

        $logs = $query->paginate($request->integer('per_page', 50));
        $logs->getCollection()->transform(
            fn (ActivityLog $log) => (new LogResource($log))->resolve()
        );

        return response()->json(['data' => $logs]);
    }
}
