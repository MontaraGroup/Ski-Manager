<?php

function checkFeatureUnlock(string $feature): ?string
{
    if (auth()->id() === 1) return null;
    if (getDifficulty() === 'easy') return null;
    if (isFeatureUnlocked($feature)) return null;

    $unlock = getUnlockRequirement($feature);
    return view('locked', ['unlock' => $unlock]);
}
