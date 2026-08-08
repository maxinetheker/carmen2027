<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\TaskItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    /** Descarga el archivo y devuelve el XML de la primera hoja, ya validado como zip. */
    private function openSheet(string $url, array $query = []): string
    {
        $response = $this->actingAs(User::firstOrFail())->get($url.'?'.http_build_query($query));
        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet',
            $response->headers->get('Content-Type'));

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->streamedContent());
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'El archivo no es un .xlsx válido.');
        foreach (['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/styles.xml'] as $part) {
            $this->assertNotFalse($zip->locateName($part), "Falta {$part} dentro del xlsx.");
        }
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($path);

        $this->assertNotFalse(simplexml_load_string($xml), 'La hoja no es XML válido.');

        return $xml;
    }

    public function test_weekly_summary_groups_the_week_by_blocks(): void
    {
        $this->seed();
        Lead::create(['first_name' => 'Ana María', 'last_name' => 'Sevilla', 'phone' => '999111222',
            'party_type' => 'buyer', 'interest' => 'Departamento San Isidro', 'status' => 'contacted']);
        Contact::create(['first_name' => 'Esther', 'last_name' => 'Arias', 'phone' => '999333444',
            'party_type' => 'seller', 'interest' => 'Casa en Surco']);
        Appointment::create(['title' => 'Visita depto. San Isidro', 'type' => 'visit',
            'starts_at' => now()->startOfWeek()->addDays(2)->setTime(12, 0), 'status' => 'scheduled']);
        TaskItem::create(['title' => 'Enviar tasación', 'due_at' => now()->addDay(), 'status' => 'pending']);

        $xml = $this->openSheet(route('admin.exports.weekly'), [
            'from' => now()->startOfWeek()->toDateString(),
            'to' => now()->endOfWeek()->toDateString(),
        ]);

        foreach (['CLIENTES COMPRADORES', 'Ana María Sevilla', 'Departamento San Isidro',
            'CLIENTES VENDEDORES', 'Esther Arias', 'VISITAS Y CITAS AGENDADAS',
            'Visita depto. San Isidro', 'TAREAS PENDIENTES', 'Enviar tasación'] as $expected) {
            $this->assertStringContainsString(htmlspecialchars($expected, ENT_QUOTES | ENT_XML1), $xml);
        }
    }

    public function test_full_data_export_writes_one_sheet_per_section(): void
    {
        $this->seed();
        Contact::create(['first_name' => 'Juan', 'last_name' => 'León', 'phone' => '900555666',
            'party_type' => 'seller', 'company' => 'Inversiones León']);

        $xml = $this->openSheet(route('admin.exports.data'), [
            'sections' => ['contacts', 'properties'],
        ]);

        $this->assertStringContainsString('Juan Le', $xml);
        $this->assertStringContainsString('Inversiones Le', $xml);
    }

    public function test_the_export_tab_is_reachable_from_the_admin(): void
    {
        $this->seed();

        $this->actingAs(User::firstOrFail())->get(route('admin.exports.index'))
            ->assertOk()->assertSee('Resumen semanal')->assertSee('Datos completos');
    }
}
