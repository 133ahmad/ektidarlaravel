<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Certificate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class CertificateController extends Controller
{
     public function show($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['error' => 'Certificate not found'], 404);
        }

        return response()->json([
            'id' => $student->id,
            'name' => $student->nameEN,
            'nameArabic' => $student->nameAR,
            'workshopName' => $student->workshopName,
            'date' => $student->workshopDate,
            'dateArabic' => $student->workshopDate, 
            'email' => $student->email,
        ]);
    }
    public function getCertificates(Request $request)
{
    $email = $request->query('email');
    $workshopType = $request->query('workshopType');

    $students = Student::where('email', $email)
        ->where('workshopType', $workshopType)
        ->get()
        ->map(function($student) {
            return [
                'id' => $student->id,
                'name' => $student->nameEN,
                'nameArabic' => $student->nameAR,
                'workshopName' => $student->workshopName,
                'date' => $student->workshopDate,
                'dateArabic' => $student->workshopDate, // format as needed
                'type' => $student->workshopType,
            ];
        });

    return response()->json([
        'certificates' => $students
    ]);
}

    // Issue (create) a new certificate for a student
    public function issueCertificate(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'workshopType' => 'required|string',
            'type' => 'required|string', // student, doctor, internship, general
            'certificate_data' => 'nullable|array',
        ]);

        $student = Student::where('email', $request->email)
            ->where('workshopType', $request->workshopType)
            ->first();

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $certificate = Certificate::create([
            'student_id' => $student->id,
            'type' => $request->type,
            'certificate_data' => $request->certificate_data ?? [],
            'issued_at' => Carbon::now(),
            'expires_at' => null,
        ]);

        return response()->json(['success' => true, 'certificate' => $certificate], 201);
    }

   public function getAvailableCertificates(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'certificates' => [],
            'error' => 'Invalid input',
            'details' => $validator->errors()
        ], 422);
    }

    // Get all students for the given email
    $students = Student::where('email', $request->email)->get();

    if ($students->isEmpty()) {
        return response()->json([
            'success' => false,
            'certificates' => [],
            'error' => 'No students found for this email'
        ], 404);
    }

    // Collect all certificates for all students
    $allCertificates = Certificate::whereIn('student_id', $students->pluck('id'))->get();

    return response()->json([
        'success' => true,
        'certificates' => $allCertificates
    ]);
}

}
