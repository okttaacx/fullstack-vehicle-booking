<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Reports extends Controller
{
    public function exportBookings()
    {
        $start = $this->request->getGet("start");
        $end   = $this->request->getGet("end");

        $db = \Config\Database::connect();
        $builder = $db->table("vehicle_bookings b")
            ->select("b.booking_code, v.name as vehicle_name, v.license_plate, d.name as driver_name, u.name as requester_name, b.purpose, b.destination, b.start_date, b.end_date, b.status")
            ->join("vehicles v", "v.id = b.vehicle_id", "left")
            ->join("drivers d", "d.id = b.driver_id", "left")
            ->join("users u", "u.id = b.requested_by", "left");

        if (! empty($start)) {
            $builder->where("b.start_date >=", $start . " 00:00:00");
        }
        if (! empty($end)) {
            $builder->where("b.end_date <=", $end . " 23:59:59");
        }

        $rows = $builder->orderBy("b.created_at", "DESC")->get()->getResultArray();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Laporan Pemesanan");

        $headers = [
            "A1" => "Kode Booking",
            "B1" => "Kendaraan",
            "C1" => "Plat Nomor",
            "D1" => "Driver",
            "E1" => "Pemohon",
            "F1" => "Keperluan",
            "G1" => "Tujuan",
            "H1" => "Tanggal Mulai",
            "I1" => "Tanggal Selesai",
            "J1" => "Status",
        ];

        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        $statusLabel = [
            "pending"     => "Menunggu Persetujuan L1",
            "approved_l1" => "Menunggu Persetujuan L2",
            "approved_l2" => "Disetujui",
            "rejected"    => "Ditolak",
            "completed"   => "Selesai",
        ];

        $rowNum = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$rowNum}", $row["booking_code"]);
            $sheet->setCellValue("B{$rowNum}", $row["vehicle_name"]);
            $sheet->setCellValue("C{$rowNum}", $row["license_plate"]);
            $sheet->setCellValue("D{$rowNum}", $row["driver_name"] ?? "-");
            $sheet->setCellValue("E{$rowNum}", $row["requester_name"]);
            $sheet->setCellValue("F{$rowNum}", $row["purpose"] ?? "-");
            $sheet->setCellValue("G{$rowNum}", $row["destination"] ?? "-");
            $sheet->setCellValue("H{$rowNum}", $row["start_date"]);
            $sheet->setCellValue("I{$rowNum}", $row["end_date"]);
            $sheet->setCellValue("J{$rowNum}", $statusLabel[$row["status"]] ?? $row["status"]);
            $rowNum++;
        }

        foreach (range("A", "J") as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "Laporan_Pemesanan_Kendaraan_" . date("Ymd_His") . ".xlsx";

        $writer = new Xlsx($spreadsheet);

        header("Access-Control-Allow-Origin: http://localhost:4200");
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header("Cache-Control: max-age=0");

        $writer->save("php://output");
        exit;
    }
}