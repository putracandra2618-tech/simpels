<?php

namespace Tests\Feature;

use App\Models\Borrowing;
use App\Models\Laptop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Tests\TestCase;

class LaptopCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_laptop_with_qr_image(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.laptop.store'), [
                'nama' => 'Dell XPS 13',
                'merek' => 'Dell',
                'spesifikasi' => "Intel Core i7\nRAM 16 GB\nSSD 512 GB",
            ]);

        $response->assertRedirect(route('admin.laptop.index'))
            ->assertSessionHas('success', 'Laptop "Dell XPS 13" ditambahkan dan QR Code dibuat.');

        $laptop = Laptop::where('nama', 'Dell XPS 13')->firstOrFail();
        $this->assertSame('tersedia', $laptop->status);
        $this->assertNotNull($laptop->qr_token);

        $qrFile = storage_path("app/public/qrcodes/{$laptop->qr_token}.png");
        $this->assertFileExists($qrFile);

        @unlink($qrFile);
    }

    public function test_store_requires_laptop_name(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.laptop.store'), ['nama' => ''])
            ->assertSessionHasErrors('nama');
    }

    public function test_admin_can_update_laptop_and_regenerate_qr(): void
    {
        $admin = User::factory()->admin()->create();
        $laptop = Laptop::factory()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.laptop.update', $laptop), [
                'nama' => 'HP EliteBook 840',
                'merek' => 'HP',
                'spesifikasi' => 'Intel Core i5, RAM 16 GB',
            ]);

        $response->assertRedirect(route('admin.laptop.index'))
            ->assertSessionHas('success', 'Data laptop "HP EliteBook 840" diperbarui.');

        $laptop->refresh();
        $this->assertSame('HP EliteBook 840', $laptop->nama);
        $this->assertSame('HP', $laptop->merek);
        $this->assertSame('Intel Core i5, RAM 16 GB', $laptop->spesifikasi);

        $qrFile = storage_path("app/public/qrcodes/{$laptop->qr_token}.png");
        $this->assertFileExists($qrFile);

        @unlink($qrFile);
    }

    public function test_admin_can_delete_laptop_and_its_qr_image(): void
    {
        $admin = User::factory()->admin()->create();
        $laptop = Laptop::factory()->create();

        $qrDirectory = storage_path('app/public/qrcodes');
        if (! is_dir($qrDirectory)) {
            mkdir($qrDirectory, 0777, true);
        }

        $qrFile = $qrDirectory.DIRECTORY_SEPARATOR.$laptop->qr_token.'.png';
        QrCode::format('png')
            ->size(512)
            ->margin(1)
            ->generate($laptop->qr_token, $qrFile);
        $this->assertFileExists($qrFile);

        $response = $this->actingAs($admin)
            ->delete(route('admin.laptop.destroy', $laptop));

        $response->assertRedirect(route('admin.laptop.index'))
            ->assertSessionHas('success', "Laptop \"{$laptop->nama}\" dihapus.");

        $this->assertDatabaseMissing('laptops', ['id' => $laptop->id]);
        $this->assertFileDoesNotExist($qrFile);
    }

    public function test_admin_cannot_delete_laptop_that_is_borrowed(): void
    {
        $admin = User::factory()->admin()->create();
        $laptop = Laptop::factory()->create();
        Borrowing::factory()->create([
            'laptop_id' => $laptop->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.laptop.destroy', $laptop))
            ->assertRedirect(route('admin.laptop.index'))
            ->assertSessionHas('error', "Laptop \"{$laptop->nama}\" tidak dapat dihapus karena sedang dipinjam.");

        $this->assertDatabaseHas('laptops', ['id' => $laptop->id]);
    }

    public function test_qr_print_escapes_script_tags_in_laptop_name(): void
    {
        $admin = User::factory()->admin()->create();
        $laptop = Laptop::factory()->create(['nama' => '</script><script>alert(1)</script>']);

        $this->actingAs($admin)
            ->get(route('admin.laptop.qr', $laptop))
            ->assertOk()
            ->assertDontSee('</script><script>alert(1)', false);
    }

    public function test_admin_can_print_multiple_qr_labels(): void
    {
        $admin = User::factory()->admin()->create();
        $laptops = Laptop::factory()->count(3)->create();

        $ids = $laptops->pluck('id')->implode(',');

        $response = $this->actingAs($admin)
            ->get(route('admin.laptop.print', ['ids' => $ids]));

        $response->assertOk();

        foreach ($laptops as $laptop) {
            $response->assertSee($laptop->nama);
        }
    }

    public function test_print_qr_without_selection_redirects_back(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.laptop.print'))
            ->assertRedirect(route('admin.laptop.index'))
            ->assertSessionHas('error', 'Pilih minimal satu laptop untuk dicetak.');

        $this->actingAs($admin)
            ->get(route('admin.laptop.print', ['ids' => '9999']))
            ->assertRedirect(route('admin.laptop.index'))
            ->assertSessionHas('error', 'Laptop yang dipilih tidak ditemukan.');
    }

    public function test_siswa_cannot_access_laptop_crud(): void
    {
        $siswa = User::factory()->siswa()->create();
        $laptop = Laptop::factory()->create();

        $this->actingAs($siswa)
            ->get(route('admin.laptop.index'))
            ->assertForbidden();

        $this->actingAs($siswa)
            ->post(route('admin.laptop.store'), ['nama' => 'Dell XPS 13'])
            ->assertForbidden();

        $this->actingAs($siswa)
            ->delete(route('admin.laptop.destroy', $laptop))
            ->assertForbidden();
    }
}
