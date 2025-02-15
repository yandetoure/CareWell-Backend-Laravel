<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AvailabilityController extends Controller
{
    /** 
     * Display a listing of the available slots for a specific doctor and service.
     */
    public function index()
    {
        $availabilities = Availability::orderBy('available_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();
        return response()->json(['data' => $availabilities]);
    }

    /**
     * Store a newly created availability in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'doctor_id' => 'required|exists:users,id',
                'service_id' => 'required|exists:services,id',
                'available_date' => 'required|date',
                'day_of_week' => 'required|in:0,1,2,3,4,5,6', // Ajout de day_of_week pour la vérification
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'appointment_duration' => 'required|integer|min:15',
                'recurrence_type' => 'nullable|string|in:none,daily,weekly',
            ]);

            $availability = Availability::create([
                'doctor_id' => $request->doctor_id,
                'service_id' => $request->service_id,
                'available_date' => $request->available_date,
                'day_of_week' => $request->day_of_week,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'appointment_duration' => $request->appointment_duration,
                'recurrence_type' => $request->recurrence_type ?? 'none',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Disponibilité ajoutée avec succès',
                'data' => $availability,
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
     * Generate weekly recurrences.
     */
    public function generateWeeklyRecurrences($startDate, $endDate)
    {
        $recurrences = [];
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            // Utilisation de day_of_week pour générer les récursions hebdomadaires
            if ($currentDate->dayOfWeek == $this->day_of_week) {
                $recurrences[] = [
                    'doctor_id' => $this->doctor_id,
                    'service_id' => $this->service_id,
                    'day_of_week' => $this->day_of_week,
                    'start_time' => $this->start_time,
                    'end_time' => $this->end_time,
                    'appointment_duration' => $this->appointment_duration,
                    'date' => $currentDate->toDateString(),
                ];
            }
            $currentDate->addWeek();
        }

        return $recurrences;
    }

    /**
     * Display the available slots for a specific doctor and service.
     */
    public function show($doctorId, $serviceId)
    {
        $availabilities = Availability::where('doctor_id', $doctorId)
            ->where('service_id', $serviceId)
            ->with(['doctor', 'service'])
            ->orderBy('available_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        if ($availabilities->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Aucune disponibilité trouvée pour ce médecin et ce service',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $availabilities,
        ]);
    }

    /**
     * Get the service for a specific doctor.
     */
    public function getServiceByDoctor($doctorId)
    {
        $doctor = User::with('service')->find($doctorId);

        if (!$doctor) {
            return response()->json(['status' => false, 'message' => 'Médecin non trouvé'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $doctor->service,
        ]);
    }

    /**
     * Get the authenticated doctor's details (ID and service).
     */
    public function getAuthenticatedDoctorDetails()
    {
        $doctor = Auth::user();

        if (!$doctor) {
            return response()->json(['status' => false, 'message' => 'Médecin non trouvé'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'doctor_id' => $doctor->id,
                'service' => $doctor->service
            ],
        ]);
    }

    /**
     * Store availability for authenticated doctor.
     */
    public function storeSelfAvailability(Request $request)
    {
        try {
            // Validation des champs
            $request->validate([
                'available_date' => 'required|date',
                'day_of_week' => 'required|in:0,1,2,3,4,5,6', // Ajout de day_of_week pour la vérification
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'appointment_duration' => 'required|integer|min:20',
                'recurrence_type' => 'nullable|string|in:none,daily,weekly',
            ]);

            // Récupérer le médecin connecté
            $doctor = Auth::user();

            // Vérifier que le médecin est authentifié
            if (!$doctor) {
                return response()->json(['status' => false, 'message' => 'Médecin non trouvé'], 404);
            }

            // Vérifier que le médecin est associé à un service
            if (!$doctor->service) {
                return response()->json(['status' => false, 'message' => 'Le médecin n\'a pas de service associé'], 404);
            }

            // Créer une nouvelle disponibilité
            $availability = Availability::create([
                'doctor_id' => $doctor->id,
                'service_id' => $doctor->service->id,
                'available_date' => $request->available_date,
                'day_of_week' => $request->day_of_week,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'appointment_duration' => $request->appointment_duration,
                'recurrence_type' => $request->recurrence_type ?? 'none',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Disponibilité ajoutée avec succès',
                'data' => $availability,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Une erreur est survenue. Veuillez réessayer plus tard.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the availability of the authenticated doctor.
     */
    public function getAuthenticatedDoctorAvailability()
    {
        $doctor = Auth::user();

        if (!$doctor) {
            return response()->json(['status' => false, 'message' => 'Médecin non trouvé'], 404);
        }

        $availabilities = Availability::where('doctor_id', $doctor->id)
            ->with('service')
            ->orderBy('available_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        if ($availabilities->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Aucune disponibilité trouvée pour ce médecin',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $availabilities,
        ]);
    }
}
