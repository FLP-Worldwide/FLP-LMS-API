<?php

namespace App\Http\Controllers\Payroll;

use App\Models\StaffSalaryPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;

class PayrollController extends Controller
{
    /*
    =====================================
    SAVE SALARY PAYMENT
    =====================================
    */

    public function saveSalaryPayment(Request $request)
    {
        $validated = $request->validate([
            'staff_id'       => 'required|exists:users,id',
            'salary_month'   => 'required|date_format:Y-m',
            'payment_amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'payment_date'   => 'required|date',
            'comment'        => 'nullable|string',
        ]);

        $staff = User::findOrFail($validated['staff_id']);


        $mapping = \App\Models\UserSalaryTemplate::with('template')
            ->where('user_id', $staff->id)
            ->where('is_active', 1)
            ->first();

        if (!$mapping) {
            return response()->json([
                'status' => 'error',
                'message' => 'Salary template not assigned to staff.'
            ], 400);
        }

        $template = $mapping->template;

        $grossSalary = 0;
        $totalDeduction = 0;
        $totalAllowance = 0;

        if ($template->type === 'monthly') {

            $salaryData = $template->salary ?? [];
            $basic = $salaryData['basic'] ?? 0;

            $grossSalary = $basic;

            $allowances = $template->allowances ?? [];
            foreach ($allowances as $allowance) {
                $totalAllowance += $allowance['amount'] ?? 0;
            }

            $grossSalary += $totalAllowance;

            $deductions = $template->deductions ?? [];
            foreach ($deductions as $deduction) {
                $totalDeduction += $deduction['amount'] ?? 0;
            }

            $netSalary = $grossSalary - $totalDeduction;
        }
        else {
            $grossSalary = 0;
            $netSalary = 0;
        }

        /*
        =====================================
        SAVE PAYMENT
        =====================================
        */

        $payment = StaffSalaryPayment::create([
            'staff_id'       => $staff->id,
            'role_type'      => $staff->role ?? 'staff',

            'salary_month'   => $validated['salary_month'],

            'gross_salary'   => $grossSalary,
            'total_deduction'=> $totalDeduction,
            'net_salary'     => $netSalary,

            'payment_amount' => $validated['payment_amount'],
            'payment_method' => $validated['payment_method'],
            'payment_date'   => $validated['payment_date'],
            'comment'        => $validated['comment'],
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Salary payment saved successfully.',
            'data'    => $payment
        ]);
    }


    public function calculateSalary(Request $request)
    {
        $validated = $request->validate([
            'staff_id'     => 'required|exists:users,id',
            'salary_month' => 'required|date_format:Y-m',
        ]);

        $staffId = $validated['staff_id'];
        $month   = $validated['salary_month'];

        /*
        =====================================
        GET USER SALARY TEMPLATE
        =====================================
        */

        $mapping = \App\Models\UserSalaryTemplate::with('template')
            ->where('user_id', $staffId)
            ->where('is_active', 1)
            ->first();

        if (!$mapping) {
            return response()->json([
                'status' => 'error',
                'message' => 'Salary template not assigned to staff.'
            ], 400);
        }

        $template = $mapping->template;

        $gross = 0;
        $totalAllowance = 0;
        $totalDeduction = 0;

        /*
        =====================================
        MONTHLY SALARY
        =====================================
        */

        if ($template->type === 'monthly') {

            $salaryData = $template->salary ?? [];
            $basic = $salaryData['basic'] ?? 0;

            $gross = $basic;

            // Allowances
            $allowances = $template->allowances ?? [];
            foreach ($allowances as $allowance) {
                $totalAllowance += $allowance['amount'];
            }

            $gross += $totalAllowance;

            // Deductions
            $deductions = $template->deductions ?? [];
            foreach ($deductions as $deduction) {
                $totalDeduction += $deduction['amount'];
            }

            $net = $gross - $totalDeduction;
        }

        /*
        =====================================
        HOURLY SALARY
        =====================================
        */

        elseif ($template->type === 'hourly') {

            $salaryData = $template->salary ?? [];
            $hourlyRate = $salaryData['hourly_rate'] ?? 0;

            // Example: You must calculate working hours from attendance table
            $totalHours = 0; // TODO: fetch from attendance

            $gross = $hourlyRate * $totalHours;
            $net = $gross;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'staff_id' => $staffId,
                'month' => $month,
                'salary_type' => $template->type,
                'basic_or_rate' => $salaryData ?? null,
                'total_allowance' => $totalAllowance,
                'total_deduction' => $totalDeduction,
                'gross_salary' => $gross,
                'net_salary' => $net,
            ]
        ]);
    }



    public function salaryHistory(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:users,id',
        ]);

        $staffId = $validated['staff_id'];

        /*
        =====================================
        GET ALL PAYMENTS
        =====================================
        */

        $payments = \App\Models\StaffSalaryPayment::where('staff_id', $staffId)
            ->orderBy('salary_month', 'desc')
            ->orderBy('payment_date', 'desc')
            ->get()
            ->groupBy('salary_month');

        /*
        =====================================
        FORMAT RESPONSE
        =====================================
        */

        $result = [];

        foreach ($payments as $month => $records) {

            $gross = $records->first()->gross_salary;
            $deduction = $records->first()->total_deduction;
            $net = $records->first()->net_salary;

            $totalPaid = $records->sum('payment_amount');
            $remaining = $net - $totalPaid;

            $result[] = [
                'month' => $month,
                'gross_salary' => $gross,
                'total_deduction' => $deduction,
                'net_salary' => $net,
                'total_paid' => $totalPaid,
                'remaining_balance' => $remaining,

                'payments' => $records->map(function ($payment) {
                    return [
                        'payment_id' => $payment->id,
                        'payment_amount' => $payment->payment_amount,
                        'payment_method' => $payment->payment_method,
                        'payment_date' => $payment->payment_date,
                        'comment' => $payment->comment,
                    ];
                })->values()
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $result
        ]);
    }

}
