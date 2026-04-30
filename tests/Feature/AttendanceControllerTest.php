<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Clase;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): static
    {
        return $this->withSession(['pin_authenticated' => true]);
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_shows_only_active_students(): void
    {
        $active = Student::factory()->create(['active' => true, 'name' => 'Activo']);
        $inactive = Student::factory()->create(['active' => false, 'name' => 'Inactivo']);

        $response = $this->actingAsAdmin()->get(route('attendance.index'))->assertOk();

        $students = $response->viewData('students');
        $this->assertEquals(1, $students->count());
        $this->assertEquals($active->id, $students->first()->id);
    }

    // -------------------------------------------------------------------------
    // take — vista de toma de asistencia
    // -------------------------------------------------------------------------

    public function test_take_shows_enrolled_students(): void
    {
        $clase = Clase::factory()->create();
        $student = Student::factory()->create();
        $clase->students()->attach($student->id, ['enrolled_at' => today()->toDateString()]);

        $response = $this->actingAsAdmin()
            ->get(route('attendance.take', $clase))
            ->assertOk();

        $students = $response->viewData('students');
        $this->assertTrue($students->contains('id', $student->id));
    }

    public function test_take_loads_existing_attendance_for_date(): void
    {
        $clase = Clase::factory()->create();
        $student = Student::factory()->create();
        $clase->students()->attach($student->id, ['enrolled_at' => today()->toDateString()]);

        Attendance::create([
            'clase_id' => $clase->id,
            'student_id' => $student->id,
            'date' => today()->toDateString(),
            'present' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->get(route('attendance.take', $clase))
            ->assertOk();

        $existing = $response->viewData('existing');
        $this->assertTrue((bool) $existing[$student->id]);
    }

    public function test_take_accepts_custom_date(): void
    {
        $clase = Clase::factory()->create();
        $date = '2026-03-15';

        $response = $this->actingAsAdmin()
            ->get(route('attendance.take', $clase).'?date='.$date)
            ->assertOk();

        $this->assertEquals($date, $response->viewData('date')->toDateString());
    }

    public function test_take_shows_non_enrolled_students_as_extra(): void
    {
        $clase = Clase::factory()->create();
        $outside = Student::factory()->create(['active' => true]);

        $response = $this->actingAsAdmin()->get(route('attendance.take', $clase))->assertOk();

        $extra = $response->viewData('extraStudents');
        $this->assertTrue($extra->contains('id', $outside->id));
    }

    // -------------------------------------------------------------------------
    // toggle — marcar presente/ausente
    // -------------------------------------------------------------------------

    public function test_toggle_creates_attendance_record(): void
    {
        $clase = Clase::factory()->create();
        $student = Student::factory()->create();
        $clase->students()->attach($student);

        $this->actingAsAdmin()
            ->postJson(route('attendance.toggle', $clase), [
                'student_id' => $student->id,
                'date' => today()->toDateString(),
                'present' => true,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('attendances', [
            'clase_id' => $clase->id,
            'student_id' => $student->id,
            'present' => true,
        ]);
    }

    public function test_toggle_rejects_unenrolled_student(): void
    {
        $clase = Clase::factory()->create();
        $student = Student::factory()->create();

        $this->actingAsAdmin()
            ->postJson(route('attendance.toggle', $clase), [
                'student_id' => $student->id,
                'date' => today()->toDateString(),
                'present' => true,
            ])
            ->assertForbidden();
    }

    public function test_toggle_updates_existing_attendance(): void
    {
        $clase = Clase::factory()->create();
        $student = Student::factory()->create();
        $clase->students()->attach($student);

        Attendance::create([
            'clase_id' => $clase->id,
            'student_id' => $student->id,
            'date' => today()->toDateString(),
            'present' => true,
        ]);

        $this->actingAsAdmin()
            ->postJson(route('attendance.toggle', $clase), [
                'student_id' => $student->id,
                'date' => today()->toDateString(),
                'present' => false,
            ]);

        $this->assertDatabaseHas('attendances', [
            'clase_id' => $clase->id,
            'student_id' => $student->id,
            'present' => false,
        ]);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_toggle_requires_student_id(): void
    {
        $clase = Clase::factory()->create();

        $this->actingAsAdmin()
            ->postJson(route('attendance.toggle', $clase), [
                'date' => today()->toDateString(),
                'present' => true,
            ])
            ->assertUnprocessable();
    }

    // -------------------------------------------------------------------------
    // save — guardar asistencia masiva
    // -------------------------------------------------------------------------

    public function test_save_records_attendance_for_all_enrolled_students(): void
    {
        $clase = Clase::factory()->create();
        [$s1, $s2, $s3] = Student::factory()->count(3)->create()->all();

        $clase->students()->attach(
            [$s1->id => ['enrolled_at' => today()->toDateString()],
                $s2->id => ['enrolled_at' => today()->toDateString()],
                $s3->id => ['enrolled_at' => today()->toDateString()]]
        );

        $this->actingAsAdmin()
            ->post(route('attendance.save', $clase), [
                'date' => today()->toDateString(),
                'present' => [$s1->id => '1', $s2->id => '1'],  // s3 ausente
            ])
            ->assertRedirect(route('attendance.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', ['student_id' => $s1->id, 'present' => true]);
        $this->assertDatabaseHas('attendances', ['student_id' => $s2->id, 'present' => true]);
        $this->assertDatabaseHas('attendances', ['student_id' => $s3->id, 'present' => false]);
    }

    public function test_save_updates_existing_records(): void
    {
        $clase = Clase::factory()->create();
        $student = Student::factory()->create();
        $clase->students()->attach($student->id, ['enrolled_at' => today()->toDateString()]);

        Attendance::create([
            'clase_id' => $clase->id,
            'student_id' => $student->id,
            'date' => today()->toDateString(),
            'present' => false,
        ]);

        $this->actingAsAdmin()
            ->post(route('attendance.save', $clase), [
                'date' => today()->toDateString(),
                'present' => [$student->id => '1'],
            ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'present' => true,
        ]);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_save_requires_date(): void
    {
        $clase = Clase::factory()->create();

        $this->actingAsAdmin()
            ->post(route('attendance.save', $clase), [])
            ->assertSessionHasErrors('date');
    }

    // -------------------------------------------------------------------------
    // addStudent — inscribir y marcar presente
    // -------------------------------------------------------------------------

    public function test_add_student_enrolls_and_marks_present(): void
    {
        $clase = Clase::factory()->create();
        $student = Student::factory()->create();

        $this->actingAsAdmin()
            ->postJson(route('attendance.add-student', $clase), [
                'student_id' => $student->id,
                'date' => today()->toDateString(),
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('clase_student', [
            'clase_id' => $clase->id,
            'student_id' => $student->id,
        ]);

        $this->assertDatabaseHas('attendances', [
            'clase_id' => $clase->id,
            'student_id' => $student->id,
            'present' => true,
        ]);
    }

    public function test_add_student_is_idempotent_for_already_enrolled(): void
    {
        $clase = Clase::factory()->create();
        $student = Student::factory()->create();
        $clase->students()->attach($student->id, ['enrolled_at' => today()->toDateString()]);

        $this->actingAsAdmin()
            ->postJson(route('attendance.add-student', $clase), [
                'student_id' => $student->id,
                'date' => today()->toDateString(),
            ])
            ->assertOk();

        $this->assertDatabaseCount('clase_student', 1);
    }

    public function test_add_student_requires_valid_student_id(): void
    {
        $clase = Clase::factory()->create();

        $this->actingAsAdmin()
            ->postJson(route('attendance.add-student', $clase), [
                'student_id' => 9999,  // no existe
                'date' => today()->toDateString(),
            ])
            ->assertUnprocessable();
    }
}
