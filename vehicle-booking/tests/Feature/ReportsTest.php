<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\CreatesBookingFixtures;

final class ReportsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use CreatesBookingFixtures;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $requesterId;
    private int $vehicleId;
    private int $approver1Id;
    private int $approver2Id;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requesterId = $this->createUser('admin');
        $this->approver1Id = $this->createUser('approver', 1);
        $this->approver2Id = $this->createUser('approver', 2);
        $this->vehicleId   = $this->createVehicle();
    }

    // ---- helpers ----

    private function createBooking(array $overrides = []): int
    {
        $result = $this->withBodyFormat('json')
            ->post('api/bookings', $this->bookingPayload($overrides));

        return json_decode($result->getJSON(), true)['data']['id'];
    }

    private function getBookingCode(int $bookingId): string
    {
        $db = \Config\Database::connect();

        return $db->table('vehicle_bookings')
            ->where('id', $bookingId)
            ->get()->getRowArray()['booking_code'];
    }

    private function approvalIds(int $bookingId): array
    {
        $db = \Config\Database::connect();

        $level1 = (int) $db->table('booking_approvals')
            ->where('booking_id', $bookingId)->where('level', 1)
            ->get()->getRowArray()['id'];

        $level2 = (int) $db->table('booking_approvals')
            ->where('booking_id', $bookingId)->where('level', 2)
            ->get()->getRowArray()['id'];

        return [$level1, $level2];
    }

    private function completeBooking(int $bookingId, int $odometerStart, int $odometerEnd): void
    {
        [$level1Id, $level2Id] = $this->approvalIds($bookingId);

        $this->withBodyFormat('json')->post("api/approvals/{$level1Id}/approve", []);
        $this->withBodyFormat('json')->post("api/approvals/{$level2Id}/approve", []);
        $this->withBodyFormat('json')->post("api/bookings/{$bookingId}/complete", [
            'odometer_start' => $odometerStart,
            'odometer_end'   => $odometerEnd,
        ]);
    }

    /**
     * Ambil raw body xlsx dari response, tulis ke file sementara,
     * lalu baca isinya lewat PhpSpreadsheet. Return array baris
     * (index 0 = header, index array kolom mulai dari 0 = kolom A).
     */
    private function parseXlsxFromResponse($result): array
    {
        $body = $result->response()->getBody();

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_test_') . '.xlsx';
        file_put_contents($tmpFile, $body);

        $spreadsheet = IOFactory::load($tmpFile);
        $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        unlink($tmpFile);

        return $rows;
    }

    private function findRowByBookingCode(array $rows, string $code): ?array
    {
        foreach ($rows as $row) {
            if (($row[0] ?? null) === $code) {
                return $row;
            }
        }

        return null;
    }

    // ---- response headers ----

    public function testExportReturnsXlsxContentTypeAndAttachmentHeader(): void
    {
        $this->createBooking();

        $result = $this->get('api/reports/bookings/export');
        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $result->assertHeader('Content-Disposition');

        $disposition = $result->response()->getHeaderLine('Content-Disposition');
        $this->assertStringContainsString('.xlsx', $disposition);
        $this->assertStringContainsString('attachment', $disposition);
    }

    // ---- struktur file ----

    public function testExportContainsHeaderRow(): void
    {
        $result = $this->get('api/reports/bookings/export');
        $rows   = $this->parseXlsxFromResponse($result);

        $this->assertSame('Kode Booking', $rows[0][0]);
        $this->assertSame('Kendaraan', $rows[0][1]);
        $this->assertSame('Plat Nomor', $rows[0][2]);
        $this->assertSame('Status', $rows[0][9]);
        $this->assertSame('Jarak Tempuh (km)', $rows[0][12]);
    }

    // ---- data booking ----

    public function testExportIncludesBookingDataForCreatedBooking(): void
    {
        $bookingId = $this->createBooking([
            'purpose'     => 'Uji Export',
            'destination' => 'Site Export',
        ]);
        $code = $this->getBookingCode($bookingId);

        $result = $this->get('api/reports/bookings/export');
        $rows   = $this->parseXlsxFromResponse($result);
        $row    = $this->findRowByBookingCode($rows, $code);

        $this->assertNotNull($row, 'Baris booking yang barusan dibuat harus ada di export');
        $this->assertSame('Uji Export', $row[5]);
        $this->assertSame('Site Export', $row[6]);
        $this->assertSame('Menunggu Persetujuan L1', $row[9]);
        $this->assertSame('-', $row[3], 'Driver kosong harus ditampilkan sebagai "-"');
    }

    public function testExportShowsDashForMissingFuelLogData(): void
    {
        $bookingId = $this->createBooking();
        $code      = $this->getBookingCode($bookingId);

        $result = $this->get('api/reports/bookings/export');
        $rows   = $this->parseXlsxFromResponse($result);
        $row    = $this->findRowByBookingCode($rows, $code);

        $this->assertNotNull($row);
        $this->assertSame('-', $row[10], 'Odometer awal kosong harus "-"');
        $this->assertSame('-', $row[11], 'Odometer akhir kosong harus "-"');
        $this->assertSame('-', $row[12], 'Jarak tempuh kosong harus "-"');
        $this->assertSame('-', $row[13], 'BBM kosong harus "-"');
        $this->assertSame('-', $row[14], 'Catatan kosong harus "-"');
    }

    public function testExportIncludesOdometerAndDistanceAfterComplete(): void
    {
        $bookingId = $this->createBooking();
        $code      = $this->getBookingCode($bookingId);

        $this->completeBooking($bookingId, 500, 650);

        $result = $this->get('api/reports/bookings/export');
        $rows   = $this->parseXlsxFromResponse($result);
        $row    = $this->findRowByBookingCode($rows, $code);

        $this->assertNotNull($row);
        $this->assertSame(500, (int) $row[10]);
        $this->assertSame(650, (int) $row[11]);
        $this->assertSame(150, (int) $row[12]);
        $this->assertSame('Selesai', $row[9]);
    }

    // ---- filter tanggal ----

    public function testExportFiltersByStartAndEndDate(): void
    {
        $inRangeId = $this->createBooking([
            'start_date' => '2026-09-10 08:00:00',
            'end_date'   => '2026-09-10 17:00:00',
        ]);
        $outOfRangeId = $this->createBooking([
            'start_date' => '2026-08-01 08:00:00',
            'end_date'   => '2026-08-01 17:00:00',
        ]);

        $codeIn  = $this->getBookingCode($inRangeId);
        $codeOut = $this->getBookingCode($outOfRangeId);

        $result = $this->get('api/reports/bookings/export?start=2026-09-01&end=2026-09-30');
        $rows   = $this->parseXlsxFromResponse($result);

        $this->assertNotNull($this->findRowByBookingCode($rows, $codeIn), 'Booking dalam range tanggal harus muncul');
        $this->assertNull($this->findRowByBookingCode($rows, $codeOut), 'Booking di luar range tanggal tidak boleh muncul');
    }
}