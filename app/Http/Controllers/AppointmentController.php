<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Appointment;
use App\Models\Availability;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Auth; 
use Illuminate\Validation\ValidationException;



class AppointmentController extends Controller
{

    /**
     * 
     * Display a listing of the resource.
     */
    public function index()
    {
        // Récupérer tous les rendez-vous
        $appointments = Appointment::with(['user', 'service'])->get();
        return response()->json([
            'status' => true,
            'data' => $appointments,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Récupérer l'utilisateur authentifié
        $user = Auth::user();
        $userId = Auth::id(); 
        $teste = $user;
        // dd($teste);
        // Vérification si l'utilisateur est authentifié
        // if (Auth::check()) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Utilisateur non authentifié',
        //     ], 401);
        // }


        try {
            $userId = Auth::id(); 

            // Validation des données
            $request->validate([
                'service_id' => 'required|exists:services,id', // Service existant
                'reason' => 'required|string|max:255',
                'symptoms' => 'nullable|string',
                'date' => 'required|date', 
                'time' => 'required|date_format:H:i',
            ]);
    
            // Recherche des disponibilités des médecins pour ce service à cette date et heure
            $availableDoctors = Availability::where('service_id', $request->service_id)
            ->where('available_date', $request->date)
            ->where('start_time', '<=', $request->time)
            ->where('end_time', '>=', $request->time)
            ->get();
        
        $eligibleDoctors = [];
        foreach ($availableDoctors as $availability) {
            $doctor = User::find($availability->doctor_id); // Récupérer le médecin par son ID
        
            if ($doctor && $doctor->hasRole('Doctor')) { // Vérifier si le médecin existe et a le rôle 'Doctor'
                $appointmentCount = Appointment::where('doctor_id', $doctor->id)
                    ->where('date', $request->date)
                    ->count();
        
                if ($appointmentCount < 15) {
                    $eligibleDoctors[] = $doctor; // Ajouter le médecin éligible
                }
            }
        }
        
        // Vérifier s'il y a des médecins éligibles
        if (empty($eligibleDoctors)) {
            return response()->json([
                'status' => false,
                'message' => 'Tous les médecins ont atteint la limite de rendez-vous pour cette date.',
            ], 422);
        }
        
        // Choisir un médecin au hasard parmi ceux qui sont éligibles
        $selectedDoctor = $eligibleDoctors[array_rand($eligibleDoctors)];
        
            // Si un médecin est disponible, création du rendez-vous
            $appointment = Appointment::create([
                'user_id' => $request->user_id,
                'service_id' => $request->service_id,
                'doctor_id' => $selectedDoctor->id,
                'reason' => $request->reason,
                'symptoms' => $request->symptoms,
                'is_visited' => false,
                'date' => $request->date,
                'time' => $request->time,
            ]);
        // dd($teste);
    
            // Création du ticket associé avec l'ID du docteur
            $ticket = Ticket::create([
                'appointment_id' => $appointment->id,
                'doctor_id' => $selectedDoctor->id, 
                'is_paid' => false, 
            ]);
    
            return response()->json([
                'status' => true,
                'message' => 'Rendez-vous créé avec succès',
                'data' => [
                    'appointment' => $appointment,
                    'ticket' => $ticket
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la création du rendez-vous',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    
  /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Récupérer les détails d'un rendez-vous
        $appointment = Appointment::with(['user', 'service', 'doctor'])->find($id);

        if (!$appointment) {
            return response()->json([
                'status' => false,
                'message' => 'Rendez-vous non trouvé',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $appointment,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validation des données
        $request->validate([
            'reason' => 'nullable|string|max:255',
            'symptoms' => 'nullable|string',
            'is_visited' => 'required|boolean', // Le patient a-t-il visité ou non ?
            'date' => 'nullable|date',
            'time' => 'nullable|date_format:H:i',
        ]);

        // Modifier le rendez-vous
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json([
                'status' => false,
                'message' => 'Rendez-vous non trouvé',
            ], 404);
        }

        // Mise à jour des champs
        $appointment->update($request->only(['reason', 'symptoms', 'is_visited', 'date', 'time']));

        return response()->json([
            'status' => true,
            'message' => 'Rendez-vous mis à jour avec succès',
            'data' => $appointment,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Supprimer un rendez-vous
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json([
                'status' => false,
                'message' => 'Rendez-vous non trouvé',
            ], 404);
        }

        $appointment->delete();

        return response()->json([
            'status' => true,
            'message' => 'Rendez-vous supprimé avec succès',
        ]);
    }
}