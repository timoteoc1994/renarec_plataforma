<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\Reciclador;
use App\Models\Asociacion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AsociacionController extends Controller
{
    /**
     * Obtener todos los recicladores de la asociación autenticada
     */
    public function getRecicladores(Request $request)
    {
        try {
            $user = $request->user();
            $asociacion = Asociacion::find($user->profile_id);

            $recicladores = Reciclador::where('asociacion_id', $asociacion->id)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Recicladores obtenidos correctamente',
                'data' => $recicladores
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recicladores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todas las solicitudes asignadas a la asociación
     */
    public function getSolicitudes(Request $request)
    {
        try {
            $user = $request->user();
            $asociacion = Asociacion::find($user->profile_id);

            $solicitudes = Solicitud::with(['ciudadano:id,name,telefono,direccion', 'reciclador:id,name,telefono,status'])
                ->where('asociacion_id', $asociacion->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Solicitudes obtenidas correctamente',
                'data' => $solicitudes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener solicitudes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar una solicitud a un reciclador
     */
    public function asignarSolicitud(Request $request)
    {
        try {
            $data = $request->validate([
                'solicitud_id' => 'required|exists:solicitudes,id',
                'reciclador_id' => 'required|exists:recicladores,id',
                'fecha_recoleccion' => 'required|date',
            ]);

            $user = $request->user();
            $asociacion = Asociacion::find($user->profile_id);

            // Verificar que la solicitud pertenezca a esta asociación
            $solicitud = Solicitud::where('id', $data['solicitud_id'])
                ->where('asociacion_id', $asociacion->id)
                ->first();

            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud no encontrada o no pertenece a esta asociación'
                ], 404);
            }

            // Verificar que el reciclador pertenezca a esta asociación
            $reciclador = Reciclador::where('id', $data['reciclador_id'])
                ->where('asociacion_id', $asociacion->id)
                ->first();

            if (!$reciclador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reciclador no encontrado o no pertenece a esta asociación'
                ], 404);
            }

            // Verificar que el reciclador esté disponible
            if ($reciclador->status !== 'disponible') {
                return response()->json([
                    'success' => false,
                    'message' => 'El reciclador no está disponible actualmente'
                ], 400);
            }

            // Verificar que la solicitud no esté ya asignada o completada
            if ($solicitud->status !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud ya ha sido asignada o completada'
                ], 400);
            }

            // Asignar la solicitud
            $solicitud->reciclador_id = $reciclador->id;
            $solicitud->fecha_recoleccion = $data['fecha_recoleccion'];
            $solicitud->status = 'asignada';
            $solicitud->save();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud asignada correctamente',
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
                'message' => 'Error al asignar solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de la asociación
     */
    public function getEstadisticas(Request $request)
    {
        try {
            $user = $request->user();
            $asociacion = Asociacion::find($user->profile_id);

            // Contar recicladores por estado
            $recicladoresStats = Reciclador::where('asociacion_id', $asociacion->id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->get()
                ->pluck('total', 'status')
                ->toArray();

            // Contar solicitudes por estado
            $solicitudesStats = Solicitud::where('asociacion_id', $asociacion->id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->get()
                ->pluck('total', 'status')
                ->toArray();

            // Contar solicitudes por mes (últimos 6 meses)
            $solicitudesPorMes = Solicitud::where('asociacion_id', $asociacion->id)
                ->whereDate('created_at', '>=', now()->subMonths(6))
                ->selectRaw('MONTH(created_at) as mes, YEAR(created_at) as anio, count(*) as total')
                ->groupBy('mes', 'anio')
                ->orderBy('anio')
                ->orderBy('mes')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Estadísticas obtenidas correctamente',
                'data' => [
                    'recicladores' => $recicladoresStats,
                    'solicitudes' => $solicitudesStats,
                    'solicitudes_por_mes' => $solicitudesPorMes,
                    'total_recicladores' => Reciclador::where('asociacion_id', $asociacion->id)->count(),
                    'total_solicitudes' => Solicitud::where('asociacion_id', $asociacion->id)->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar perfil de la asociación
     */
    public function updateProfile(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'sometimes|string|max:255',
                'number_phone' => 'sometimes|string|max:20',
                'direccion' => 'sometimes|string|max:255',
                'descripcion' => 'sometimes|string',
            ]);

            $user = $request->user();
            $asociacion = Asociacion::find($user->profile_id);

            $asociacion->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado correctamente',
                'data' => $asociacion
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
                'message' => 'Error al actualizar perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar una solicitud
     */
    public function cancelarSolicitud(Request $request, $id)
    {
        try {
            $user = $request->user();
            $asociacion = Asociacion::find($user->profile_id);

            $solicitud = Solicitud::where('id', $id)
                ->where('asociacion_id', $asociacion->id)
                ->first();

            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud no encontrada o no pertenece a esta asociación'
                ], 404);
            }

            // Verificar que la solicitud no esté completada
            if ($solicitud->status === 'completada') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar una solicitud completada'
                ], 400);
            }

            // Cancelar la solicitud
            $solicitud->status = 'cancelada';
            $solicitud->save();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud cancelada correctamente',
                'data' => $solicitud
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
