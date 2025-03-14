<?php

namespace App\Http\Controllers;

use App\Models\AuthUser;
use App\Models\Ciudadano;
use App\Models\Reciclador;
use App\Models\Asociacion;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class AuthController extends Controller
{
    /**
     * Registro de usuarios
     */
    public function register(Request $request)
    {
        try {
            // Validar datos comunes
            $common = $request->validate([
                'email' => 'required|email|unique:auth_users,email',
                'password' => 'required|min:8|confirmed',
                'role' => 'required|in:ciudadano,reciclador,asociacion',
            ]);

            // Validar datos específicos según el rol
            $profileData = [];
            $profile = null;

            if ($common['role'] === 'ciudadano') {
                $profileData = $request->validate([
                    'name' => 'required|string',
                    'direccion' => 'required|string',
                    'ciudad' => 'required|string',
                    'telefono' => 'nullable|string',
                    'referencias_ubicacion' => 'nullable|string',
                ]);

                $profile = Ciudadano::create($profileData);
            } elseif ($common['role'] === 'reciclador') {
                $profileData = $request->validate([
                    'name' => 'required|string',
                    'telefono' => 'required|string',
                    'ciudad' => 'required|string',
                    'asociacion_id' => 'required|exists:asociaciones,id',
                ]);

                $profile = Reciclador::create($profileData);
            } elseif ($common['role'] === 'asociacion') {
                $profileData = $request->validate([
                    'name' => 'required|string',
                    'number_phone' => 'required|string',
                    'city' => 'required|string',
                    'direccion' => 'nullable|string',
                    'descripcion' => 'nullable|string',
                ]);

                $profile = Asociacion::create($profileData);
            }

            // Crear usuario de autenticación
            $user = AuthUser::create([
                'email' => $common['email'],
                'password' => Hash::make($common['password']),
                'role' => $common['role'],
                'profile_id' => $profile->id,
            ]);

            // Generar token si se usa Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registro exitoso',
                'data' => [
                    'user' => $user,
                    'profile' => $profile,
                    'token' => $token
                ]
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (QueryException $e) {
            // Manejo de errores de base de datos
            if ($e->getCode() == 23505 || $e->getCode() == 1062) { // Códigos para clave duplicada en PostgreSQL y MySQL
                return response()->json([
                    'success' => false,
                    'message' => 'El correo electrónico ya está registrado'
                ], 400);
            }
            return response()->json([
                'success' => false,
                'message' => 'Error de base de datos',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login de usuarios
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = AuthUser::where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            // Obtener datos específicos del perfil
            $profileData = null;
            if ($user->role === 'ciudadano') {
                $profileData = Ciudadano::find($user->profile_id);
            } elseif ($user->role === 'reciclador') {
                $profileData = Reciclador::with('asociacion:id,name')->find($user->profile_id);
            } elseif ($user->role === 'asociacion') {
                $profileData = Asociacion::find($user->profile_id);
            }

            // Generar token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'data' => [
                    'user' => $user,
                    'profile' => $profileData,
                    'token' => $token
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout de usuarios
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al cerrar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registro de reciclador (solo para asociaciones)
     */
    public function registerRecycler(Request $request)
    {
        try {
            // Verificar que el usuario autenticado sea una asociación
            if ($request->user()->role !== 'asociacion') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para registrar recicladores'
                ], 403);
            }

            // Datos de validación
            $data = $request->validate([
                'email' => 'required|email|unique:auth_users,email',
                'password' => 'required|min:8|confirmed',
                'name' => 'required|string',
                'telefono' => 'required|string',
                'ciudad' => 'required|string',
            ]);

            // Obtener ID de la asociación actual
            $asociacionId = $request->user()->profile_id;

            // Crear reciclador
            $reciclador = Reciclador::create([
                'name' => $data['name'],
                'telefono' => $data['telefono'],
                'ciudad' => $data['ciudad'],
                'asociacion_id' => $asociacionId,
                'status' => 'disponible',
            ]);

            // Crear usuario de autenticación
            $user = AuthUser::create([
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'reciclador',
                'profile_id' => $reciclador->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reciclador registrado correctamente',
                'data' => [
                    'user' => $user,
                    'profile' => $reciclador,
                ]
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (QueryException $e) {
            if ($e->getCode() == 23505 || $e->getCode() == 1062) { // Códigos para clave duplicada
                return response()->json([
                    'success' => false,
                    'message' => 'El correo electrónico ya está registrado'
                ], 400);
            }
            return response()->json([
                'success' => false,
                'message' => 'Error de base de datos',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener ciudades disponibles
     */
    public function getCities()
    {
        try {
            $cities = City::all(['id', 'name']);

            return response()->json([
                'success' => true,
                'message' => 'Ciudades obtenidas correctamente',
                'data' => $cities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ciudades',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener perfil del usuario autenticado
     */
    public function getProfile(Request $request)
    {
        try {
            $user = $request->user();
            $profileData = null;

            if ($user->role === 'ciudadano') {
                $profileData = Ciudadano::find($user->profile_id);
            } elseif ($user->role === 'reciclador') {
                $profileData = Reciclador::with('asociacion:id,name')->find($user->profile_id);
            } elseif ($user->role === 'asociacion') {
                $profileData = Asociacion::find($user->profile_id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Perfil obtenido correctamente',
                'data' => [
                    'user' => $user,
                    'profile' => $profileData
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
