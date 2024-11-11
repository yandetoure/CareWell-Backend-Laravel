<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Medical;
use Illuminate\Http\Request;
use App\Models\MedicalFileExam;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ExamPrescriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $medicalFileExamens = MedicalFileExam::with(['medicalFile', 'exam'])->get(); // Récupérer les examens avec leurs fichiers médicaux et examens
        return response()->json([
            'status' => true,
            'data' => $medicalFileExamens,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'exam_id' => 'required|exists:exams,id',
                'medical_files_id' => 'required|exists:medical_files,id',
            ]);
            
            $medicalFileExam = MedicalFileExam::create([
                'is_done' => false,
                'exam_id' => $request->exam_id,
                'medical_files_id' => $request->medical_files_id,
            ]);

            $ticket = Ticket::create([
                'exam_id' => $request->exam_id,
                'is_paid' => false,
            ]);

            Mail::to($user->email)->send(new \App\Mail\PrescriptionMail($user));

            return response()->json([
                'status' => true,
                'message' => 'Examen créé avec succès',
                'data' => $medicalFileExam,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->validator->errors(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $medicalFileExam = MedicalFileExam::with(['service', 'medicalFile'])->find($id);
        
        if (!$medicalFileExam) {
            return response()->json([
                'status' => false,
                'message' => 'Examen non trouvé',
            ], 404);
        }
 
        return response()->json([
            'status' => true,
            'data' => $medicalFileExam,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validation des données
        $request->validate([
            'is_done' => 'required|boolean',
            'exam_id' => 'required|exists:exams,id',
        ]);

        $medicalFileExam = MedicalFileExam::find($id);
        
        if (!$medicalFileExam) {
            return response()->json([
                'status' => false,
                'message' => 'Examen non trouvé',
            ], 404);
        }

        $medicalFileExam->is_done = $request->is_done;
        $medicalFileExam->exam_id = $request->exam_id;
        $medicalFileExam->save();

        return response()->json([
            'status' => true,
            'message' => "L'examen a été modifié avec succès",
            'data' => $medicalFileExam,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $medicalFileExam = MedicalFileExam::find($id);
        
        if (!$medicalFileExam) {
            return response()->json([
                'status' => false,
                'message' => 'Examen non trouvé',
            ], 404);
        }

        $medicalFileExam->delete();

        return response()->json([
            'status' => true,
            'message' => "L'examen a été supprimé avec succès",
        ]);
    }

    public function getExamByService()
    {
        // Récupérer l'utilisateur connecté
        $doctor = auth()->user();

        // Vérifier que l'utilisateur est un médecin
        if (!$doctor || !$doctor->service_id) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur non autorisé ou service non trouvé',
            ], 403);
        }

        // Récupérer les examens liés au service avec les informations de l'utilisateur
        $examPrescription = MedicalFileExam::with([
                'medicalFile.user',
                'exam.service',
            ])
            ->whereHas('exam', function ($query) use ($doctor) {
                $query->where('service_id', $doctor->service_id);
            })
            ->get();

        // Vérifier si des examens existent
        if ($examPrescription->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Aucun examen trouvé pour ce service',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $examPrescription,
        ]);
    }

    public function updateExamStatus(Request $request, $id)
    {
        $exam =  MedicalFileExam::find($id);

        if (!$exam) {
            return response()->json([
                'status' => false,
                'message' => 'Examen non trouvé',
            ], 404);
        }

        $exam->is_done = $request->is_done;
        $exam->save();

        return response()->json([
            'status' => true,
            'message' => 'Statut de l\'examen mis à jour avec succès',
            'data' => $exam,
        ]);
    }
}
