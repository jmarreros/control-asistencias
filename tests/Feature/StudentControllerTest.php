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
    // index — planStatus, isExpiring y URLs WhatsApp
    // -------------------------------------------------------------------------

    public function test_index_sets_plan_status_ok_for_active_plan(): void
    {
        $student = Student::factory()->create();
        \App\Models\StudentPlan::factory()->active()->create(['student_id' => $student->id]);

        $response = $this->actingAsAdmin()->get(route('students.index'))->assertOk();

        $found = $response->viewData('students')->firstWhere('id', $student->id);
        $this->assertEquals('ok', $found->planStatus);
    }

    public function test_index_sets_plan_status_expired_for_expired_plan(): void
    {
        $student = Student::factory()->create();
        \App\Models\StudentPlan::factory()->expired()->create(['student_id' => $student->id]);

        $response = $this->actingAsAdmin()->get(route('students.index'))->assertOk();

        $found = $response->viewData('students')->firstWhere('id', $student->id);
        $this->assertEquals('expired', $found->planStatus);
    }

    public function test_index_sets_no_plan_status_when_student_has_no_plan(): void
    {
        $student = Student::factory()->create();

        $response = $this->actingAsAdmin()->get(route('students.index'))->assertOk();

        $found = $response->viewData('students')->firstWhere('id', $student->id);
        $this->assertEquals('no_plan', $found->planStatus);
        $this->assertFalse($found->isExpiring);
    }

    public function test_index_flags_expiring_when_end_date_within_days_before(): void
    {
        // notify_days_before default = 3; plan vence en 2 días → debe marcar isExpiring
        $student = Student::factory()->create(['phone' => '987000001']);
        \App\Models\StudentPlan::factory()->create([
            'student_id'  => $student->id,
            'start_date'  => now()->subDays(10)->toDateString(),
            'end_date'    => now()->addDays(2)->toDateString(),
            'class_quota' => '16',  // cuota alta → no exhausted
        ]);

        $response = $this->actingAsAdmin()->get(route('students.index'))->assertOk();

        $found = $response->viewData('students')->firstWhere('id', $student->id);
        $this->assertTrue($found->isExpiring);
    }

    public function test_index_does_not_flag_expiring_when_end_date_far(): void
    {
        $student = Student::factory()->create();
        \App\Models\StudentPlan::factory()->active()->create(['student_id' => $student->id]);
        // active() pone end_date en 25 días → muy lejos del umbral de 3

        $response = $this->actingAsAdmin()->get(route('students.index'))->assertOk();

        $found = $response->viewData('students')->firstWhere('id', $student->id);
        $this->assertFalse($found->isExpiring);
    }

    public function test_index_generates_whatsapp_url_for_expiring_student_with_phone(): void
    {
        $student = Student::factory()->create(['name' => 'Ana García', 'phone' => '987654321']);
        \App\Models\StudentPlan::factory()->create([
            'student_id'  => $student->id,
            'start_date'  => now()->subDays(10)->toDateString(),
            'end_date'    => now()->addDays(1)->toDateString(),
            'class_quota' => '16',
        ]);

        $response = $this->actingAsAdmin()->get(route('students.index'))->assertOk();

        $found = $response->viewData('students')->firstWhere('id', $student->id);
        $this->assertTrue($found->isExpiring);
        $this->assertStringContainsString('wa.me/51987654321', $found->waUrl);
        $this->assertStringContainsString('Ana', rawurldecode($found->waUrl));
    }

    public function test_index_no_whatsapp_url_when_expiring_student_has_no_phone(): void
    {
        $student = Student::factory()->create(['phone' => null]);
        \App\Models\StudentPlan::factory()->create([
            'student_id'  => $student->id,
            'start_date'  => now()->subDays(10)->toDateString(),
            'end_date'    => now()->addDays(1)->toDateString(),
            'class_quota' => '16',
        ]);

        $response = $this->actingAsAdmin()->get(route('students.index'))->assertOk();

        $found = $response->viewData('students')->firstWhere('id', $student->id);
        $this->assertTrue($found->isExpiring);
        $this->assertNull($found->waUrl);
    }

    public function test_index_generates_expired_whatsapp_url_for_expired_student(): void
    {
        $student = Student::factory()->create(['phone' => '912345678']);
        \App\Models\StudentPlan::factory()->expired()->create(['student_id' => $student->id]);

        $response = $this->actingAsAdmin()->get(route('students.index'))->assertOk();

        $found = $response->viewData('students')->firstWhere('id', $student->id);
        $this->assertNotNull($found->waUrlExpired);
        $this->assertStringContainsString('wa.me/51912345678', $found->waUrlExpired);
    }

    public function test_index_no_expired_whatsapp_url_when_expired_student_has_no_phone(): void
    {
        $student = Student::factory()->create(['phone' => null]);
        \App\Models\StudentPlan::factory()->expired()->create(['student_id' => $student->id]);

        $response = $this->actingAsAdmin()->get(route('students.index'))->assertOk();

        $found = $response->viewData('students')->firstWhere('id', $student->id);
        $this->assertNull($found->waUrlExpired);
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
