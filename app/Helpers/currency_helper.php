<?php

function isImperial(): bool
{
    $units = session()->get('units');
    if (!$units && function_exists('auth') && auth()->loggedIn()) {
        $fin = db_connect()->table('player_finances')->where('user_id', auth()->id())->get()->getRowArray();
        $units = $fin['units'] ?? 'metric';
        session()->set('units', $units);
        session()->set('currency', $units === 'imperial' ? 'USD' : 'EUR');
    }
    return ($units ?? 'metric') === 'imperial';
}

function currencySymbol(): string { return isImperial() ? '$' : '€'; }

function currency($amount): string
{
    $formatted = number_format(abs((int) $amount));
    $sign = (int) $amount < 0 ? '-' : '';
    return isImperial()
        ? $sign . '$' . $formatted
        : $sign . $formatted . ' €';
}

function currencyConvert($amount): int
{
    return isImperial() ? (int) round($amount * 1.08) : (int) $amount;
}

function temp(int $celsius): string
{
    if (isImperial()) {
        return round($celsius * 9 / 5 + 32) . '°F';
    }
    return $celsius . '°C';
}

function speed(int $kmh): string
{
    if (isImperial()) {
        return round($kmh * 0.621371) . ' mph';
    }
    return $kmh . ' km/h';
}

function distance(int $meters): string
{
    if (isImperial()) {
        return $meters >= 1609 ? round($meters / 1609.34, 1) . ' mi' : round($meters * 3.28084) . ' ft';
    }
    return $meters >= 1000 ? round($meters / 1000, 1) . ' km' : $meters . ' m';
}

function snow(int $cm): string
{
    if (isImperial()) {
        return round($cm / 2.54) . ' in';
    }
    return $cm . ' cm';
}

function altitude(mixed $meters): string
{
    $meters = (int) $meters;
    if (isImperial()) {
        return number_format(round($meters * 3.28084)) . ' ft';
    }
    return number_format($meters) . ' m';
}

function speedUnit(): string { return isImperial() ? 'mph' : 'km/h'; }
function distanceUnit(): string { return isImperial() ? 'ft' : 'm'; }
