<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): static
    {
        return $this->withSession(['pin_authenticated' => true]);
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_plans_page_is_accessible(): void
    {
        $student = Student::factory()->create();

        $this->actingAsAdmin()
            ->get(route('students.plans.index', $student))
            ->assertOk()
            ->assertViewIs('students.plans');
    }

    public function test_plans_page_shows_all_plans_including_cancelled(): void
    {
        $student = Student::factory()->create();
        $active  = StudentPlan::factory()->active()->create(['student_id' => $student->id]);
        $deleted = StudentPlan::factory()->expired()->create(['student_id' => $student->id]);
        $deleted->delete();

        $response = $this->actingAsAdmin()->get(route('students.plans.index', $student));

        $plans = $response->viewData('plans');
        $this->assertEquals(2, $plans->count());
    }

    // -------------------------------------------------------------------------
    // store — registrar nuevo plan
    // -------------------------------------------------------------------------

    public function test_store_creates_plan_with_valid_data(): void
    {
        $student = Student::factory()->create();
        $payload = [
            'start_date'  => now()->addDays(1)->toDateString(),
            'end_date'    => now()->addMonths(1)->toDateString(),
            'class_quota' => '8',
            'price'       => 120,
        ];

        $this->actingAsAdmin()
            ->post(route('students.plans.store', $student), $payload)
            ->assertRedirect(route('students.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('student_plans', [
            'student_id'  => $student->id,
            'class_quota' => '8',
        ]);
    }

    public function test_store_with_promotion(): void
    {
        $student = Student::factory()->create();
        $payload = [
            'start_date'  => now()->addDays(1)->toDateString(),
            'end_date'    => now()->addMonths(1)->toDateString(),
            'class_quota' => '12',
            'price'       => 135,
            'promotion'   => 'promo_10',
        ];

        $this->actingAsAdmin()->post(route('students.plans.store', $student), $payload);

        $this->assertDatabaseHas('student_plans', [
            'student_id' => $student->id,
            'promotion'  => 'promo_10',
        ]);
    }

    public function test_store_requires_start_date(): void
    {
        $student = Student::factory()->create();

        $this->actingAsAdmin()
            ->post(route('students.plans.store', $student), [
                'end_date'    => now()->addMonths(1)->toDateString(),
                'class_quota' => '8',
            ])
            ->assertSessionHasErrors('start_date');
    }

    public function test_store_requires_end_date_after_start_date(): void
    {
        $student = Student::factory()->create();

        $this->actingAsAdmin()
            ->post(route('students.plans.store', $student), [
                'start_date'  => now()->addMonths(1)->toDateString(),
                'end_date'    => now()->toDateString(),
                'class_quota' => '8',
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_store_requires_valid_class_quota(): void
    {
        $student = Student::factory()->create();

        $this->actingAsAdmin()
            ->post(route('students.plans.store', $student), [
                'start_date'  => now()->toDateString(),
                'end_date'    => now()->addMonths(1)->toDateString(),
                'class_quota' => '5',   // valor inválido
            ])
            ->assertSessionHasErrors('class_quota');
    }

    public function test_store_blocks_new_plan_when_active_plan_exists(): void
    {
        $student = Student::factory()->create();
        StudentPlan::factory()->active()->create(['student_id' => $student->id]);

        $this->actingAsAdmin()
            ->post(route('students.plans.store', $student), [
                'start_date'  => now()->toDateString(),
                'end_date'    => now()->addMonths(1)->toDateString(),
                'class_quota' => '8',
            ])
            ->assertSessionHas('error');

        // Solo debe existir el plan original
        $this->assertEquals(1, StudentPlan::where('student_id', $student->id)->count());
    }

    public function test_store_allows_new_plan_when_previous_is_expired(): void
    {
        $student = Student::factory()->create();
        StudentPlan::factory()->expired()->create(['student_id' => $student->id]);

        $this->actingAsAdmin()
            ->post(route('students.plans.store', $student), [
                'start_date'  => now()->toDateString(),
                'end_date'    => now()->addMonths(1)->toDateString(),
                'class_quota' => '8',
            ])
            ->assertSessionHasNoErrors();

        $this->assertEquals(2, StudentPlan::where('student_id', $student->id)->count());
    }

    public function test_store_allows_new_plan_when_previous_is_pending(): void
    {
        $student = Student::factory()->create();
        StudentPlan::factory()->pending()->create(['student_id' => $student->id]);

        // El plan pending no está activo (status = 'pending', no 'ok'), debe permitir crear otro
        $this->actingAsAdmin()
            ->post(route('students.plans.store', $student), [
                'start_date'  => now()->toDateString(),
                'end_date'    => now()->addMonths(1)->toDateString(),
                'class_quota' => '8',
            ])
            ->assertSessionHasNoErrors();
    }

    // -------------------------------------------------------------------------
    // destroy — cancelar plan (soft delete)
    // -------------------------------------------------------------------------

    public function test_destroy_soft_deletes_plan(): void
    {
        $student = Student::factory()->create();
        $plan    = StudentPlan::factory()->active()->create(['student_id' => $student->id]);

        $this->actingAsAdmin()
            ->delete(route('students.plans.destroy', [$student, $plan]))
            ->assertRedirect(route('students.plans.index', $student))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('student_plans', ['id' => $plan->id]);
    }

    public function test_destroy_keeps_plan_in_history(): void
    {
        $student = Student::factory()->create();
        $plan    = StudentPlan::factory()->active()->create(['student_id' => $student->id]);

        $this->actingAsAdmin()
            ->delete(route('students.plans.destroy', [$student, $plan]));

        $this->assertNotNull(StudentPlan::withTrashed()->find($plan->id));
    }
}
