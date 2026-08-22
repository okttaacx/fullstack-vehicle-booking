<?php

namespace Tests\Support;

trait CreatesBookingFixtures
{
    protected function createUser(string $role = 'admin', ?int $level = null): int
    {
        $db = \Config\Database::connect();

        $db->table('users')->insert([
            'name'     => ucfirst($role) . ' ' . uniqid(),
            'username' => strtolower($role) . '_' . uniqid(),
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
            'role'     => $role,
            'level'    => $level,
        ]);

        return (int) $db->insertID();
    }

    protected function createVehicle(): int
    {
        $db = \Config\Database::connect();

        $db->table('vehicles')->insert([
            'name'             => 'Truck ' . uniqid(),
            'license_plate'    => 'B ' . random_int(1000, 9999) . ' TEST',
            'type'             => 'angkutan_barang',
            'ownership'        => 'milik_perusahaan',
            'fuel_consumption' => 10.5,
        ]);

        return (int) $db->insertID();
    }

    protected function bookingPayload(array $overrides = []): array
    {
        return array_merge([
            'requested_by'       => $this->requesterId,
            'vehicle_id'         => $this->vehicleId,
            'start_date'         => '2026-09-01 08:00:00',
            'end_date'           => '2026-09-01 17:00:00',
            'approver_level1_id' => $this->approver1Id,
            'approver_level2_id' => $this->approver2Id,
            'purpose'            => 'Uji coba',
            'destination'        => 'Site A',
        ], $overrides);
    }
}
