<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Certificate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /**
     * Check if email exists
     */
    public function checkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid input',
                'details' => $validator->errors()
            ], 422);
        }

        $exists = Student::where('email', $request->email)->exists();

        return response()->json(['exists' => $exists]);
    }

    /**
     * Import students from Excel and auto-issue default certificates
     */
  public function import(Request $request)
{
    $validator = Validator::make($request->all(), [
        'excel' => 'required|file|mimes:xlsx,xls,csv',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => 'Invalid file',
            'details' => $validator->errors()
        ], 422);
    }

    $file = $request->file('excel');
    $spreadsheet = IOFactory::load($file->getPathname());
    $sheetData = $spreadsheet->getActiveSheet()->toArray();

    $skipped = 0;
    $inserted = 0;
    $errors = [];

    foreach ($sheetData as $index => $row) {
        if ($index === 0) continue; // Skip header row

        $email = $row[3] ?? null;
        $workshopType = $row[6] ?? null;

        if (!$email || !$workshopType) {
            $errors[] = "Row $index: Missing email or workshop type";
            $skipped++;
            continue;
        }

        // Check if student already exists
        $existingStudent = Student::where('email', $email)
                                  ->where('workshopType', $workshopType)
                                  ->first();

        if ($existingStudent) {
            $skipped++;
            continue;
        }

        try {
            // Parse date (assumes dd/mm/yyyy format)
            $workshopDate = isset($row[5]) ? \DateTime::createFromFormat('d/m/Y', $row[5]) : null;
            $workshopDateFormatted = $workshopDate ? $workshopDate->format('Y-m-d') : null;

            $student = Student::create([
                'nameEN' => $row[1] ?? null,
                'nameAR' => $row[0] ?? null,
                'phone' => $row[2] ?? null,
                'email' => $email,
                'workshopName' => $row[4] ?? null,
                'workshopDate' => $workshopDateFormatted,
                'workshopType' => $workshopType,
            ]);

            // Issue certificate with type = workshopType
            Certificate::create([
                'student_id' => $student->id,
                'type' => $workshopType, // dynamic certificate type
                'certificate_data' => [
                    'name' => $student->nameEN,
                    'workshop' => $student->workshopName,
                    'date' => $workshopDateFormatted,
                ],
                'issued_at' => now(),
                'expires_at' => null,
            ]);

            $inserted++;
        } catch (\Exception $e) {
            $errors[] = "Row $index: " . $e->getMessage();
            $skipped++;
        }
    }

    return response()->json([
        'message' => "Excel imported successfully! Inserted: $inserted, Skipped: $skipped",
        'inserted' => $inserted,
        'skipped' => $skipped,
        'errors' => $errors
    ]);
}

    /**
     * Get available certificates for a student
     */
    public function getAvailableCertificates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'workshopType' => 'required|in:student,doctor,internship',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'certificates' => [],
                'error' => 'Invalid input',
                'details' => $validator->errors()
            ], 422);
        }

        $student = Student::where('email', $request->email)
                          ->where('workshopType', $request->workshopType)
                          ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'certificates' => [],
                'error' => 'Student not found for the given email and workshop type'
            ], 404);
        }

        // Fetch issued certificates from DB
        $issuedCertificates = Certificate::where('student_id', $student->id)->get();

        // If none, return default certificate types for this workshop type
        if ($issuedCertificates->isEmpty()) {
            $availableCertificates = [
                ['id' => 'completion', 'name' => 'Completion Certificate'],
            ];

            if ($student->workshopType === 'student') {
                $availableCertificates[] = ['id' => 'excellence', 'name' => 'Excellence Certificate'];
            } elseif ($student->workshopType === 'doctor') {
                $availableCertificates[] = ['id' => 'participation', 'name' => 'Participation Certificate'];
            }

            return response()->json([
                'success' => true,
                'certificates' => $availableCertificates
            ]);
        }

        return response()->json([
            'success' => true,
            'certificates' => $issuedCertificates
        ]);
    }
}
