<?php

namespace App\Controllers\Api\V1\App;

use App\Controllers\BaseController;
use App\Libraries\Shell;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * The website's navigation (Shell::NAV) as the player may see it: switched-off features left out,
 * locked ones marked. The app's More tab is built from this, so both stay in step.
 */
class Menu extends BaseController
{
    use AppResponses;

    /** Pages the website reaches through search and in-page links rather than the sidebar */
    private const EXTRA = ['title' => 'More pages', 'items' => [
        ['url' => '/insurance',   'label' => 'Insurance',     'icon' => 'fa-shield-halved',  'hide' => 'insurance'],
        ['url' => '/government',  'label' => 'Government',    'icon' => 'fa-building-columns', 'hide' => 'government'],
        ['url' => '/environment', 'label' => 'Environment',   'icon' => 'fa-leaf'],
        ['url' => '/morale',      'label' => 'Staff morale',  'icon' => 'fa-face-smile'],
        ['url' => '/updates',     'label' => 'Updates',       'icon' => 'fa-newspaper'],
        ['url' => '/beta',        'label' => 'Beta program',  'icon' => 'fa-flask'],
    ]];

    public function show(): ResponseInterface
    {
        $isAdmin = Shell::isAdmin();
        $bonusDue = Shell::bonusDue();
        $sections = [];
        foreach (Shell::NAV + [self::EXTRA['title'] => self::EXTRA] as $title => $section) {
            $items = [];
            foreach ($section['items'] as $it) {
                $item = Shell::resolveItem($it, $isAdmin, $bonusDue);
                if ($item === null) continue;
                $items[] = [
                    'path' => ltrim($item['url'], '/'),
                    'url' => $item['url'],
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'locked' => $item['locked'],
                    'external' => $item['external'],
                    'bonusDue' => $item['bonusDue'],
                    'isNew' => $item['isNew'],
                    'beta' => $item['beta'],
                ];
            }
            if ($items) $sections[] = ['title' => $title, 'items' => $items];
        }
        return $this->ok(['sections' => $sections]);
    }
}
