<?php

namespace Tests\Feature;

use App\Models\Clase;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): static
    {
        return $this->withSession(['pin_authenticated' => true]);
    }

    // -------------------------------------------------------------------------
    // edit — formulario de matrícula
    // -------------------------------------------------------------------------

    public function test_enroll_form_shows_active_students(): void
    {
        $clase   = Clase::factory()->create();
        $active  = Student::factory()->create(['active' => true]);
        $inactive = Student::factory()->create(['active' => false]);

        $this->actingAsAdmin()
            ->get(route('clases.enroll', $clase))
            ->assertOk()
            ->assertViewHas('allStudents', fn($students) =>
                $students->contains($active) && !$students->contains($inactive)
            );
    }

    public function test_enroll_form_marks_already_enrolled_students(): void
    {
        $clase   = Clase::factory()->create();
        $student = Student::factory()->create();
        $clase->students()->attach($student->id, ['enrolled_at' => today()->toDateString()]);

        $this->actingAsAdmin()
            ->get(route('clases.enroll', $clase))
            ->assertOk()
            ->assertViewHas('enrolledIds', fn($ids) => in_array($student->id, $ids));
    }

    // -------------------------------------------------------------------------
    // update — guardar matrícula
    // -------------------------------------------------------------------------

    public function test_update_enrolls_selected_students(): void
    {
        $clase    = Clase::factory()->create();
        $students = Student::factory()->count(3)->create();

        $this->actingAsAdmin()
            ->post(route('clases.enroll.update', $clase), [
                'student_ids' => $students->pluck('id')->toArray(),
            ])
            ->assertRedirect(route('clases.index'))
            ->assertSessionHas('success');

        foreach ($students as $student) {
            $this->assertDatabaseHas('clase_student', [
                'clase_id'   => $clase->id,
                'student_id' => $student->id,
            ]);
        }
    }

    public function test_update_removes_unenrolled_students(): void
    {
        $clase    = Clase::factory()->create();
        $s1       = Student::factory()->create();
        $s2       = Student::factory()->create();
        $clase->students()->attach([$s1->id, $s2->id], ['enrolled_at' => today()->toDateString()]);

        // Solo mantener s1
        $this->actingAsAdmin()
            ->post(route('clases.enroll.update', $clase), ['student_ids' => [$s1->id]]);

        $this->assertDatabaseHas('clase_student', ['clase_id' => $clase->id, 'student_id' => $s1->id]);
        $this->assertDatabaseMissing('clase_student', ['clase_id' => $clase->id, 'student_id' => $s2->id]);
    }

    public function test_update_with_empty_list_removes_all_students(): void
    {
        $clase   = Clase::factory()->create();
        $student = Student::factory()->create();
        $clase->students()->attach($student->id, ['enrolled_at' => today()->toDateString()]);

        $this->actingAsAdmin()
            ->post(route('clases.enroll.update', $clase), ['student_ids' => []]);

        $this->assertDatabaseMissing('clase_student', ['clase_id' => $clase->id]);
    }
}
