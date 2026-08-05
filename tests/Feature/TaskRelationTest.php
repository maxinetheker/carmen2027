<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\TaskItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_related_record_search_is_filtered_and_validated_by_type(): void
    {
        $this->seed();
        $user = User::firstOrFail();
        $lead = Lead::firstOrFail();

        $this->actingAs($user)->get(route('admin.tasks.create'))
            ->assertOk()
            ->assertSee('data-depends-on="related_type"', false)
            ->assertSee('data-option-group="lead"', false)
            ->assertSee($lead->full_name);

        $payload = [
            'title' => 'Llamar al prospecto',
            'priority' => 'high',
            'status' => 'pending',
            'related_type' => 'lead',
            'related_id' => $lead->id,
        ];

        $this->actingAs($user)->post(route('admin.tasks.store'), $payload)
            ->assertRedirect(route('admin.tasks.index'));
        $this->assertDatabaseHas('task_items', [
            'title' => 'Llamar al prospecto',
            'related_type' => 'lead',
            'related_id' => $lead->id,
        ]);

        $this->actingAs($user)->post(route('admin.tasks.store'), [
            ...$payload,
            'title' => 'Relación inválida',
            'related_id' => TaskItem::max('id') + 99999,
        ])->assertSessionHasErrors('related_id');
    }
}
