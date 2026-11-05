<?php

namespace Database\Seeders;

use App\Enums\HalfDay;
use App\Enums\LeaveStatus;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\EmployeeService;
use App\Services\LeaveService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Demo company for a fresh install: 3 departments, 15 employees, 4 known logins and about
 * two months of leave history. Fake people only (Faker, @example.com, +91 99999 phones).
 */
class DemoDataSeeder extends Seeder
{
    public const PASSWORD = 'password';

    /** Sales and Engineering staff in addition to the department managers and the demo logins. */
    private const STAFF = [
        'Engineering' => ['Software Developer', 'Senior Software Developer', 'QA Engineer', 'DevOps Engineer'],
        'Sales' => ['Sales Executive', 'Account Manager', 'Sales Executive', 'Business Development Executive'],
        'Human Resources' => ['Recruiter', 'Payroll Executive'],
    ];

    private const REASONS = [
        'Casual Leave' => ['Family function', 'Personal work', 'Travelling to my hometown', 'Bank and house paperwork', 'Wedding in the family'],
        'Sick Leave' => ['Fever and cold', 'Doctor appointment', 'Migraine', 'Food poisoning', 'Dental treatment'],
        'Unpaid Leave' => ['Extended family trip', 'Moving to a new house', 'Personal reasons'],
    ];

    private const REJECT_COMMENTS = [
        'Release week, please choose other dates.',
        'Too many people are already off on these days.',
        'Client visit planned, let us discuss other dates.',
    ];

    private CarbonInterface $now;

    /** @var array<string, LeaveType> */
    private array $types;

    public function __construct(
        private readonly EmployeeService $employees,
        private readonly LeaveService $leaves,
    ) {}

    public function run(): void
    {
        fake()->seed(2026); // same people and history on every fresh install

        $this->now = Carbon::now();

        $this->types = [
            'Casual Leave' => LeaveType::create(['name' => 'Casual Leave', 'yearly_quota' => 12, 'is_active' => true]),
            'Sick Leave' => LeaveType::create(['name' => 'Sick Leave', 'yearly_quota' => 8, 'is_active' => true]),
            'Unpaid Leave' => LeaveType::create(['name' => 'Unpaid Leave', 'yearly_quota' => null, 'is_active' => true]),
        ];

        $departments = collect(array_keys(self::STAFF))
            ->mapWithKeys(fn (string $name) => [$name => Department::create(['name' => $name])]);

        User::create(['name' => 'System Admin', 'email' => 'admin@example.com', 'password' => self::PASSWORD])->assignRole('Admin');

        $hr = $this->person($departments['Human Resources'], 'HR', 'HR Executive', 'hr@example.com');
        $manager = $this->person($departments['Engineering'], 'Manager', 'Engineering Manager', 'manager@example.com');
        $employee = $this->person($departments['Engineering'], 'Employee', 'Software Developer', 'employee@example.com');

        $departments['Engineering']->update(['manager_id' => $manager->id]);
        $departments['Sales']->update(['manager_id' => $this->person($departments['Sales'], 'Manager', 'Sales Manager')->id]);
        $departments['Human Resources']->update(['manager_id' => $this->person($departments['Human Resources'], 'Manager', 'HR Manager')->id]);

        $staff = collect(self::STAFF)->flatMap(fn (array $designations, string $department) => collect($designations)
            ->map(fn (string $designation) => $this->person($departments[$department], 'Employee', $designation)));

        $original = Carbon::getTestNow();

        try {
            $this->demoEmployeeLeave($employee);
            $this->teamLeave($manager, $staff->first(), $staff->get(1));
            $this->history(User::whereKeyNot($employee->id)->whereHas('employee')->get());
        } finally {
            Carbon::setTestNow($original);
        }

        // Someone who left the company: history stays, login is blocked.
        $this->employees->setActive($staff->last()->employee, false);

        DatabaseNotification::where('created_at', '<', $this->now->copy()->subDays(3))->update(['read_at' => $this->now]);
    }

    private function person(Department $department, string $role, string $designation, ?string $email = null): User
    {
        $first = fake()->unique()->firstName();
        $last = fake()->lastName();

        return $this->employees->create([
            'name' => "{$first} {$last}",
            'email' => $email ?? Str::lower("{$first}.{$last}").'@example.com',
            'password' => self::PASSWORD,
            'role' => $role,
            'department_id' => $department->id,
            'designation' => $designation,
            'joining_date' => fake()->dateTimeBetween('-6 years', '-3 months')->format('Y-m-d'),
            'phone' => fake()->numerify('+91 99999 #####'),
        ])->user;
    }

    /** employee@example.com sees every status on "My leaves". */
    private function demoEmployeeLeave(User $employee): void
    {
        $this->leave($employee, 'Casual Leave', $this->workingDay(-40), 2, LeaveStatus::Approved);
        $this->leave($employee, 'Casual Leave', $this->workingDay(-26), 1, LeaveStatus::Rejected);
        $this->leave($employee, 'Sick Leave', $this->workingDay(-12), 1, LeaveStatus::Approved, HalfDay::First);
        $this->leave($employee, 'Casual Leave', $this->workingDay(9), 2, LeaveStatus::Pending);
        $this->leave($employee, 'Casual Leave', $this->workingDay(20), 1, LeaveStatus::Cancelled);
    }

    /**
     * manager@example.com has someone on leave today and requests to decide, one clashing with approved
     * team leave – and some own leave (the upcoming one waits for HR).
     */
    private function teamLeave(User $manager, User $first, User $second): void
    {
        $this->leave($manager, 'Casual Leave', $this->workingDay(-18), 1, LeaveStatus::Approved);
        $this->leave($manager, 'Casual Leave', $this->workingDay(12), 2, LeaveStatus::Pending);
        $this->leave($first, 'Sick Leave', $this->workingDay(-1), 3, LeaveStatus::Approved);
        $this->leave($first, 'Casual Leave', $this->workingDay(7), 2, LeaveStatus::Approved);
        $this->leave($second, 'Casual Leave', $this->workingDay(6), 3, LeaveStatus::Pending);
        $this->leave($second, 'Sick Leave', $this->workingDay(3), 1, LeaveStatus::Pending, HalfDay::Second);
    }

    /**
     * A realistic small team: about a third take no leave in the window, the rest one request every
     * 4–7 weeks (mostly 1–2 days), from two months ago to a month ahead. Clashes are skipped.
     *
     * @param  Collection<int, User>  $users
     */
    private function history(Collection $users): void
    {
        $end = $this->now->copy()->addMonth();

        foreach ($users as $user) {
            if (fake()->boolean(35)) {
                continue;
            }

            $from = $this->workingDay(fake()->numberBetween(-60, -20));

            while ($from->lt($end)) {
                $halfDay = fake()->boolean(20) ? fake()->randomElement(HalfDay::cases()) : null;
                $length = $halfDay ? 1 : fake()->randomElement([1, 1, 1, 2, 2, 3]);
                $type = fake()->randomElement(['Casual Leave', 'Casual Leave', 'Casual Leave', 'Sick Leave', 'Sick Leave', 'Unpaid Leave']);

                $this->leave($user, $type, $from, $length, $this->randomStatus($from, $length), $halfDay);

                $from = $this->nextWorkingDay($from->copy()->addDays($length + fake()->numberBetween(28, 50)));
            }
        }
    }

    private function randomStatus(CarbonInterface $from, int $length): LeaveStatus
    {
        $today = $this->now->copy()->startOfDay();

        if ($from->lte($today)) {
            return $from->copy()->addDays($length - 1)->gte($today)
                ? LeaveStatus::Approved
                : fake()->randomElement([...array_fill(0, 7, LeaveStatus::Approved), LeaveStatus::Rejected, LeaveStatus::Rejected, LeaveStatus::Cancelled]);
        }

        return fake()->randomElement([...array_fill(0, 5, LeaveStatus::Pending), ...array_fill(0, 3, LeaveStatus::Approved), LeaveStatus::Rejected, LeaveStatus::Cancelled]);
    }

    /**
     * Applies through LeaveService at a past moment, then decides or cancels a little later, so the
     * history follows every leave rule and sends the same notifications as the app.
     */
    private function leave(User $user, string $type, CarbonInterface $from, int $length, LeaveStatus $status, ?HalfDay $halfDay = null): ?LeaveRequest
    {
        $to = $from->copy()->addDays($length - 1);
        $appliedAt = $from->copy()->subDays(fake()->numberBetween(2, 10))->setTime(fake()->numberBetween(9, 17), fake()->numberBetween(0, 59))
            ->min($this->now->copy()->subHours(fake()->numberBetween(2, 72)));

        if ($this->leaves->remaining($user, $this->types[$type], $from->year) < $length && $type !== 'Unpaid Leave') {
            $type = 'Unpaid Leave';
        }

        Carbon::setTestNow($appliedAt);

        try {
            $leave = $this->leaves->apply($user, [
                'leave_type_id' => $this->types[$type]->id,
                'from_date' => $from->toDateString(),
                'to_date' => $to->toDateString(),
                'half_day' => $halfDay?->value,
                'reason' => fake()->randomElement(self::REASONS[$type]),
            ]);
        } catch (ValidationException) {
            return null; // overlaps earlier leave, crosses a year end or falls on off days only
        }

        if ($status === LeaveStatus::Pending) {
            return $leave;
        }

        Carbon::setTestNow($appliedAt->copy()->addHours(fake()->numberBetween(2, 30))->min($this->now->copy()->subMinutes(30)));

        if ($status === LeaveStatus::Cancelled) {
            $this->leaves->cancel($leave);

            return $leave;
        }

        $approvers = $this->leaves->approversFor($leave);
        $approver = $approvers->first(fn (User $approver) => $approver->can('leaves.approve-team')) ?? $approvers->first();

        $this->leaves->decide($leave, $approver, $status, $status === LeaveStatus::Rejected ? fake()->randomElement(self::REJECT_COMMENTS) : null);

        return $leave;
    }

    /** The first working day on or after today + $offset days. */
    private function workingDay(int $offset): Carbon
    {
        return $this->nextWorkingDay($this->now->copy()->startOfDay()->addDays($offset));
    }

    private function nextWorkingDay(Carbon $day): Carbon
    {
        while ($this->leaves->countDays($day, $day) <= 0) {
            $day->addDay();
        }

        return $day;
    }
}
