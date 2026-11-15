<?php

namespace App\Controllers\Api\V1;

use App\Libraries\ResortVote as Poll;
use CodeIgniter\HTTP\ResponseInterface;

/** GET /api/v1/resort-vote/search?q=, POST /api/v1/resort-vote and /api/v1/resort-vote/retract */
class ResortVote extends ApiController
{
    public function search(): ResponseInterface
    {
        if (!Poll::flagOn()) return $this->fail('Not found.', 404);
        return $this->ok(Poll::search((string) $this->request->getGet('q')));
    }

    public function vote(): ResponseInterface
    {
        $userId = (int) auth()->id();
        [$round, $blocked] = $this->openRound($userId);
        if ($blocked) return $blocked;

        $input = $this->input();
        $areaId = (int) ($input['areaId'] ?? 0);
        $area = $areaId > 0 ? Poll::votable($areaId) : null;
        if (!$area) return $this->fail('Pick a resort from the list.', 422, ['areaId' => 'Pick a resort from the list.']);

        Poll::vote((int) $round['id'], $userId, $areaId);
        return $this->ok(Poll::page($userId), 'You voted for ' . $area['name'] . '.');
    }

    public function retract(): ResponseInterface
    {
        $userId = (int) auth()->id();
        [$round, $blocked] = $this->openRound($userId);
        if ($blocked) return $blocked;
        Poll::retract((int) $round['id'], $userId);
        return $this->ok(Poll::page($userId), 'Vote removed.');
    }

    /** [round, null] while the player may vote, or [null, error response] */
    private function openRound(int $userId): array
    {
        if (!Poll::flagOn()) return [null, $this->fail('Not found.', 404)];
        $round = Poll::rollover();
        if (!Poll::isOpen($round)) return [null, $this->fail('Voting for ' . Poll::monthLabel($round['month']) . ' is closed.', 403)];
        if (!Poll::eligible($userId)) return [null, $this->fail("This account can't vote. Demo accounts don't take part.", 403)];
        return [$round, null];
    }

    private function input(): array
    {
        try {
            $json = $this->request->getJSON(true);
        } catch (\Throwable) {
            $json = null;
        }
        return is_array($json) ? $json : (array) $this->request->getPost();
    }
}
