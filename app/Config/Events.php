<?php

namespace Config;

use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\HotReloader\HotReloader;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

Events::on('pre_system', static function (): void {
    if (ENVIRONMENT !== 'testing') {
        $value = ini_get('zlib.output_compression');

        if (filter_var($value, FILTER_VALIDATE_BOOLEAN) || (int) $value > 0) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start(static fn ($buffer) => $buffer);
    }

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        service('toolbar')->respond();
        // Hot Reload route - for framework use on the hot reloader.
        if (ENVIRONMENT === 'development') {
            service('routes')->get('__hot-reload', static function (): void {
                (new HotReloader())->run();
            });
        }
    }
});

Events::on("register", static function ($user) {
    $db = db_connect();
    $request = service("request");
    $difficulty = $request->getPost("difficulty") ?? session("difficulty") ?? "standard";
    $resortMap = $request->getPost("resort_map") ?? session("resort_map") ?? "ParkCity";
    $cash = match($difficulty) { "easy" => 1000000, "hard" => 200000, default => 500000 };
    $existing = $db->table("player_finances")->where("user_id", $user->id)->countAllResults();
    if (!$existing) {
        $db->table("player_finances")->insert(["user_id" => $user->id, "cash" => $cash, "total_income" => 0, "total_expenses" => 0, "difficulty" => $difficulty, "resort_map" => $resortMap]);
        $db->table("genepis")->insert(["user_id" => $user->id, "balance" => 0]);
        $db->table("daily_bonus")->insert(["user_id" => $user->id, "last_claim_day" => 0, "streak" => 0]);
        log_activity($user->id, "register", "Joined Ski Manager");
        notify($user->id, "welcome", "Welcome to Ski Manager!", "Start by hiring staff and building your first slope.", "fa-solid fa-mountain-sun", "/dashboard");
    }
});

// Save difficulty choice from registration form to session
Events::on('post_controller_constructor', static function () {
    $request = service('request');
    if ($request->is('post') && str_contains($request->getPath(), 'register')) {
        $difficulty = $request->getPost('difficulty');
        $resortMap = $request->getPost('resort_map');
        if (in_array($difficulty, ['easy', 'standard', 'hard'])) {
            session()->set('difficulty', $difficulty);
            if ($resortMap && in_array($resortMap, ['AspenSnowmass','BigSkyCombo','DeerValley','Killington','PalisadesTahoe','ParkCity','Vail'])) {
                session()->set('resort_map', $resortMap);
            }
        }
    }
});

