<?php

namespace App\Controllers;

class Equipment extends BaseController
{
    private array $groomers = [
        'pb100' => ['brand' => 'PistenBully', 'name' => 'PistenBully 100', 'desc' => 'Compact groomer for narrow trails and park features', 'capacity' => 2, 'fuel' => 300, 'cost' => 120000, 'img' => 'fa-solid fa-truck-monster'],
        'pb400' => ['brand' => 'PistenBully', 'name' => 'PistenBully 400', 'desc' => 'Versatile mid-range groomer, the industry workhorse', 'capacity' => 3, 'fuel' => 450, 'cost' => 250000, 'img' => 'fa-solid fa-truck-monster'],
        'pb600' => ['brand' => 'PistenBully', 'name' => 'PistenBully 600', 'desc' => 'Powerful wide-track groomer for large slopes', 'capacity' => 5, 'fuel' => 600, 'cost' => 380000, 'img' => 'fa-solid fa-truck-monster'],
        'pb800' => ['brand' => 'PistenBully', 'name' => 'PistenBully 800', 'desc' => 'Top-of-the-line, GPS-guided precision grooming', 'capacity' => 7, 'fuel' => 750, 'cost' => 520000, 'img' => 'fa-solid fa-truck-monster'],
        'prinoth_leitwolf' => ['brand' => 'Prinoth', 'name' => 'Prinoth Leitwolf', 'desc' => 'Premium groomer with unmatched traction and power', 'capacity' => 6, 'fuel' => 700, 'cost' => 480000, 'img' => 'fa-solid fa-truck-monster'],
        'prinoth_husky' => ['brand' => 'Prinoth', 'name' => 'Prinoth Husky', 'desc' => 'Reliable all-terrain groomer for varied conditions', 'capacity' => 4, 'fuel' => 500, 'cost' => 300000, 'img' => 'fa-solid fa-truck-monster'],
        'prinoth_bison' => ['brand' => 'Prinoth', 'name' => 'Prinoth Bison', 'desc' => 'Heavy-duty groomer for extreme terrain and deep snow', 'capacity' => 6, 'fuel' => 650, 'cost' => 420000, 'img' => 'fa-solid fa-truck-monster'],
    ];

    private array $snowmakers = [
        'ta_tt10' => ['brand' => 'TechnoAlpin', 'name' => 'TechnoAlpin TT10', 'desc' => 'Compact tower gun, low noise, ideal for residential areas', 'capacity' => 4, 'fuel' => 400, 'cost' => 35000, 'img' => 'fa-solid fa-snowflake'],
        'ta_t40' => ['brand' => 'TechnoAlpin', 'name' => 'TechnoAlpin T40', 'desc' => 'High-performance tower gun with excellent range', 'capacity' => 8, 'fuel' => 700, 'cost' => 65000, 'img' => 'fa-solid fa-snowflake'],
        'ta_m18' => ['brand' => 'TechnoAlpin', 'name' => 'TechnoAlpin M18', 'desc' => 'Mobile fan gun, flexible placement, quick setup', 'capacity' => 6, 'fuel' => 550, 'cost' => 45000, 'img' => 'fa-solid fa-snowflake'],
        'ta_m90' => ['brand' => 'TechnoAlpin', 'name' => 'TechnoAlpin M90', 'desc' => 'Largest mobile fan gun, massive snow output', 'capacity' => 15, 'fuel' => 1200, 'cost' => 120000, 'img' => 'fa-solid fa-snowflake'],
        'ta_v3' => ['brand' => 'TechnoAlpin', 'name' => 'TechnoAlpin V3', 'desc' => 'Compact ventilator gun, energy efficient', 'capacity' => 5, 'fuel' => 350, 'cost' => 30000, 'img' => 'fa-solid fa-snowflake'],
        'sufag_compact' => ['brand' => 'Sufag', 'name' => 'Sufag Compact', 'desc' => 'Budget-friendly fan gun for smaller resorts', 'capacity' => 3, 'fuel' => 300, 'cost' => 20000, 'img' => 'fa-solid fa-snowflake'],
        'sufag_power' => ['brand' => 'Sufag', 'name' => 'Sufag PowerSnow', 'desc' => 'Mid-range fan gun with good snow quality', 'capacity' => 7, 'fuel' => 600, 'cost' => 55000, 'img' => 'fa-solid fa-snowflake'],
        'demaclenko_titan' => ['brand' => 'Demaclenko', 'name' => 'Demaclenko Titan 4.0', 'desc' => 'All-weather system, produces snow at higher temps', 'capacity' => 12, 'fuel' => 900, 'cost' => 95000, 'img' => 'fa-solid fa-snowflake'],
        'smi_polecat' => ['brand' => 'SMI', 'name' => 'SMI PoleCat', 'desc' => 'American-made tower gun, rugged and reliable', 'capacity' => 6, 'fuel' => 500, 'cost' => 40000, 'img' => 'fa-solid fa-snowflake'],
        'smi_viking' => ['brand' => 'SMI', 'name' => 'SMI Super Viking', 'desc' => 'High-output fan gun, proven in harsh conditions', 'capacity' => 10, 'fuel' => 800, 'cost' => 80000, 'img' => 'fa-solid fa-snowflake'],
    ];

    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $equipment = $db->table('equipment')->where('user_id', $userId)->get()->getResultArray();

        $ownedGroomers = array_filter($equipment, fn($e) => $e['equipment_type'] === 'groomer');
        $ownedSnowmakers = array_filter($equipment, fn($e) => $e['equipment_type'] === 'snowmaker');

        $totalFuel = array_sum(array_column(array_filter($equipment, fn($e) => $e['status'] === 'active'), 'fuel_cost'));

        return view('equipment/index', [
            'groomers' => $this->groomers,
            'snowmakers' => $this->snowmakers,
            'ownedGroomers' => $ownedGroomers,
            'ownedSnowmakers' => $ownedSnowmakers,
            'totalFuel' => $totalFuel,
            'equipment' => $equipment,
        ]);
    }

    public function buy()
    {
        $userId = auth()->id();
        $modelKey = $this->request->getPost('model');
        $type = $this->request->getPost('type');

        $catalog = $type === 'groomer' ? $this->groomers : $this->snowmakers;

        if (!isset($catalog[$modelKey])) {
            return redirect()->back()->with('error', 'Invalid equipment.');
        }

        $item = $catalog[$modelKey];
        $db = db_connect();
        $count = $db->table('equipment')->where('user_id', $userId)->where('model_key', $modelKey)->countAllResults();

        $db->table('equipment')->insert([
            'user_id' => $userId,
            'equipment_type' => $type,
            'model_key' => $modelKey,
            'name' => $item['name'] . ' #' . ($count + 1),
            'brand' => $item['brand'],
            'capacity' => $item['capacity'],
            'fuel_cost' => $item['fuel'],
            'condition_pct' => 100,
            'status' => 'off',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        log_activity($userId, 'Equipment', 'Purchased ' . $item['name'] . ' for ' . currency($item['cost']), $item['img']);
        return redirect()->to('/equipment')->with('success', $item['name'] . ' purchased for ' . currency($item['cost']) . '!');
    }

    public function toggle(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $eq = $db->table('equipment')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$eq) return redirect()->back()->with('error', 'Not found.');

        if ((int) $eq['condition_pct'] <= 0) return redirect()->back()->with('error', 'Broken — repair first.');

        $new = $eq['status'] === 'active' ? 'off' : 'active';
        $db->table('equipment')->where('id', $id)->update(['status' => $new, 'updated_at' => date('Y-m-d H:i:s')]);
        return redirect()->to('/equipment')->with('success', $eq['name'] . ' turned ' . $new . '.');
    }

    public function repair(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $eq = $db->table('equipment')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$eq) return redirect()->back()->with('error', 'Not found.');

        $db->table('equipment')->where('id', $id)->update(['condition_pct' => 100, 'status' => 'off', 'updated_at' => date('Y-m-d H:i:s')]);
        log_activity($userId, 'Equipment', 'Repaired ' . $eq['name'], 'fa-solid fa-wrench');
        return redirect()->to('/equipment')->with('success', $eq['name'] . ' repaired.');
    }

    public function sell(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $eq = $db->table('equipment')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$eq) return redirect()->back()->with('error', 'Not found.');

        $db->table('equipment')->where('id', $id)->delete();
        log_activity($userId, 'Equipment', 'Sold ' . $eq['name'], 'fa-solid fa-money-bill-wave');
        return redirect()->to('/equipment')->with('success', $eq['name'] . ' sold.');
    }
}
