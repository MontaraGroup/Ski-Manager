<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * /guides: public how-to articles. Their numbers are read from the game's own rules (Economy,
 * Weather, Grooming, Snowmaking, Fleet, Bank, Staff...) so they stay true when the rules change.
 */
class Guides extends BaseController
{
    /** slug => [title, one-line summary, icon] in reading order */
    public const GUIDES = [
        'getting-started' => ['Getting started: your first winter', 'What to build first, what it costs to run, and how a game day works.', 'fa-flag-checkered'],
        'ticket-pricing' => ['Ticket pricing and skier demand', 'How many skiers want to come, and how your ticket prices change that.', 'fa-ticket'],
        'lifts' => ['Lifts: capacity, wear and repairs', 'How much a lift carries, what it costs to run, and keeping it in shape.', 'fa-cable-car'],
        'grooming' => ['Grooming and slope condition', 'Why slopes wear out, what that costs you, and how the night shift fixes it.', 'fa-tractor'],
        'snow-and-weather' => ['Weather, snow base and snowmaking', 'How the season unfolds, what weather does to skiers, and when snow guns pay off.', 'fa-snowflake'],
        'staff' => ['Hiring the right staff', 'What every role really does, and how to avoid paying people to stand around.', 'fa-users'],
        'money' => ['Money: profit, loans and repairs', 'Reading your daily results, borrowing sensibly, and what repairs cost.', 'fa-coins'],
    ];

    public function index(): string
    {
        $n = 0;
        $formatted = [];
        foreach (self::GUIDES as $slug => [$title, $summary, $icon]) {
            $n++;
            $cleanIcon = str_replace(['fa-solid ', 'fa-regular ', 'fa-brands '], '', $icon);
            $formatted[] = [
                'number' => $n,
                'slug' => $slug,
                'title' => $title,
                'summary' => $summary,
                'icon' => $cleanIcon,
            ];
        }

        $page = [
            'guides' => $formatted,
            'isLoggedIn' => auth()->loggedIn(),
        ];

        return view('guides/index', ['page' => $page]);
    }

    public function show(string $slug): string
    {
        if (!isset(self::GUIDES[$slug])) throw PageNotFoundException::forPageNotFound();
        $keys = array_keys(self::GUIDES);
        $i = array_search($slug, $keys, true);

        $prevSlug = $i > 0 ? $keys[$i - 1] : null;
        $nextSlug = $keys[$i + 1] ?? null;

        $contentHtml = view('guides/articles/' . $slug);

        $page = [
            'slug' => $slug,
            'title' => self::GUIDES[$slug][0],
            'summary' => self::GUIDES[$slug][1],
            'contentHtml' => $contentHtml,
            'prev' => $prevSlug ? [
                'slug' => $prevSlug,
                'title' => self::GUIDES[$prevSlug][0],
            ] : null,
            'next' => $nextSlug ? [
                'slug' => $nextSlug,
                'title' => self::GUIDES[$nextSlug][0],
            ] : null,
            'isLoggedIn' => auth()->loggedIn(),
        ];

        return view('guides/article', ['page' => $page]);
    }
}
