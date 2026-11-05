<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use App\Support\LeaveBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class LeaveTypeController extends Controller
{
    /** Leave types the user can apply for, with this year's balance; the app needs the ids to apply. */
    public function index(Request $request, LeaveService $leaves): JsonResponse
    {
        Gate::authorize('create', LeaveRequest::class);

        $year = Carbon::today()->year;

        $types = $leaves->balances($request->user(), $year)
            ->filter(fn (LeaveBalance $balance) => $balance->type->is_active)
            ->map(fn (LeaveBalance $balance) => [
                'id' => $balance->type->id,
                'name' => $balance->type->name,
                'yearly_quota' => $balance->quota(),
                'used' => $balance->used(),
                'remaining' => $balance->remaining(),
            ])
            ->values();

        return response()->json(['year' => $year, 'data' => $types]);
    }
}
