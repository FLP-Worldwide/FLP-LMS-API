<?php

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\FeesStructure;
use App\Models\StudentFee;
use App\Models\StudentFeeInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentFeeController extends Controller
{
    /**
     * Assign fees structure to student
     */

    public function assign(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'fees_structure_id' => 'required|exists:fees_structures,id',

            'extra_installments' => 'nullable|array',
            'extra_installments.*.fee_type_id' => 'required|exists:fees_types,id',
            'extra_installments.*.assign_type' =>
                'required|in:TRIGGER,BAD,DAYS_AFTER_BAD,MONTH_AFTER_BAD',
            'extra_installments.*.offset' => 'required|integer|min:0',
            'extra_installments.*.amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {

            /**
             * 1️⃣ Load structure with base installments
             */
            $structure = FeesStructure::with('installments')
                ->findOrFail($request->fees_structure_id);

            $assignedStudents = [];

            /**
             * 2️⃣ Loop through students
             */
            foreach ($request->student_ids as $studentId) {

                $studentFee = StudentFee::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'fees_structure_id' => $structure->id,
                    ],
                    []
                );

                /**
                 * 3️⃣ Reset old installments
                 */
                $studentFee->installments()->delete();

                $totalAmount = 0;

                /**
                 * 4️⃣ Copy base installments
                 */
                foreach ($structure->installments as $item) {

                    StudentFeeInstallment::create([
                        'student_fee_id' => $studentFee->id,
                        'fee_type_id' => $item->fee_type_id,
                        'assign_type' => $item->assign_type,
                        'offset' => $item->offset,
                        'amount' => $item->amount,
                        'is_extra' => false,
                    ]);

                    $totalAmount += $item->amount;
                }

                /**
                 * 5️⃣ Add extra installments
                 */
                if ($request->filled('extra_installments')) {

                    foreach ($request->extra_installments as $extra) {

                        StudentFeeInstallment::create([
                            'student_fee_id' => $studentFee->id,
                            'fee_type_id' => $extra['fee_type_id'],
                            'assign_type' => $extra['assign_type'],
                            'offset' => $extra['offset'],
                            'amount' => $extra['amount'],
                            'is_extra' => true,
                        ]);

                        $totalAmount += $extra['amount'];
                    }
                }

                /**
                 * 6️⃣ Update total
                 */
                $studentFee->update([
                    'total_amount' => $totalAmount,
                ]);

                $assignedStudents[] = $studentFee->id;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Fees assigned to students successfully',
                'assigned_count' => count($assignedStudents)
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
