<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\Reciclador;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RecicladorController extends Controller
{
    /**
     * Obtener todas las asignaciones del reciclador autenticado
     */
    public function getAsignaciones(Request $request)
    {
        try {
            $user = $request->user();
            $reciclador = Reciclador::find($user->profile_id);

            $asignaciones = Solicitud::with(['ciudadano:id,name,telefono,direccion'])
                ->where('reciclador_id', $reciclador->id)
                ->whereIn('status', ['asignada', 'en_progreso'])
                ->orderBy('fecha_recoleccion')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Asignaciones obtenidas correctamente',
                'data' => $asignaciones
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener asignaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener el historial de recolecciones completadas
     */
    public function getHistorial(Request $request)
    {
        try {
            $user = $request->user();
            $reciclador = Reciclador::find($user->profile_id);

            $historial = Solicitud::with(['ciudadano:id,name,telefono,direccion'])
                ->where('reciclador_id', $reciclador->id)
                ->where('status', 'completada')
                ->orderBy('fecha_recoleccion', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Historial obtenido correctamente',
                'data' => $historial
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar el estado de una asignación
     */
    public function updateAsignacion(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'status' => 'required|in:en_progreso,completada',
                'comentarios' => 'nullable|string',
            ]);

            $user = $request->user();
            $reciclador = Reciclador::find($user->profile_id);

            $solicitud = Solicitud::where('reciclador_id', $reciclador->id)
                ->where('id', $id)
                ->first();

            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asignación no encontrada'
                ], 404);
            }

            // Verificar si la solicitud puede ser actualizada
            if ($solicitud->status === 'completada' || $solicitud->status === 'cancelada') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede actualizar una solicitud completada o cancelada'
                ], 400);
            }

            // Actualizar el estado
            $solicitud->status = $data['status'];

            // Si hay comentarios, actualizarlos
            if (isset($data['comentarios'])) {
                $solicitud->comentarios = $data['comentarios'];
            }

            $solicitud->save();

            return response()->json([
                'success' => true,
                'message' => 'Asignación actualizada correctamente',
                'data' => $solicitud
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar asignación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar el estado del reciclador
     */
    public function updateStatus(Request $request)
    {
        try {
            $data = $request->validate([
                'status' => 'required|in:disponible,en_ruta,inactivo',
            ]);

            $user = $request->user();
            $reciclador = Reciclador::find($user->profile_id);

            $reciclador->status = $data['status'];
            $reciclador->save();

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'data' => $reciclador
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
