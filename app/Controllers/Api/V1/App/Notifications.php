<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

class Notifications extends \App\Controllers\Notifications
{
    use AppResponses;

    /** Same data as the web page. filter, unreadCount, totalCount and items[].id/title/message/icon/isRead/time keep their shape. */
    public function show(): ResponseInterface
    {
        return $this->ok($this->pageData());
    }

    public function markAllRead(): ResponseInterface
    {
        return $this->fromAction($this->readAll());
    }

    public function markOneRead(int $id): ResponseInterface
    {
        return $this->fromAction($this->markRead($id), fn() => ['unread' => unreadNotificationCount((int) auth()->id())]);
    }
}
