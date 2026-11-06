<?php

namespace App\Libraries;

/**
 * Centralized game-balance constants for staff roles and their nightly tick effects.
 */
class StaffEffects
{
    // Skier boosts (percentage increase in daily visitors per assigned staff member)
    public const SKI_PATROL_SKIER_BOOST = 2;
    public const INSTRUCTOR_SKIER_BOOST = 3;
    public const MANAGER_SKIER_BOOST = 5;

    // Accident liability damages
    public const ACCIDENT_COST_MIN = 2500;
    public const ACCIDENT_COST_MAX = 8000;
    public const ACCIDENT_COST_MIN_FORMATTED = '2,500';
    public const ACCIDENT_COST_MAX_FORMATTED = '8,000';

    // Ski lessons / ski school
    public const LESSONS_PER_INSTRUCTOR = 10;
    public const LESSON_DEMAND_SHARE = 0.08;
    public const LESSON_DEMAND_PCT = 8;
    public const LESSON_FEE = 50;

    // Building revenue multipliers
    public const RECEPTIONIST_REVENUE_BOOST = 0.15;
    public const RECEPTIONIST_REVENUE_BOOST_PCT = 15;
    public const CHEF_REVENUE_BOOST = 0.10;
    public const CHEF_REVENUE_BOOST_PCT = 10;

    // Maintenance & wear relief
    public const MECHANIC_LIFT_WEAR_RELIEF = 1;
    public const PARK_CREW_WEAR_RELIEF = 0.4;
    public const PARK_CREW_CAP = 1.5;
}
