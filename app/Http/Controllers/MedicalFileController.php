<?php
namespace App\Http\Controllers;

use App\Model;
use App\Models\Exam;
use App\Models\Ticket;
use App\Models\Disease;
use App\Models\Service;
use App\Models\MedicalFile;
use App\Models\Prescription;
use Illuminate\Http\Request;
use App\Models\medicalHystory;
use Illuminate\Support\Facades\Auth; 
use App\Models\medicalfilePrescription;

class MedicalFileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $medicalFiles = MedicalFile::with(['note', 'medicalHistories', 'medicalprescription',  'user'  ])->get();
        return response()->json(['data' => $medicalFiles]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $medicalFile = MedicalFile::create($request->all());
        return response()->json(['message' => 'Dossier médical créé avec succès', 'data' => $medicalFile]);
    }

    public function medicalHystory(Reqquest $request)
    {
        
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $medicalFile = MedicalFile::with(['note', 'medicalHistories', 'medicalprescription.prescription', 'user', 'medicalexam.exam', 'medicaldisease.disease'])->find($id);
    
        if (!$medicalFile) {
            return response()->json(['message' => 'Dossier médical non trouvé'], 404);
        }
    
        return response()->json(['data' => $medicalFile]);
    }
    

    
    public function showAuthMedicalFile()
    {
        $user = Auth::user();
        $medicalFile = MedicalFile::with(['note', 'medicalHistories', 'medicalprescription.prescription', 'user', 'medicalexam.exam'])
            ->where('user_id', $user->id) 
            ->first();

        if (!$medicalFile) {
            return response()->json(['message' => 'Dossier médical non trouvé'], 404);
        }

        return response()->json(['data' => $medicalFile]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $medicalFile = MedicalFile::find($id);

        if (!$medicalFile) {
            return response()->json(['message' => 'Dossier médical non trouvé'], 404);
        }

        $medicalFile->update($request->all());

        return response()->json(['message' => 'Dossier médical mis à jour avec succès', 'data' => $medicalFile]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $medicalFile = MedicalFile::find($id);

        if (!$medicalFile) {
            return response()->json(['message' => 'Dossier médical non trouvé'], 404);
        }

        $medicalFile->delete();

        return response()->json(['message' => 'Dossier médical supprimé avec succès']);
    }




    public function addNote(Request $request, string $id)
    {
        $medicalFile = MedicalFile::find($id);
    
        if (!$medicalFile) {
            return response()->json(['message' => 'Dossier médical non trouvé'], 404);
        }
    
        $validated = $request->validate([
            'content' => 'required|string',
        ]);
    
        // Vérifier si l'utilisateur connecté a le rôle "doctor"
        // if (Auth::user()->role !== 'Doctor') {
        //     return response()->json(['message' => 'Accès refusé. Seul un docteur peut ajouter une note.'], 403);
        // }
    
        // Ajouter l'ID du docteur
        $note = $medicalFile->note()->create([
            'content' => $validated['content'],
            'doctor_id' => Auth::id(), 
        ]);
    
        return response()->json(['message' => 'Note ajoutée avec succès', 'data' => $note]);
    }
    
    
    public function addPrescription(Request $request, string $id)
{
    $validated = $request->validate([
        'prescription_id' => 'required|exists:prescriptions,id',
    ]);

    $medicalFile = MedicalFile::find($id);

    if (!$medicalFile) {
        return response()->json(['message' => 'Dossier médical non trouvé'], 404);
    }

    // Optionnel : Décommentez si vous souhaitez restreindre l'accès uniquement aux médecins
    // if (Auth::user()->role !== 'doctor') {
    //     return response()->json(['message' => 'Accès refusé. Seul un docteur peut ajouter une prescription.'], 403);
    // }

    $prescription = Prescription::find($validated['prescription_id']);
    
    if (!$prescription) {
        return response()->json(['message' => 'Prescription non trouvée'], 404);
    }

    // Récupérer l'ID du patient à partir du dossier médical
    $userId = $medicalFile->user_id; // Assurez-vous que `patient_id` existe dans votre modèle MedicalFile

    // Créer la prescription
    $medicalFile->medicalprescription()->create([
        'prescription_id' => $prescription->id,
        'doctor_id' => Auth::id(),
    ]);

    // Créer le ticket en ajoutant l'ID du patient
    $ticket = Ticket::create([
        'prescription_id' => $prescription->id,
        'doctor_id' => Auth::id(),
        'user_id' => $userId, // Ajouter l'ID du patient ici
        'is_paid' => false,
    ]);
    
    return response()->json(['message' => 'Prescription ajoutée avec succès']);
}
    
    


    public function addExam(Request $request, string $id)
    {
        $medicalFile = MedicalFile::find($id);
    
        if (!$medicalFile) {
            return response()->json(['message' => 'Dossier médical non trouvé'], 404);
        }
    
        // if (Auth::user()->role !== 'doctor') {
        //     return response()->json(['message' => 'Accès refusé. Seul un docteur peut ajouter un examen.'], 403);
        // }
    
        $exam = Exam::find($request->exam_id);
        if (!$exam) {
            return response()->json(['message' => 'Examen non trouvé'], 404);
        }
        $userId = $medicalFile->user_id; // Assurez-vous que `patient_id` existe dans votre modèle MedicalFile

        $medicalFile->medicalexam()->create([
            'exam_id' => $exam->id,
            'doctor_id' => Auth::id(),
        ]);    


        $ticket = Ticket::create([
            'exam_id' => $exam->id,
            'doctor_id' => Auth::id(),       
            'user_id' => $userId, // Ajouter l'ID du patient ici     
            'is_paid' => false, 
        ]);
        return response()->json(['message' => 'Examen ajouté avec succès']);
    }
    


    public function addMedicalHistories(Request $request, string $id)
    {
        $medicalFile = MedicalFile::find($id);
    
        if (!$medicalFile) {
            return response()->json(['message' => 'Dossier médical non trouvé'], 404);
        }
    
        $validated = $request->validate([
            'content' => 'nullable|string',
        ]);
    
        $medicalHistories = $medicalFile->medicalHistories()->create($validated);
    
        return response()->json(['message' => 'Antecedent ajoutée avec succès', 'data' => $medicalHistories]);
    }


        
    public function addDisease(Request $request, string $id)
    {
        $validated = $request->validate([
            'disease_id' => 'nullable|required|exists:diseases,id',
            'treatment' => 'nullable|required',
            'state' => 'nullable|string',
        ]);
    
        $medicalFile = MedicalFile::find($id);
    
        if (!$medicalFile) {
            return response()->json(['message' => 'Dossier médical non trouvé'], 404);
        }
    
        $disease = Disease::find($validated['disease_id']);
        
        if (!$disease) {
            return response()->json(['message' => 'Maladie non trouvée'], 404);
        }
    
        $medicalFile->medicaldisease()->create([
            'disease_id' => $disease->id,
            'treatment' => $validated['treatment'],
            'state' => $validated['state'],
        ]);
    
        return response()->json(['message' => 'Maladie ajoutée avec succès']);
    }
    
}
