<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Rol;

class primer_usuario extends Command
{
    protected $signature = 'primer_usuario {username} {nombre} {apellido} {email} {password}';
    protected $description = 'Crea un nuevo usuario en la tabla yafo_plaft.users con datos validados';

    public function handle()
    {
        $data = [
            'username' => $this->argument('username'),
            'nombre' => $this->argument('nombre'),
            'apellido' => $this->argument('apellido'),
            'email' => $this->argument('email'),
            'password' => $this->argument('password'),
        ];

        // Validar los datos
        $validator = Validator::make($data, [
            'username' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        // Aplicar hash al password
        $data['password'] = Hash::make($data['password']);

        // Verificar si el rol "Administrador" ya existe, si no, crearlo
        $rol_administrador = Rol::firstOrCreate(
            ['nombre' => 'Administrador'],  // Condición para buscar el rol
            [
                'guard_name' => 'web',  // Proporciona un valor por defecto para guard_name
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
        // Insertar el usuario en la tabla
        DB::table('yafo_plaft.users')->insert([
            'username' => $data['username'],
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'password' => $data['password'],
            'rol_id' => $rol_administrador->id,  // Asignar el ID del rol "Administrador"
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info('Usuario creado exitosamente.');
        return 0; // Retornar éxito
    }
}