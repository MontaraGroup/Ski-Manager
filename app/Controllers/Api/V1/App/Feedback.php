<?php

namespace App\Controllers\Api\V1\App;

use App\Controllers\BaseController;
use App\Libraries\AppFeedback;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * POST api/v1/app/feedback (multipart): message, category (bug|idea|other), diagnostics (JSON the
 * player chose to attach, optional), screenshot (PNG/JPEG file, optional). Answers { id }.
 */
class Feedback extends BaseController
{
    use AppResponses;

    public function submit(): ResponseInterface
    {
        $userId = (int) auth()->id();
        if ($why = AppFeedback::rateLimited($userId)) {
            return $this->fail($why, 429, ['code' => 'rate_limited']);
        }

        $message = trim((string) $this->request->getPost('message'));
        $category = (string) $this->request->getPost('category');
        $diagnostics = trim((string) $this->request->getPost('diagnostics'));
        $shot = $this->request->getFile('screenshot');

        $errors = AppFeedback::validate($message, $category, $diagnostics, $shot);
        if ($errors) {
            return $this->fail((string) reset($errors), 422, $errors);
        }

        $id = AppFeedback::store($userId, $message, $category, $diagnostics, $shot);
        return $this->ok(['id' => $id], 'Thanks. Replies show up in Support.', 201);
    }
}
