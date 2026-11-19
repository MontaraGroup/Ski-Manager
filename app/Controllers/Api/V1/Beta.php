<?php

namespace App\Controllers\Api\V1;

use App\Libraries\Beta as Program;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * POST /api/v1/beta/join, /beta/leave, /beta/feature/{key}, /beta/feedback/{key}.
 * Signed-in session and CSRF come from the api/v1 group; demo accounts are refused here.
 */
class Beta extends ApiController
{
    /** Feedback saves per player per hour */
    private const FEEDBACK_PER_HOUR = 10;

    public function join(): ResponseInterface
    {
        $userId = (int) auth()->id();
        if ($blocked = $this->blocked($userId)) return $blocked;
        Program::join($userId);
        return $this->ok(Program::state($userId), 'You joined the beta program.');
    }

    public function leave(): ResponseInterface
    {
        $userId = (int) auth()->id();
        if ($blocked = $this->blocked($userId)) return $blocked;
        Program::leave($userId);
        return $this->ok(Program::state($userId), 'You left the beta program.');
    }

    public function feature(string $key): ResponseInterface
    {
        $userId = (int) auth()->id();
        if ($blocked = $this->blocked($userId)) return $blocked;
        $flag = Program::betaFlag($key);
        if (!$flag) return $this->fail("That feature isn't in beta.", 404);
        if (!Program::isMember($userId)) return $this->fail('Join the beta program to switch beta features on.', 403);

        $on = filter_var($this->input()['on'] ?? false, FILTER_VALIDATE_BOOLEAN);
        Program::setFeature($userId, $key, $on);
        $name = Program::cleanName((string) $flag['name']);
        return $this->ok(Program::state($userId), $name . ($on ? ' is now on.' : ' is now off.'));
    }

    public function feedback(string $key): ResponseInterface
    {
        $userId = (int) auth()->id();
        if ($blocked = $this->blocked($userId)) return $blocked;
        if (!Program::betaFlag($key)) return $this->fail("That feature isn't in beta.", 404);

        $input = $this->input();
        $rating = (int) ($input['rating'] ?? 0);
        $comment = is_string($input['comment'] ?? null) ? trim($input['comment']) : '';
        $errors = [];
        if ($rating < 1 || $rating > 5) $errors['rating'] = 'Pick a rating from 1 to 5.';
        if (mb_strlen($comment) > Program::COMMENT_MAX) $errors['comment'] = 'Keep the comment to ' . Program::COMMENT_MAX . ' characters.';
        if ($errors) return $this->fail(reset($errors), 422, $errors);

        if (!service('throttler')->check('beta-feedback-' . $userId, self::FEEDBACK_PER_HOUR, HOUR)) {
            return $this->fail("You've sent a lot of feedback. Try again in a while.", 429);
        }

        Program::saveFeedback($userId, $key, $rating, $comment === '' ? null : $comment);
        return $this->ok(Program::state($userId), 'Thanks for your feedback.');
    }

    private function blocked(int $userId): ?ResponseInterface
    {
        if (!Program::eligible($userId)) return $this->fail("Demo accounts can't join the beta program.", 403);
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
