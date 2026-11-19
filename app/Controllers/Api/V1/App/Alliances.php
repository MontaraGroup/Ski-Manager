<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

/** The web controller's directory(), leaderboard() and view() render pages, so the app's GETs use other names */
class Alliances extends \App\Controllers\Alliances
{
    use AppPage;

    protected const ACTIONS = ['create', 'join', 'leave', 'donate', 'upgradePass', 'unlockPerk', 'manageMember', 'reviewApplication'];
    protected const UNLOCK = null;
    protected const HIDDEN = null;

    /** mode "member": the player's alliance. mode "directory": they have none yet */
    protected function appData(): array
    {
        $page = $this->dashboardData();
        return $page === null
            ? ['mode' => 'directory', 'page' => $this->directoryData()]
            : ['mode' => 'member', 'page' => $page];
    }

    public function directoryShow(): ResponseInterface
    {
        return $this->ok($this->directoryData());
    }

    public function leaderboardShow(): ResponseInterface
    {
        return $this->ok($this->leaderboardData());
    }

    public function allianceShow(int $id): ResponseInterface
    {
        $page = $this->viewData($id);
        return $page === null ? $this->fail('Alliance not found.', 404, ['code' => 'not_found']) : $this->ok($page);
    }
}
