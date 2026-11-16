<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\TitleReign;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TitleReignReadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_a_championships_reigns_ordered_by_won_on(): void
    {
        $championship = Championship::factory()->create();
        $other = Championship::factory()->create();

        $later = TitleReign::factory()->create([
            'championship_id' => $championship->id,
            'won_on' => '2021-06-01',
            'lost_on' => null,
        ]);
        $earlier = TitleReign::factory()->create([
            'championship_id' => $championship->id,
            'won_on' => '2020-01-01',
            'lost_on' => '2021-06-01',
        ]);
        TitleReign::factory()->create(['championship_id' => $other->id]);

        $response = $this->getJson("/api/championships/{$championship->slug}/title-reigns");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $earlier->id)
            ->assertJsonPath('data.1.id', $later->id)
            ->assertJsonPath('data.0.championship.id', $championship->id)
            ->assertJsonPath('meta.page.total', 2);
    }

    public function test_index_is_paginated(): void
    {
        $championship = Championship::factory()->create();
        TitleReign::factory()->count(3)->create([
            'championship_id' => $championship->id,
            'lost_on' => '2021-01-01',
        ]);

        $response = $this->getJson("/api/championships/{$championship->id}/title-reigns?per_page=2");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.page.per_page', 2)
            ->assertJsonPath('meta.page.total', 3)
            ->assertJsonPath('meta.page.last_page', 2);
    }

    public function test_index_returns_404_for_unknown_championship(): void
    {
        $this->getJson('/api/championships/does-not-exist/title-reigns')
            ->assertStatus(404);
    }

    public function test_index_filters_by_current(): void
    {
        $championship = Championship::factory()->create();
        $ended = TitleReign::factory()->create([
            'championship_id' => $championship->id,
            'won_on' => '2020-01-01',
            'lost_on' => '2020-06-01',
        ]);
        $current = TitleReign::factory()->create([
            'championship_id' => $championship->id,
            'won_on' => '2020-06-01',
            'lost_on' => null,
        ]);

        $this->getJson("/api/championships/{$championship->id}/title-reigns?current=1")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $current->id)
            ->assertJsonPath('data.0.status', 'current');

        $this->getJson("/api/championships/{$championship->id}/title-reigns?current=0")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ended->id);
    }

    public function test_index_filters_vacated_by_vacancy_reason(): void
    {
        $championship = Championship::factory()->create();
        TitleReign::factory()->create([
            'championship_id' => $championship->id,
            'won_on' => '2020-01-01',
            'lost_on' => '2020-02-01',
            'lost_at' => 'Some Event',
            'vacancy_reason' => null,
        ]);
        $vacated = TitleReign::factory()->create([
            'championship_id' => $championship->id,
            'won_on' => '2020-02-01',
            'lost_on' => '2020-03-01',
            'lost_at' => null,
            'vacancy_reason' => 'Injury',
        ]);

        $this->getJson("/api/championships/{$championship->id}/title-reigns?vacated=1")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $vacated->id)
            ->assertJsonPath('data.0.status', 'vacated');

        $this->getJson("/api/championships/{$championship->id}/title-reigns?vacated=0")
            ->assertJsonCount(1, 'data');
    }

    public function test_index_filters_by_wrestler_win_type_and_won_on_range(): void
    {
        $championship = Championship::factory()->create();
        $wrestler = Wrestler::factory()->create();

        $match = TitleReign::factory()->create([
            'championship_id' => $championship->id,
            'wrestler_id' => $wrestler->id,
            'won_on' => '2020-05-01',
            'lost_on' => '2020-06-01',
            'win_type' => 'pinfall',
        ]);
        TitleReign::factory()->create([
            'championship_id' => $championship->id,
            'won_on' => '2020-07-01',
            'lost_on' => '2020-08-01',
            'win_type' => 'submission',
        ]);
        TitleReign::factory()->create([
            'championship_id' => $championship->id,
            'wrestler_id' => $wrestler->id,
            'won_on' => '2022-01-01',
            'lost_on' => '2022-02-01',
            'win_type' => 'pinfall',
        ]);

        $this->getJson("/api/championships/{$championship->id}/title-reigns?wrestler_id={$wrestler->id}")
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/championships/{$championship->id}/title-reigns?win_type=pinfall")
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/championships/{$championship->id}/title-reigns?won_on_from=2020-01-01&won_on_to=2020-12-31")
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/championships/{$championship->id}/title-reigns?wrestler_id={$wrestler->id}&win_type=pinfall&won_on_to=2020-12-31")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_index_rejects_invalid_filters(): void
    {
        $championship = Championship::factory()->create();

        $this->getJson("/api/championships/{$championship->id}/title-reigns?win_type=flying-elbow")
            ->assertStatus(422);

        $this->getJson("/api/championships/{$championship->id}/title-reigns?won_on_from=not-a-date")
            ->assertStatus(422);
    }

    public function test_show_returns_a_single_reign(): void
    {
        $wrestler = Wrestler::factory()->create();
        $reign = TitleReign::factory()->create([
            'wrestler_id' => $wrestler->id,
            'won_on' => '2020-01-01',
            'lost_on' => '2020-03-01',
            'lost_at' => 'Big Show',
        ]);

        $response = $this->getJson("/api/title-reigns/{$reign->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $reign->id)
            ->assertJsonPath('data.championship.id', $reign->championship_id)
            ->assertJsonPath('data.wrestlers.0.wrestler.id', $wrestler->id)
            ->assertJsonPath('data.won_on', '2020-01-01')
            ->assertJsonPath('data.lost_on', '2020-03-01')
            ->assertJsonPath('data.status', 'ended')
            ->assertJsonPath('data.reign_length', 60);
    }

    public function test_show_returns_404_for_unknown_reign(): void
    {
        $this->getJson('/api/title-reigns/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    }
}
