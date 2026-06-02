<?php

function isImperial(): bool
{
    return (session()->get('units') ?? 'metric') === 'imperial';
}

function currency(int $amountEur): string
{
    if (isImperial()) {
        return '$' . number_format(round($amountEur * 1.08));
    }
    return number_format($amountEur) . ' €';
}

function currencySymbol(): string
{
    return isImperial() ? '$' : '€';
}

function currencyConvert(int $amountEur): int
{
    return isImperial() ? round($amountEur * 1.08) : $amountEur;
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
        $feet = round($meters * 3.28084);
        if ($feet >= 5280) {
            return round($feet / 5280, 1) . ' mi';
        }
        return number_format($feet) . ' ft';
    }
    if ($meters >= 1000) {
        return round($meters / 1000, 1) . ' km';
    }
    return number_format($meters) . ' m';
}

function snow(int $cm): string
{
    if (isImperial()) {
        return round($cm * 0.393701) . ' in';
    }
    return $cm . ' cm';
}

function altitude(string $level): string
{
    $labels = [
        'low' => isImperial() ? 'Low (Below 3,280 ft)' : 'Low (Below 1,000 m)',
        'medium' => isImperial() ? 'Medium (3,280–6,560 ft)' : 'Medium (1,000–2,000 m)',
        'high' => isImperial() ? 'High (Above 6,560 ft)' : 'High (Above 2,000 m)',
    ];
    return $labels[$level] ?? 'Unknown';
}

function speedUnit(): string
{
    return isImperial() ? 'mph' : 'km/h';
}

function distanceUnit(): string
{
    return isImperial() ? 'ft' : 'm';
}
