<?php

function checkFeatureUnlock(string $feature): ?string
{
    if (getDifficulty() === 'easy') return null;
    if (isFeatureUnlocked($feature)) return null;

    $unlock = getUnlockRequirement($feature);
    return view('locked', ['unlock' => $unlock]);
}
