<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class Vehicles extends ResourceController
{
    // Hubungkan dengan model yang sudah kita buat tadi
    protected $modelName = 'App\Models\VehiclesModel';
    
    // Format output otomatis menjadi JSON
    protected $format    = 'json';

    /**
     * Method untuk mengambil semua data kendaraan
     * URL Akses: GET /api/vehicles
     */
    public function index()
    {
        // Mengambil semua data dari tabel vehicles
        $data = $this->model->findAll();
        
        // Mengembalikan response sukses (HTTP 200) beserta datanya
        return $this->respond([
            'status'  => 200,
            'message' => 'Data kendaraan berhasil diambil',
            'data'    => $data
        ]);
    }
}