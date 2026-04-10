<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): static
    {
        return $this->withSession(['pin_authenticated' => true]);
    }

    // -------------------------------------------------------------------------
    // Middleware de protección
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('students.index'))->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_lists_students_ordered_by_name(): void
    {
        Student::factory()->create(['name' => 'Zoe']);
        Student::factory()->create(['name' => 'Ana']);

        $response = $this->actingAsAdmin()->get(route('students.index'))->assertOk();

        $names = $response->viewData('students')->pluck('name')->toArray();
        $this->assertEquals(['Ana', 'Zoe'], $names);
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_create_form_is_accessible(): void
    {
        $this->actingAsAdmin()
            ->get(route('students.create'))
            ->assertOk()
            ->assertViewIs('students.create');
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_student_with_valid_data(): void
    {
        $payload = [
            'name'  => 'Juan Pérez',
            'dni'   => '12345678',
            'phone' => '987654321',
            'notes' => 'Alumno nuevo',
        ];

        $this->actingAsAdmin()
            ->post(route('students.store'), $payload)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('students', ['name' => 'Juan Pérez', 'dni' => '12345678']);
    }

    public function test_store_redirects_to_plans_after_creation(): void
    {
        $this->actingAsAdmin()
            ->post(route('students.store'), ['name' => 'María López'])
            ->assertRedirectToRoute('students.plans.index', Student::first());
    }

    public function test_store_requires_name(): void
    {
        $this->actingAsAdmin()
            ->post(route('students.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_store_rejects_duplicate_dni(): void
    {
        Student::factory()->create(['dni' => '11111111']);

        $this->actingAsAdmin()
            ->post(route('students.store'), ['name' => 'Otro', 'dni' => '11111111'])
            ->assertSessionHasErrors('dni');
    }

    public function test_store_allows_null_dni(): void
    {
        $this->actingAsAdmin()
            ->post(route('students.store'), ['name' => 'Sin DNI', 'dni' => ''])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('students', ['name' => 'Sin DNI', 'dni' => null]);
    }

    public function test_store_name_max_100_characters(): void
    {
        $this->actingAsAdmin()
            ->post(route('students.store'), ['name' => str_repeat('a', 101)])
            ->assertSessionHasErrors('name');
    }

    public function test_store_sets_active_true_by_default(): void
    {
        $this->actingAsAdmin()->post(route('students.store'), ['name' => 'Activo']);

        $this->assertDatabaseHas('students', ['name' => 'Activo', 'active' => true]);
    }

    // -------------------------------------------------------------------------
    // edit
    // -------------------------------------------------------------------------

    public function test_edit_form_shows_student_data(): void
    {
        $student = Student::factory()->create(['name' => 'Carlos']);

        $this->actingAsAdmin()
            ->get(route('students.edit', $student))
            ->assertOk()
            ->assertViewIs('students.edit')
            ->assertViewHas('student', fn($s) => $s->id === $student->id);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_modifies_student(): void
    {
        $student = Student::factory()->create(['name' => 'Nombre Viejo']);

        $this->actingAsAdmin()
            ->put(route('students.update', $student), ['name' => 'Nombre Nuevo', 'active' => true])
            ->assertRedirect(route('students.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('students', ['id' => $student->id, 'name' => 'Nombre Nuevo']);
    }

    public function test_update_allows_same_dni_for_own_student(): void
    {
        $student = Student::factory()->create(['dni' => '22222222']);

        $this->actingAsAdmin()
            ->put(route('students.update', $student), [
                'name'   => $student->name,
                'dni'    => '22222222',
                'active' => true,
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_update_rejects_dni_of_another_student(): void
    {
        Student::factory()->create(['dni' => '33333333']);
        $other = Student::factory()->create(['dni' => '44444444']);

        $this->actingAsAdmin()
            ->put(route('students.update', $other), [
                'name'   => $other->name,
                'dni'    => '33333333',
                'active' => true,
            ])
            ->assertSessionHasErrors('dni');
    }

    // -------------------------------------------------------------------------
    // destroy (desactivar)
    // -------------------------------------------------------------------------

    public function test_destroy_deactivates_student(): void
    {
        $student = Student::factory()->create(['active' => true]);

        $this->actingAsAdmin()
            ->delete(route('students.destroy', $student))
            ->assertRedirect(route('students.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('students', ['id' => $student->id, 'active' => false]);
    }

    public function test_destroy_does_not_delete_record(): void
    {
        $student = Student::factory()->create();

        $this->actingAsAdmin()->delete(route('students.destroy', $student));

        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }
}
