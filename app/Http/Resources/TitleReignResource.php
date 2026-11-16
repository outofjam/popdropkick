<?php

namespace App\Http\Resources;

/**
 * Canonical shape for a single title reign, used by the title-reign
 * index/show/store/update responses. Matches the nested entries on a
 * championship's `title_reigns`, plus a championship reference and status.
 */
class TitleReignResource extends BaseResource
{
    public function toArray($request): array
    {
        $reign = $this->resource;

        return array_merge(
            [
                'id' => $reign->id,
                'detail_url' => $this->detailUrl('title-reigns.show', $reign->id),
                'status' => $this->status(),
                'championship' => $this->formatChampionshipReference($reign->championship),
                'team' => $this->formatTeamReference($reign->team),
                'wrestlers' => $this->formatParticipants($reign),
                'won_on' => $this->formatDate($reign->won_on),
                'won_at' => $reign->won_at,
                'lost_on' => $this->formatDate($reign->lost_on),
                'lost_at' => $reign->lost_at,
                'vacancy_reason' => $reign->vacancy_reason,
                'win_type' => $reign->win_type,
                'reign_length' => $reign->reign_length_in_days,
                'reign_length_human' => $reign->reign_length_human,
            ],
            $this->formatTimestamps()
        );
    }

    /**
     * current: still held. vacated: ended because the title was vacated
     * (vacancy_reason set). ended: lost to another champion.
     */
    private function status(): string
    {
        if ($this->resource->lost_on === null) {
            return 'current';
        }

        return $this->resource->vacancy_reason !== null ? 'vacated' : 'ended';
    }
}
