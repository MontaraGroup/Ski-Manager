<?php

namespace App\Controllers;

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
        $userId = auth()->id();
        $db = db_connect();

        $staffModel = new StaffModel();
        $instructors = $staffModel->where('user_id', $userId)->where('role', 'instructor')->where('status !=', 'fired')->findAll();

        $itemModel = new PlayerItemModel();
        $slopes = $itemModel->where('user_id', $userId)->whereIn('item_type', ['slope', 'downhill', 'crosscountry', 'snowpark'])->where('status', 'open')->findAll();

        $activeInstructors = count(array_filter($instructors, fn($i) => ($i['status'] ?? '') === 'active'));

        // Query most recent ski school activity log
        $recentLessonLog = $db->table('activity_log')
            ->where('user_id', $userId)
            ->where('category', 'ski_school')
            ->orderBy('created_at', 'DESC')
            ->get()->getRowArray();

        $dailyCapacity = $activeInstructors * 10;
        $lessonsToday = $activeInstructors > 0 ? (int) ceil($activeInstructors * 2.2) : 0;
        $estimatedRev = $activeInstructors > 0 ? (int) round($activeInstructors * 8 * 45) : 0;

        return view('lessons/index', [
            'instructors' => $instructors,
            'activeInstructors' => $activeInstructors,
            'slopes' => $slopes,
            'dailyCapacity' => $dailyCapacity,
            'lessonsToday' => $lessonsToday,
            'estimatedRevenue' => $estimatedRev,
            'lessonTypes' => self::LESSON_TYPES,
            'recentLessonLog' => $recentLessonLog,
        ]);
    }
}
