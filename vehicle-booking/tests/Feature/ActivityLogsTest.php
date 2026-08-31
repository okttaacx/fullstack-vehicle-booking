<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class ActivityLogsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    // ---- helpers ----

    private function seedLog(array $overrides = []): int
    {
        $db = \Config\Database::connect();

        $data = array_merge([
            'user_id'     => null,
            'action'      => 'create_vehicle',
            'description' => 'Test log ' . uniqid(),
            'ip_address'  => '127.0.0.1',
            'created_at'  => date('Y-m-d H:i:s'),
        ], $overrides);

        $db->table('activity_logs')->insert($data);

        return (int) $db->insertID();
    }

    private function seedUser(array $overrides = []): int
    {
        $db = \Config\Database::connect();

        $data = array_merge([
            'name'     => 'User Test ' . uniqid(),
            'username' => 'user_' . uniqid(),
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
            'role'     => 'admin',
        ], $overrides);

        $db->table('users')->insert($data);

        return (int) $db->insertID();
    }

    /**
     * NOTE: database di environment ini tidak dijamin kosong di awal tiap test
     * (kombinasi migrateOnce + refresh tidak selalu reset data antar-method).
     * Jadi semua test di bawah ini nyari baris spesifik lewat description/action
     * yang unik, bukan berasumsi jumlah total baris di tabel.
     */
    private function findByDescription(array $rows, string $description): ?array
    {
        foreach ($rows as $row) {
            if ($row['description'] === $description) {
                return $row;
            }
        }

        return null;
    }

    // ---- index() basic ----

    public function testIndexReturnsArrayStructure(): void
    {
        $result = $this->get('api/activity-logs');
        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame(200, $body['status']);
        $this->assertIsArray($body['data']);
    }

    public function testIndexReturnsLogsOrderedByNewestFirst(): void
    {
        $marker = uniqid();
        $this->seedLog([
            'description' => "Log Lama {$marker}",
            'created_at'  => '2026-01-01 08:00:00',
        ]);
        $this->seedLog([
            'description' => "Log Baru {$marker}",
            'created_at'  => '2026-01-02 08:00:00',
        ]);

        $result = $this->get('api/activity-logs');
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];

        $descriptions = array_column($rows, 'description');
        $posBaru = array_search("Log Baru {$marker}", $descriptions, true);
        $posLama = array_search("Log Lama {$marker}", $descriptions, true);

        $this->assertNotFalse($posBaru, 'Log Baru harus ada di hasil');
        $this->assertNotFalse($posLama, 'Log Lama harus ada di hasil');
        $this->assertLessThan($posLama, $posBaru, 'Log Baru harus muncul lebih dulu daripada Log Lama');
    }

    // ---- join dengan users ----

    public function testIndexIncludesUserNameAndUsernameFromJoin(): void
    {
        $marker = uniqid();
        $userId = $this->seedUser([
            'name'     => "Budi Santoso {$marker}",
            'username' => "budi.s.{$marker}",
        ]);

        $this->seedLog([
            'user_id'     => $userId,
            'description' => "Log dengan user {$marker}",
        ]);

        $result = $this->get('api/activity-logs');
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];
        $row  = $this->findByDescription($rows, "Log dengan user {$marker}");

        $this->assertNotNull($row, 'Log yang barusan dibuat harus ada di hasil');
        $this->assertSame("Budi Santoso {$marker}", $row['user_name']);
        $this->assertSame("budi.s.{$marker}", $row['user_username']);
    }

    public function testIndexReturnsNullUserNameWhenUserIdIsNull(): void
    {
        $marker = uniqid();
        $this->seedLog([
            'user_id'     => null,
            'description' => "Log tanpa user {$marker}",
        ]);

        $result = $this->get('api/activity-logs');
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];
        $row  = $this->findByDescription($rows, "Log tanpa user {$marker}");

        $this->assertNotNull($row, 'Log yang barusan dibuat harus ada di hasil');
        $this->assertNull($row['user_name']);
        $this->assertNull($row['user_username']);
    }

    // ---- filter by action ----

    public function testIndexFiltersByAction(): void
    {
        $marker = uniqid();
        $actionMatch    = "delete_vehicle_{$marker}";
        $actionNoMatch  = "create_vehicle_{$marker}";

        $this->seedLog(['action' => $actionNoMatch, 'description' => "Buat kendaraan {$marker}"]);
        $this->seedLog(['action' => $actionMatch, 'description' => "Hapus kendaraan {$marker}"]);

        $result = $this->get("api/activity-logs?action={$actionMatch}");
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];

        $this->assertCount(1, $rows);
        $this->assertSame($actionMatch, $rows[0]['action']);
    }

    public function testIndexFilterByActionReturnsEmptyWhenNoMatch(): void
    {
        $marker = uniqid();
        $this->seedLog(['action' => "create_vehicle_{$marker}"]);

        $result = $this->get("api/activity-logs?action=action_tidak_ada_{$marker}");
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];
        $this->assertSame([], $rows);
    }

    // ---- filter by date range ----

    public function testIndexFiltersByStartDate(): void
    {
        $marker = uniqid();
        $this->seedLog([
            'description' => "Sebelum range {$marker}",
            'created_at'  => '2020-01-01 10:00:00',
        ]);
        $this->seedLog([
            'description' => "Dalam range {$marker}",
            'created_at'  => '2020-01-10 10:00:00',
        ]);

        $result = $this->get('api/activity-logs?start=2020-01-05');
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];

        $this->assertNotNull($this->findByDescription($rows, "Dalam range {$marker}"), 'Log dalam range harus muncul');
        $this->assertNull($this->findByDescription($rows, "Sebelum range {$marker}"), 'Log sebelum range tidak boleh muncul');
    }

    public function testIndexFiltersByEndDate(): void
    {
        $marker = uniqid();
        $this->seedLog([
            'description' => "Dalam range {$marker}",
            'created_at'  => '2020-01-01 10:00:00',
        ]);
        $this->seedLog([
            'description' => "Setelah range {$marker}",
            'created_at'  => '2020-01-20 10:00:00',
        ]);

        $result = $this->get('api/activity-logs?end=2020-01-10');
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];

        $this->assertNotNull($this->findByDescription($rows, "Dalam range {$marker}"), 'Log dalam range harus muncul');
        $this->assertNull($this->findByDescription($rows, "Setelah range {$marker}"), 'Log setelah range tidak boleh muncul');
    }

    public function testIndexFiltersByStartAndEndDateTogether(): void
    {
        $marker = uniqid();
        $this->seedLog([
            'description' => "Terlalu awal {$marker}",
            'created_at'  => '2019-12-01 10:00:00',
        ]);
        $this->seedLog([
            'description' => "Pas di tengah {$marker}",
            'created_at'  => '2020-01-15 10:00:00',
        ]);
        $this->seedLog([
            'description' => "Terlalu akhir {$marker}",
            'created_at'  => '2020-02-01 10:00:00',
        ]);

        $result = $this->get('api/activity-logs?start=2020-01-01&end=2020-01-31');
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];

        $this->assertNotNull($this->findByDescription($rows, "Pas di tengah {$marker}"), 'Log di tengah range harus muncul');
        $this->assertNull($this->findByDescription($rows, "Terlalu awal {$marker}"), 'Log sebelum range tidak boleh muncul');
        $this->assertNull($this->findByDescription($rows, "Terlalu akhir {$marker}"), 'Log setelah range tidak boleh muncul');
    }

    public function testIndexEndDateIsInclusiveUntilEndOfDay(): void
    {
        $marker = uniqid();
        $this->seedLog([
            'description' => "Jam terakhir hari itu {$marker}",
            'created_at'  => '2020-01-10 23:59:59',
        ]);
        $this->seedLog([
            'description' => "Lewat tengah malam {$marker}",
            'created_at'  => '2020-01-11 00:00:01',
        ]);

        $result = $this->get('api/activity-logs?end=2020-01-10');
        $result->assertStatus(200);

        $rows = json_decode($result->getJSON(), true)['data'];

        $this->assertNotNull($this->findByDescription($rows, "Jam terakhir hari itu {$marker}"), 'Log jam 23:59:59 harus masih masuk end date');
        $this->assertNull($this->findByDescription($rows, "Lewat tengah malam {$marker}"), 'Log yang udah lewat tengah malam tidak boleh muncul');
    }
}