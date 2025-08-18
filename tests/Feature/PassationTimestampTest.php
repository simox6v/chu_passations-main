<?php

namespace Tests\Feature;

use App\Models\Passation;
use App\Models\User;
use App\Models\Salle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class PassationTimestampTest extends TestCase
{
    use RefreshDatabase;

    public function test_passation_creation_enforces_system_timestamp()
    {
        // Create test user and salle
        $user = User::factory()->create(['role' => 'medecin']);
        $salle = Salle::factory()->create();

        // Set a fixed time for testing
        $fixedTime = Carbon::create(2025, 8, 18, 12, 0, 0);
        Carbon::setTestNow($fixedTime);

        // Create passation
        $passation = Passation::create([
            'nom_patient' => 'Test Patient',
            'user_id' => $user->id,
            'salle_id' => $salle->id,
            'date_passation' => '2025-01-01 00:00:00', // Try to set a different date
            // Try to set created_at to a different time (should be overridden)
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00',
        ]);

        // Refresh the model to get the actual database values
        $passation->refresh();

        // Assert that timestamps are set to system time (fixed time for testing)
        $this->assertEquals($fixedTime->format('Y-m-d H:i:s'), $passation->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals($fixedTime->format('Y-m-d H:i:s'), $passation->updated_at->format('Y-m-d H:i:s'));

        // Reset Carbon test time
        Carbon::setTestNow();
    }

    public function test_passation_update_enforces_system_timestamp()
    {
        // Create test user and salle
        $user = User::factory()->create(['role' => 'medecin']);
        $salle = Salle::factory()->create();

        // Create initial passation
        $passation = Passation::create([
            'nom_patient' => 'Test Patient',
            'user_id' => $user->id,
            'salle_id' => $salle->id,
        ]);

        $originalCreatedAt = $passation->created_at;

        // Set a different fixed time for update
        $updateTime = Carbon::create(2025, 8, 18, 13, 0, 0);
        Carbon::setTestNow($updateTime);

        // Update the passation
        $passation->update([
            'nom_patient' => 'Updated Patient',
            // Try to manually set updated_at (should be overridden)
            'updated_at' => '2025-01-01 00:00:00',
        ]);

        // Refresh the model to get the actual database values
        $passation->refresh();

        // Assert that created_at remains unchanged but updated_at is set to system time
        $this->assertEquals($originalCreatedAt->format('Y-m-d H:i:s'), $passation->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals($updateTime->format('Y-m-d H:i:s'), $passation->updated_at->format('Y-m-d H:i:s'));

        // Reset Carbon test time
        Carbon::setTestNow();
    }

    public function test_date_passation_defaults_to_current_date_when_empty()
    {
        // Create test user and salle
        $user = User::factory()->create(['role' => 'medecin']);
        $salle = Salle::factory()->create();

        // Set a fixed time for testing
        $fixedTime = Carbon::create(2025, 8, 18, 12, 0, 0);
        Carbon::setTestNow($fixedTime);

        // Create passation without date_passation
        $passation = Passation::create([
            'nom_patient' => 'Test Patient',
            'user_id' => $user->id,
            'salle_id' => $salle->id,
            // No date_passation provided
        ]);

        // Refresh the model to get the actual database values
        $passation->refresh();

        // Assert that date_passation is set to current system time
        $this->assertEquals($fixedTime->format('Y-m-d H:i:s'), $passation->date_passation->format('Y-m-d H:i:s'));

        // Reset Carbon test time
        Carbon::setTestNow();
    }
}