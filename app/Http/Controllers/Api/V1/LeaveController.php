<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveRequest;
use App\Http\Resources\LeaveResource;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LeaveController extends Controller
{
    public function __construct(private readonly LeaveService $leaves) {}

    /** Own leave, latest start date first; optional ?filter[status]=pending&filter[type]=2 */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', LeaveRequest::class);

        $leaves = QueryBuilder::for($request->user()->leaveRequests()->with('leaveType'))
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type', 'leave_type_id'),
            ])
            ->orderByDesc('from_date')
            ->orderByDesc('leave_requests.id')
            ->paginate(config('hrms.per_page'))
            ->withQueryString();

        return LeaveResource::collection($leaves);
    }

    public function store(StoreLeaveRequest $request): JsonResponse
    {
        $leave = $this->leaves->apply($request->user(), $request->validated());

        return LeaveResource::make($leave->load('leaveType'))->response()->setStatusCode(201);
    }

    public function cancel(LeaveRequest $leave): LeaveResource|JsonResponse
    {
        Gate::authorize('cancel', $leave);

        // The policy saw it cancellable; 409 only when it was rejected or started at the same moment.
        return $this->leaves->cancel($leave)
            ? LeaveResource::make($leave->load('leaveType'))
            : response()->json(['message' => 'This leave can no longer be cancelled.'], 409);
    }
}
