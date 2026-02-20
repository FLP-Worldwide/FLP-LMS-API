<?php

namespace App\Exports;

use App\Models\UserAttendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceLast3DaysExport implements FromCollection, WithHeadings
{
    protected $dates;

    public function __construct()
    {
        $this->dates = collect([
            Carbon::today()->subDays(2),
            Carbon::today()->subDay(),
            Carbon::today(),
        ]);
    }

    public function headings(): array
    {
        return array_merge(
            ['Name', 'Role'],
            $this->dates->map(fn($d) => $d->format('d M'))->toArray()
        );
    }

    public function collection()
    {
        $from = $this->dates->first();
        $to   = $this->dates->last();

        $attendances = UserAttendance::with(['teacher', 'user'])
            ->whereBetween('attendance_date', [$from, $to])
            ->get()
            ->groupBy(function ($a) {
                if ($a->teacher) {
                    return 'teacher_' . $a->teacher->id;
                }
                return 'staff_' . $a->user_id;
            });

        return $attendances->map(function ($records) {

            $first = $records->first();

            if ($first->teacher) {
                $name = trim($first->teacher->first_name . ' ' . $first->teacher->last_name);
                $role = 'Teacher';
            } else {
                $name = $first->user?->name ?? '-';
                $role = 'Staff';
            }

            $row = [
                'name' => $name,
                'role' => $role,
            ];

            foreach ($this->dates as $date) {

                $attendance = $records->first(function ($a) use ($date) {
                    return $a->attendance_date->toDateString() === $date->toDateString();
                });

                $row[$date->format('Y-m-d')] = $attendance?->status ?? '-';
            }

            return $row;
        })->values();
    }
}
