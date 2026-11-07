<?php

namespace App\Controllers\Api\V1;

use App\Libraries\SeasonVote as Poll;
use CodeIgniter\HTTP\ResponseInterface;

/** POST /api/v1/season-vote and /api/v1/season-vote/snooze */
class SeasonVote extends ApiController
{
    public function vote(): ResponseInterface
    {
        $userId = (int) auth()->id();
        if ($blocked = $this->blocked($userId)) return $blocked;

        $input = $this->input();
        $choice = is_string($input['choice'] ?? null) ? $input['choice'] : '';
        $comment = is_string($input['comment'] ?? null) ? trim($input['comment']) : '';

        $errors = [];
        if (!in_array($choice, Poll::CHOICES, true)) $errors['choice'] = 'Pick one of the two options.';
        if (mb_strlen($comment) > Poll::COMMENT_MAX) $errors['comment'] = 'Keep the comment to ' . Poll::COMMENT_MAX . ' characters.';
        if ($errors) return $this->fail(reset($errors), 422, $errors);

        Poll::vote($userId, $choice, $comment === '' ? null : $comment);
        return $this->ok(Poll::state($userId), 'Thanks. You can change your vote until ' . Poll::closesLabel() . ' in Settings.');
    }

    public function snooze(): ResponseInterface
    {
        $userId = (int) auth()->id();
        if ($blocked = $this->blocked($userId)) return $blocked;
        return $this->ok(['snoozedUntil' => Poll::snooze($userId)]);
    }

    private function blocked(int $userId): ?ResponseInterface
    {
        if (!Poll::isOpen()) return $this->fail('Voting is closed.', 403);
        if (!Poll::eligible($userId)) return $this->fail("This account can't vote. Demo accounts don't take part.", 403);
        return null;
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
