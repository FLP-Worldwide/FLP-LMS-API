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
            'student_id' => 'required|exists:students,id',
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
             * 1️⃣ Load fees structure with base installments
             */
            $structure = FeesStructure::with('installments')
                ->findOrFail($request->fees_structure_id);

            /**
             * 2️⃣ Create or update student fee assignment
             */
            $studentFee = StudentFee::updateOrCreate(
                [
                    'student_id' => $request->student_id,
                    'fees_structure_id' => $structure->id,
                ],
                [] // institute_id handled by trait
            );

            /**
             * 3️⃣ Reset old installments (safe re-assign)
             */
            $studentFee->installments()->delete();

            $totalAmount = 0;

            /**
             * 4️⃣ Copy base installments from structure
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
             * 5️⃣ Add student-specific extra installments
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
             * 6️⃣ Update final total
             */
            $studentFee->update([
                'total_amount' => $totalAmount,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Fees assigned to student successfully',
                'data' => $studentFee->load('installments.feesType', 'structure')
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
