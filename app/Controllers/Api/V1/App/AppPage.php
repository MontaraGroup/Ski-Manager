<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * A website page for the app: GET answers the page's pageData(), POST runs one of the web
 * controller's own actions and answers the page again. The using class sets:
 *   ACTIONS: web methods the app may call through act()
 *   UNLOCK:  achievement-unlocked feature key, as in checkFeatureUnlock(), or null
 *   HIDDEN:  difficulty hidden_pages key, as in isPageHidden(), or null
 */
trait AppPage
{
    use AppResponses;

    public function show(): ResponseInterface
    {
        return $this->appGate() ?? $this->ok($this->appData());
    }

    /** Route as 'Page::act/method/$1': $method must be listed in ACTIONS */
    public function act(string $method, string ...$args): ResponseInterface
    {
        if (!in_array($method, static::ACTIONS, true)) {
            return $this->fail('Unknown action.', 404);
        }
        if ($deny = $this->appGate()) {
            return $deny;
        }
        if (empty($this->request->getPost()) && $json = $this->request->getJSON(true)) {
            $this->request->setGlobal('post', $json);
        }
        return $this->fromAction($this->$method(...$args), fn() => $this->appData());
    }

    protected function appData(): array
    {
        return $this->pageData();
    }

    /** The answer when this player can't use the page, or null when they can */
    protected function appGate(): ?ResponseInterface
    {
        if (static::HIDDEN !== null && isPageHidden(static::HIDDEN)) {
            return $this->fail('This page is not part of your difficulty.', 404, ['code' => 'hidden']);
        }
        if (static::UNLOCK !== null && (int) auth()->id() !== 1 && !isFeatureUnlocked(static::UNLOCK)) {
            $unlock = getUnlockRequirement(static::UNLOCK);
            $how = $unlock ? ' Complete the achievement "' . $unlock['name'] . '" to unlock it: ' . $unlock['desc'] : '';
            return $this->fail('This feature is locked.' . $how, 403, ['code' => 'locked']);
        }
        return null;
    }
}
