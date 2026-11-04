<?php

namespace App\Controllers;

use App\Libraries\Fleet;
use App\Libraries\Snowmaking as SM;
use App\Libraries\Utilities;
use App\Libraries\Weather as W;

/**
 * /snowmaking: snow guns, which slopes they cover today, the next five days and what it all costs.
 * The rules are App\Libraries\Snowmaking, the same ones the nightly tick uses.
 */
class Snowmaking extends BaseController
{
    public function index(): string
    {
        $locked = checkFeatureUnlock('snowmaking'); if ($locked) return $locked;
        $userId = auth()->id();
        $db = db_connect();
        helper('weather');

        $weather = getCurrentWeather();
        $baseTemp = (int) $weather['base_temp'];
        $snowBase = (int) $weather['snow_base'];
        $prices = Fleet::prices();

        $crew = $db->table('staff')->where('user_id', $userId)->where('role', 'snowmaker')->where('status', 'active')->get()->getResultArray();
        $crewByGun = array_column(array_filter($crew, fn($s) => !empty($s['assigned_to'])), 'name', 'assigned_to');

        $guns = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'snowmaker')->orderBy('id')->get()->getResultArray();
        $slopes = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->orderBy('name')->get()->getResultArray();
        $open = array_values(array_filter($slopes, fn($s) => $s['status'] === 'open'));
        $coverage = SM::coverage($open, $guns, $baseTemp);
        $slopeNames = array_column($slopes, 'name', 'id');

        $costMult = ((int) getDifficultyConfig('cost_multiplier', '100')) / 100;
        foreach ($guns as &$g) {
            $g['max_temp'] = SM::maxTemp($g);
            $g['hours'] = SM::hours($g, $baseTemp);
            $g['runs'] = SM::runs($g, $baseTemp);
            $g['state'] = match (true) {
                (int) $g['condition_pct'] <= 0 || $g['status'] === 'broken' => 'broken',
                $g['status'] !== 'active' => 'off',
                !$g['runs'] => 'idle',
                default => 'running',
            };
            $g['covers'] = array_keys(array_filter($coverage, fn($gunId) => $gunId === (int) $g['id']));
            $g['priority'] = SM::prioritySlope($g);
            $g['crew'] = $crewByGun[$g['name']] ?? null;
            $g['operating_cost'] = (int) round((int) $g['fuel_cost'] * $costMult);
            $g['repair_cost'] = Fleet::repairCost($g, $prices);
            $g['resale'] = Fleet::resaleValue($g, $prices);
        }
        unset($g);
        $running = array_filter($guns, fn($g) => $g['runs']);

        // Energy and water: running guns against everything else the sources supply
        $energySupply = (int) $db->table('energy_management')->selectSum('output_kwh')->where('user_id', $userId)->where('status', 'active')->get()->getRow()->output_kwh;
        $waterSupply = (int) $db->table('water_management')->selectSum('output_liters')->where('user_id', $userId)->where('status', 'active')->get()->getRow()->output_liters;
        $energyDemand = Utilities::energyDemand(
            $running,
            $db->table('night_skiing')->where('user_id', $userId)->where('status', 'active')->countAllResults(),
            $db->table('player_items')->select('subtype, length_meters')->where('user_id', $userId)->where('item_type', 'lift')->where('status', 'open')->get()->getResultArray(),
            $db->table('buildings')->select('level')->where('user_id', $userId)->where('status', 'open')->get()->getResultArray(),
        );
        $waterDemand = Utilities::waterDemand($running);
        $hidden = array_map('trim', explode(',', (string) getDifficultyConfig('hidden_pages', '')));
        // Imports caused by snowmaking: the extra deficit its demand adds on top of everything else
        $energyImport = in_array('energy', $hidden, true) ? 0 : Utilities::importCost('energy', max(0, $energyDemand['total'] - $energySupply) - max(0, $energyDemand['total'] - $energyDemand['snowmaking'] - $energySupply));
        $waterImport = in_array('water', $hidden, true) ? 0 : Utilities::importCost('water', max(0, $waterDemand - $waterSupply));

        // Next five days: hours a standard gun can run, and whether any of your guns can
        $outlook = [];
        foreach (is_array($weather['forecast']) ? $weather['forecast'] : [] as $f) {
            $t = (int) $f['temp'];
            $outlook[] = [
                'day' => getSeasonDay() + (int) $f['day'], 'temp' => $t, 'condition' => $f['condition'], 'snowfall' => (int) $f['snowfall'],
                'hours' => SM::hours(['model_key' => ''], $t),
                'guns' => count(array_filter($guns, fn($g) => $g['status'] === 'active' && (int) $g['condition_pct'] > 0 && SM::hours($g, $t) > 0)),
            ];
        }

        return view('snowmaking/index', [
            'guns' => $guns,
            'running' => $running,
            'crew' => $crew,
            'crewByGun' => $crewByGun,
            'slopes' => $slopes,
            'openSlopes' => $open,
            'slopeNames' => $slopeNames,
            'coverage' => $coverage,
            'capacity' => array_sum(array_map(fn($g) => max(1, (int) $g['capacity']), $running)),
            'weather' => $weather,
            'baseTemp' => $baseTemp,
            'snowBase' => $snowBase,
            'thinBase' => $snowBase < W::THIN_BASE,
            'standardHours' => SM::hours(['model_key' => ''], $baseTemp),
            'outlook' => $outlook,
            'energy' => ['need' => $energyDemand['snowmaking'], 'total' => $energyDemand['total'], 'supply' => $energySupply, 'import' => $energyImport, 'hidden' => in_array('energy', $hidden, true)],
            'water' => ['need' => $waterDemand, 'supply' => $waterSupply, 'import' => $waterImport, 'hidden' => in_array('water', $hidden, true)],
            'operatingCost' => array_sum(array_column($running, 'operating_cost')),
        ]);
    }

    private function gun(int $id): ?array
    {
        return db_connect()->table('equipment')->where('id', $id)->where('user_id', auth()->id())->where('equipment_type', 'snowmaker')->get()->getRowArray();
    }

    public function toggle(int $id)
    {
        $userId = auth()->id();
        $eq = $this->gun($id);
        if (!$eq) return redirect()->back()->with('error', 'Snow gun not found.');
        if ((int) $eq['condition_pct'] <= 0) return redirect()->back()->with('error', $eq['name'] . ' is broken: repair it first.');

        $newStatus = $eq['status'] === 'active' ? 'off' : 'active';
        db_connect()->table('equipment')->where('id', $id)->update(['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')]);
        log_activity($userId, 'Snowmaking', $eq['name'] . ($newStatus === 'active' ? ' switched on' : ' switched off'), 'fa-solid fa-power-off');
        return redirect()->to('/snowmaking')->with('success', $eq['name'] . ($newStatus === 'active' ? ' switched on.' : ' switched off.'));
    }

    public function toggleAll()
    {
        $userId = auth()->id();
        $on = $this->request->getPost('action') === 'on';
        $db = db_connect();
        $q = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'snowmaker');
        if ($on) {
            $q->where('condition_pct >', 0)->whereIn('status', ['off', 'active'])->update(['status' => 'active', 'updated_at' => date('Y-m-d H:i:s')]);
        } else {
            $q->where('status', 'active')->update(['status' => 'off', 'updated_at' => date('Y-m-d H:i:s')]);
        }
        log_activity($userId, 'Snowmaking', $on ? 'All snow guns switched on' : 'All snow guns switched off', 'fa-solid fa-power-off');
        return redirect()->to('/snowmaking')->with('success', $on ? 'All working snow guns switched on.' : 'All snow guns switched off.');
    }

    public function repair(int $id)
    {
        $eq = $this->gun($id);
        if (!$eq) return redirect()->back()->with('error', 'Snow gun not found.');
        if ((int) $eq['condition_pct'] >= 100) return redirect()->back()->with('error', $eq['name'] . ' is already in perfect condition.');

        $cost = Fleet::repair(auth()->id(), $eq);
        if ($cost === null) return redirect()->back()->with('error', 'Not enough cash: repairing ' . $eq['name'] . ' costs ' . currency(Fleet::repairCost($eq)) . '.');
        return redirect()->to('/snowmaking')->with('success', $eq['name'] . ' repaired for ' . currency($cost) . '.');
    }

    public function assignCannon()
    {
        $userId = auth()->id();
        $db = db_connect();
        $eq = $this->gun((int) $this->request->getPost('cannon_id'));
        if (!$eq) return redirect()->back()->with('error', 'Snow gun not found.');

        $slopeId = (int) $this->request->getPost('slope_id');
        $slope = $slopeId ? $db->table('player_items')->where('id', $slopeId)->where('user_id', $userId)->where('item_type', 'slope')->get()->getRowArray() : null;
        if ($slopeId && !$slope) return redirect()->back()->with('error', 'Slope not found.');

        $db->table('equipment')->where('id', $eq['id'])->update(['assigned_to' => $slope ? 'slope_' . $slope['id'] : null, 'updated_at' => date('Y-m-d H:i:s')]);
        $msg = $slope ? $eq['name'] . ' now covers ' . $slope['name'] . ' first' : $eq['name'] . ' now covers the worst slopes first';
        log_activity($userId, 'Snowmaking', $msg, 'fa-solid fa-snowflake');
        return redirect()->to('/snowmaking')->with('success', $msg . '.');
    }

    public function sell(int $id)
    {
        $eq = $this->gun($id);
        if (!$eq) return redirect()->back()->with('error', 'Snow gun not found.');
        $refund = Fleet::sell(auth()->id(), $eq);
        return redirect()->to('/snowmaking')->with('success', $eq['name'] . ' sold for ' . currency($refund) . '.');
    }
}
