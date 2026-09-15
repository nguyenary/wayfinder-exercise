<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\TourEnquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_submission_creates_an_enquiry_with_new_status(): void
    {
        $tour = Tour::create(['name' => 'Ha Long Bay Cruise', 'slug' => 'ha-long-bay-cruise']);

        $response = $this->postJson('/api/enquiries', [
            'tour_id' => $tour->id,
            'name' => 'Nguyen Van A',
            'email' => 'a@example.com',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('status', 'new');
        $this->assertDatabaseHas('tour_enquiries', [
            'email' => 'a@example.com',
            'status' => 'new',
        ]);
    }

    public function test_submitter_cannot_set_their_own_status(): void
    {
        $tour = Tour::create(['name' => 'Ha Long Bay Cruise', 'slug' => 'ha-long-bay-cruise']);

        $response = $this->postJson('/api/enquiries', [
            'tour_id' => $tour->id,
            'name' => 'Nguyen Van A',
            'email' => 'a@example.com',
            'status' => 'booked',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('status', 'new');
        $this->assertDatabaseHas('tour_enquiries', [
            'email' => 'a@example.com',
            'status' => 'new',
        ]);
    }

    public function test_submission_missing_required_fields_is_rejected(): void
    {
        $response = $this->postJson('/api/enquiries', [
            'name' => 'Nguyen Van A',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('tour_enquiries', 0);
    }

    public function test_validation_errors_are_returned_as_json_even_without_an_accept_header(): void
    {
        $response = $this->post('/api/enquiries', [
            'name' => 'Nguyen Van A',
        ]);

        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_submission_with_invalid_email_is_rejected(): void
    {
        $tour = Tour::create(['name' => 'Ha Long Bay Cruise', 'slug' => 'ha-long-bay-cruise']);

        $response = $this->postJson('/api/enquiries', [
            'tour_id' => $tour->id,
            'name' => 'Nguyen Van A',
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('tour_enquiries', 0);
    }

    public function test_submission_with_nonexistent_tour_is_rejected(): void
    {
        $response = $this->postJson('/api/enquiries', [
            'tour_id' => 999,
            'name' => 'Nguyen Van A',
            'email' => 'a@example.com',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('tour_enquiries', 0);
    }

    public function test_allowed_status_transition_succeeds(): void
    {
        $tour = Tour::create(['name' => 'Ha Long Bay Cruise', 'slug' => 'ha-long-bay-cruise']);
        $enquiry = TourEnquiry::create([
            'tour_id' => $tour->id,
            'name' => 'Nguyen Van A',
            'email' => 'a@example.com',
            'status' => 'new',
        ]);

        $response = $this->patchJson("/api/enquiries/{$enquiry->id}/status", [
            'status' => 'contacted',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'contacted');
        $this->assertDatabaseHas('tour_enquiries', [
            'id' => $enquiry->id,
            'status' => 'contacted',
        ]);
    }

    public function test_disallowed_status_transition_is_rejected_and_status_stays_unchanged(): void
    {
        $tour = Tour::create(['name' => 'Ha Long Bay Cruise', 'slug' => 'ha-long-bay-cruise']);
        $enquiry = TourEnquiry::create([
            'tour_id' => $tour->id,
            'name' => 'Nguyen Van A',
            'email' => 'a@example.com',
            'status' => 'new',
        ]);

        $response = $this->patchJson("/api/enquiries/{$enquiry->id}/status", [
            'status' => 'booked',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('tour_enquiries', [
            'id' => $enquiry->id,
            'status' => 'new',
        ]);
    }

    public function test_index_returns_enquiries_with_tour_name(): void
    {
        $tour = Tour::create(['name' => 'Ha Long Bay Cruise', 'slug' => 'ha-long-bay-cruise']);
        TourEnquiry::create([
            'tour_id' => $tour->id,
            'name' => 'Nguyen Van A',
            'email' => 'a@example.com',
            'status' => 'new',
        ]);

        $response = $this->getJson('/api/enquiries');

        $response->assertOk();
        $response->assertJsonFragment(['tour_name' => 'Ha Long Bay Cruise']);
    }
}
