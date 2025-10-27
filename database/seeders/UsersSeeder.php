<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('users')->insert([
            [
                'firstName' => 'Admin',
                'lastName'  => 'Principal',
                'email'     => 'admin@example.com',
                'password'  => Hash::make('admin1234'),
                'dni'       => '00000000A',
                'role'      => 'admin',
                'isActive'  => true,
                'status'    => 'active',
                'coursePriceCents' => 0,
                'tutor_id'  => null,
                'depositStatus' => 'paid',
                'finalPayment'  => 'pending',
                'contractSigned'=> false,
                'contractDate'  => null,
                'contractIp'    => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'firstName' => 'Tina',
                'lastName'  => 'Teacher',
                'email'     => 'teacher@example.com',
                'password'  => Hash::make('teacher1234'),
                'dni'       => '11111111B',
                'role'      => 'teacher',
                'isActive'  => true,
                'status'    => 'active',
                'coursePriceCents' => 0,
                'tutor_id'  => null,
                'depositStatus' => 'pending',
                'finalPayment'  => 'pending',
                'contractSigned'=> false,
                'contractDate'  => null,
                'contractIp'    => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'firstName' => 'Celia',
                'lastName'  => 'Client',
                'email'     => 'client@example.com',
                'password'  => Hash::make('client1234'),
                'dni'       => '22222222C',
                'role'      => 'client',
                'isActive'  => true,
                'status'    => 'active',
                'coursePriceCents' => 19900, // 199,00 €
                'tutor_id'  => null, // o el id del teacher insertado arriba si quieres vincularlo luego
                'depositStatus' => 'paid',
                'finalPayment'  => 'pending',
                'contractSigned'=> false,
                'contractDate'  => null,
                'contractIp'    => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ]);
    }
}
