<?php

namespace Tests\Feature;

use App\Models\Clase;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaseControllerTest extends TestCase
{
    use RefreshDatabase;

    // Simula sesión autenticada de admin (PIN)
    private function actingAsAdmin(): static
    {
        return $this->withSession(['pin_authenticated' => true]);
    }

    // -------------------------------------------------------------------------
    // Middleware de protección
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('clases.index'))->assertRedirect(route('login'));
        $this->get(route('clases.create'))->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_lists_courses(): void
    {
        Clase::factory()->count(3)->create();

        $this->actingAsAdmin()
            ->get(route('clases.index'))
            ->assertOk()
            ->assertViewHas('clases', fn($clases) => $clases->count() === 3);
    }

    public function test_index_is_empty_when_no_courses(): void
    {
        $this->actingAsAdmin()
            ->get(route('clases.index'))
            ->assertOk()
            ->assertViewHas('clases', fn($clases) => $clases->isEmpty());
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_create_form_is_accessible(): void
    {
        $this->actingAsAdmin()
            ->get(route('clases.create'))
            ->assertOk()
            ->assertViewIs('clases.create');
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_course_with_valid_data(): void
    {
        $payload = [
            'name'        => 'Salsa Básica',
            'description' => 'Introducción a la salsa',
            'schedule'    => [
                'lun' => ['start' => '18:00', 'end' => '19:30'],
                'mie' => ['start' => '18:00', 'end' => '19:30'],
            ],
        ];

        $this->actingAsAdmin()
            ->post(route('clases.store'), $payload)
            ->assertRedirect(route('clases.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('clases', ['name' => 'Salsa Básica']);

        $clase = Clase::where('name', 'Salsa Básica')->first();
        $this->assertNotNull($clase->schedule);
        $this->assertArrayHasKey('lun', $clase->schedule);
        $this->assertArrayHasKey('mie', $clase->schedule);
        $this->assertEquals('18:00', $clase->schedule['lun']['start']);
    }

    public function test_store_requires_name(): void
    {
        $this->actingAsAdmin()
            ->post(route('clases.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('clases', 0);
    }

    public function test_store_name_max_100_characters(): void
    {
        $this->actingAsAdmin()
            ->post(route('clases.store'), ['name' => str_repeat('a', 101)])
            ->assertSessionHasErrors('name');
    }

    public function test_store_without_schedule_saves_null(): void
    {
        $this->actingAsAdmin()
            ->post(route('clases.store'), ['name' => 'Tango']);

        $clase = Clase::where('name', 'Tango')->first();
        $this->assertNull($clase->schedule);
    }

    public function test_store_ignores_days_without_start_time(): void
    {
        $payload = [
            'name'     => 'Bachata',
            'schedule' => [
                'lun' => ['start' => '19:00', 'end' => '20:00'],
                'mar' => ['start' => '',       'end' => ''],  // día vacío
            ],
        ];

        $this->actingAsAdmin()->post(route('clases.store'), $payload);

        $clase = Clase::where('name', 'Bachata')->first();
        $this->assertArrayHasKey('lun', $clase->schedule);
        $this->assertArrayNotHasKey('mar', $clase->schedule);
    }

    public function test_store_sets_active_true_by_default(): void
    {
        $this->actingAsAdmin()->post(route('clases.store'), ['name' => 'Merengue']);

        $this->assertDatabaseHas('clases', ['name' => 'Merengue', 'active' => true]);
    }

    // -------------------------------------------------------------------------
    // edit
    // -------------------------------------------------------------------------

    public function test_edit_form_shows_course_data(): void
    {
        $clase = Clase::factory()->create(['name' => 'Cumbia']);

        $this->actingAsAdmin()
            ->get(route('clases.edit', $clase))
            ->assertOk()
            ->assertViewIs('clases.edit')
            ->assertViewHas('clase', fn($c) => $c->id === $clase->id);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_modifies_course(): void
    {
        $clase = Clase::factory()->create(['name' => 'Original']);

        $this->actingAsAdmin()
            ->put(route('clases.update', $clase), [
                'name'   => 'Nombre Actualizado',
                'active' => true,
            ])
            ->assertRedirect(route('clases.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('clases', ['id' => $clase->id, 'name' => 'Nombre Actualizado']);
    }

    public function test_update_can_deactivate_course(): void
    {
        $clase = Clase::factory()->create(['active' => true]);

        $this->actingAsAdmin()
            ->put(route('clases.update', $clase), [
                'name'   => $clase->name,
                'active' => false,
            ]);

        $this->assertDatabaseHas('clases', ['id' => $clase->id, 'active' => false]);
    }

    public function test_update_updates_schedule(): void
    {
        $clase = Clase::factory()->create();

        $this->actingAsAdmin()
            ->put(route('clases.update', $clase), [
                'name'     => $clase->name,
                'schedule' => [
                    'vie' => ['start' => '20:00', 'end' => '21:30'],
                ],
            ]);

        $clase->refresh();
        $this->assertArrayHasKey('vie', $clase->schedule);
        $this->assertEquals('20:00', $clase->schedule['vie']['start']);
    }

    public function test_update_requires_name(): void
    {
        $clase = Clase::factory()->create();

        $this->actingAsAdmin()
            ->put(route('clases.update', $clase), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    // -------------------------------------------------------------------------
    // destroy (desactivar)
    // -------------------------------------------------------------------------

    public function test_destroy_deactivates_course(): void
    {
        $clase = Clase::factory()->create(['active' => true]);

        $this->actingAsAdmin()
            ->delete(route('clases.destroy', $clase))
            ->assertRedirect(route('clases.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('clases', ['id' => $clase->id, 'active' => false]);
    }

    public function test_destroy_does_not_delete_record(): void
    {
        $clase = Clase::factory()->create();

        $this->actingAsAdmin()->delete(route('clases.destroy', $clase));

        $this->assertDatabaseHas('clases', ['id' => $clase->id]);
    }
}
