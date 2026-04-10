<?php

namespace Tests\Unit;

use App\Models\Clase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaseModelTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // scheduleText()
    // -------------------------------------------------------------------------

    public function test_schedule_text_returns_empty_string_when_no_schedule(): void
    {
        $clase = new Clase(['schedule' => null]);
        $this->assertSame('', $clase->scheduleText());
    }

    public function test_schedule_text_formats_single_day(): void
    {
        $clase = new Clase(['schedule' => ['lun' => ['start' => '18:00', 'end' => '19:30']]]);
        $text  = $clase->scheduleText();

        $this->assertStringContainsString('Lun', $text);
        $this->assertStringContainsString('06:00pm', $text);
        $this->assertStringContainsString('07:30pm', $text);
    }

    public function test_schedule_text_groups_days_with_same_time(): void
    {
        $clase = new Clase(['schedule' => [
            'lun' => ['start' => '18:00', 'end' => '19:30'],
            'mie' => ['start' => '18:00', 'end' => '19:30'],
        ]]);

        $text = $clase->scheduleText();

        // Los dos días deben aparecer agrupados en el mismo bloque
        $this->assertStringContainsString('Lun', $text);
        $this->assertStringContainsString('Mié', $text);
        // Solo debe existir una aparición del horario (agrupados)
        $this->assertEquals(1, substr_count($text, '06:00pm'));
    }

    public function test_schedule_text_separates_days_with_different_times(): void
    {
        $clase = new Clase(['schedule' => [
            'lun' => ['start' => '08:00', 'end' => '09:00'],
            'vie' => ['start' => '20:00', 'end' => '21:00'],
        ]]);

        $text = $clase->scheduleText();

        $this->assertStringContainsString('08:00am', $text);
        $this->assertStringContainsString('08:00pm', $text);
    }

    // -------------------------------------------------------------------------
    // cast schedule → array
    // -------------------------------------------------------------------------

    public function test_schedule_is_cast_to_array(): void
    {
        $clase = Clase::factory()->create([
            'schedule' => ['mar' => ['start' => '10:00', 'end' => '11:00']],
        ]);

        $this->assertIsArray($clase->fresh()->schedule);
        $this->assertArrayHasKey('mar', $clase->fresh()->schedule);
    }

    // -------------------------------------------------------------------------
    // relación students
    // -------------------------------------------------------------------------

    public function test_students_relationship_returns_belongs_to_many(): void
    {
        $clase = Clase::factory()->create();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $clase->students());
    }
}
