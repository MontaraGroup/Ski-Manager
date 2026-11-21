<?php

namespace App\Controllers;

use App\Libraries\StaffEffects;
use App\Models\StaffModel;
use App\Models\PlayerItemModel;

class SkiLessons extends BaseController
{
    public const LESSON_TYPES = [
        'beginner' => ['name' => 'Beginner Group', 'duration' => '2 hours', 'price' => 40, 'instructors' => 1, 'max_students' => 8, 'icon' => 'fa-solid fa-person-skiing text-success'],
        'intermediate' => ['name' => 'Intermediate Group', 'duration' => '2 hours', 'price' => 50, 'instructors' => 1, 'max_students' => 6, 'icon' => 'fa-solid fa-person-skiing text-info'],
        'advanced' => ['name' => 'Advanced Group', 'duration' => '3 hours', 'price' => 70, 'instructors' => 1, 'max_students' => 4, 'icon' => 'fa-solid fa-person-skiing text-warning'],
        'private' => ['name' => 'Private Lesson', 'duration' => '1 hour', 'price' => 120, 'instructors' => 1, 'max_students' => 1, 'icon' => 'fa-solid fa-user text-primary'],
        'kids_camp' => ['name' => 'Kids Camp', 'duration' => '4 hours', 'price' => 60, 'instructors' => 2, 'max_students' => 10, 'icon' => 'fa-solid fa-child text-error'],
    ];

    public function index(): string
    {
        return view('lessons/index', ['page' => $this->pageData()]);
    }

    /** What the page shows: the web view and the app API (Api\V1\App) both use it */
    protected function pageData(): array
    {
        $userId = auth()->id();
        $db = db_connect();

        $staffModel = new StaffModel();
        $instructors = $staffModel->where('user_id', $userId)->where('role', 'instructor')->where('status !=', 'fired')->findAll();

        $itemModel = new PlayerItemModel();
        $slopes = $itemModel->where('user_id', $userId)->whereIn('item_type', ['slope', 'downhill', 'crosscountry', 'snowpark'])->where('status', 'open')->findAll();

        $assignedInstructors = count(array_filter($instructors, fn($i) => ($i['status'] ?? '') === 'active' && !empty($i['assigned_to'])));

        // Query most recent ski school activity log
        $recentLessonLog = $db->table('activity_log')
            ->where('user_id', $userId)
            ->where('category', 'ski_school')
            ->orderBy('created_at', 'DESC')
            ->get()->getRowArray();

        helper('difficulty');
        $finance = $db->table('player_finances')->select('daily_visitors, resort_open')->where('user_id', $userId)->get()->getRowArray();
        $dailyVisitors = ((int) ($finance['resort_open'] ?? 1) === 1) ? (int) ($finance['daily_visitors'] ?? 0) : 0;
        $revMult = ((int) getDifficultyConfig('revenue_multiplier', '100')) / 100;

        $dailyCapacity = $assignedInstructors * StaffEffects::LESSONS_PER_INSTRUCTOR;
        $studentsToday = ($assignedInstructors > 0 && count($slopes) > 0 && $dailyVisitors > 0)
            ? min($dailyCapacity, (int) round($dailyVisitors * StaffEffects::LESSON_DEMAND_SHARE))
            : 0;
        $lessonsToday = $studentsToday > 0 ? (int) ceil($studentsToday / 5) : 0;
        $estimatedRev = $studentsToday > 0 ? (int) round($studentsToday * StaffEffects::LESSON_FEE * $revMult) : 0;

        $page = [
            'stats' => [
                'activeInstructors' => (int) $assignedInstructors,
                'totalInstructors' => count($instructors),
                'dailyCapacity' => (int) $dailyCapacity,
                'lessonsToday' => (int) $lessonsToday,
                'openSlopesCount' => count($slopes),
                'estimatedRevenueText' => currency((int) $estimatedRev),
            ],
            'recentLessonLog' => $recentLessonLog ? (string) ($recentLessonLog['message'] ?? '') : null,
            'lessonPrograms' => array_values(array_map(function ($key, $type) {
                // Extract base icon name
                $icon = 'fa-person-skiing';
                if (preg_match('/fa-([a-z0-9-]+)/', $type['icon'], $m)) {
                    $icon = 'fa-' . $m[1];
                }
                return [
                    'key' => (string) $key,
                    'name' => (string) $type['name'],
                    'duration' => (string) $type['duration'],
                    'price' => (int) $type['price'],
                    'priceText' => currency((int) $type['price']),
                    'instructors' => (int) $type['instructors'],
                    'maxStudents' => (int) $type['max_students'],
                    'icon' => $icon,
                ];
            }, array_keys(self::LESSON_TYPES), self::LESSON_TYPES)),
            'instructors' => array_map(function ($inst) {
                return [
                    'id' => (int) $inst['id'],
                    'name' => (string) $inst['name'],
                    'status' => (string) ($inst['status'] ?? 'active'),
                    'level' => (int) ($inst['level'] ?? 1),
                    'salaryText' => currency((int) ($inst['salary'] ?? 0)),
                    'morale' => (int) ($inst['morale'] ?? 100),
                    'assignedTo' => !empty($inst['assigned_to']) ? (string) $inst['assigned_to'] : null,
                ];
            }, $instructors),
        ];

        return $page;
    }
}
