<?php
namespace App\Controllers;
use App\Models\MarketingModel;
class Marketing extends BaseController
{
    protected MarketingModel $model;

    public function __construct()
    {
        $this->model = new MarketingModel();
    }

    private function getCampaignTypes(): array
    {
        $db = db_connect();
        $rows = $db->table('campaign_types')->orderBy('sort_order')->get()->getResultArray();
        $types = [];
        foreach ($rows as $r) {
            $types[$r['type_key']] = [
                'name' => $r['name'], 'icon' => $r['icon'], 'cost' => (int) $r['daily_cost'],
                'visitors' => (int) $r['visitor_boost'], 'rep' => (int) $r['reputation_boost'],
                'days' => (int) $r['duration_days'], 'price' => (int) $r['total_price'], 'desc' => $r['description'],
            ];
        }
        return $types;
    }

    public function index(): string
    {
        $userId = auth()->id();
        $campaigns = $this->model->where('user_id', $userId)->where('status !=', 'expired')->findAll();
        $activeCampaigns = array_filter($campaigns, fn($c) => $c['status'] === 'active');

        return view('marketing/index', [
            'campaigns' => $campaigns, 'campaignTypes' => $this->getCampaignTypes(),
            'totalCost' => array_sum(array_column($activeCampaigns, 'daily_cost')),
            'totalVisitorBoost' => array_sum(array_column($activeCampaigns, 'visitor_boost')),
            'totalRepBoost' => array_sum(array_column($activeCampaigns, 'reputation_boost')),
        ]);
    }

    public function launch()
    {
        $userId = auth()->id();
        $type = $this->request->getPost('type');
        $types = $this->getCampaignTypes();
        if (!isset($types[$type])) return redirect()->back()->with('error', 'Invalid campaign type.');

        $t = $types[$type];
        $this->model->insert([
            'user_id' => $userId, 'campaign_type' => $type, 'name' => $t['name'],
            'daily_cost' => $t['cost'], 'visitor_boost' => $t['visitors'],
            'reputation_boost' => $t['rep'], 'days_remaining' => $t['days'], 'status' => 'active',
        ]);
        log_activity($userId, 'Marketing', 'Launched ' . $t['name'] . ' campaign', 'fa-solid fa-bullhorn');
        return redirect()->to('/marketing')->with('success', $t['name'] . ' campaign launched!');
    }

    public function cancel(int $id)
    {
        $userId = auth()->id();
        $campaign = $this->model->where('id', $id)->where('user_id', $userId)->first();
        if (!$campaign) return redirect()->back()->with('error', 'Campaign not found.');
        $this->model->update($id, ['status' => 'expired']);
        log_activity($userId, 'Marketing', 'Cancelled ' . $campaign['name'], 'fa-solid fa-xmark');
        return redirect()->to('/marketing')->with('success', $campaign['name'] . ' cancelled.');
    }

    public function toggle(int $id)
    {
        $userId = auth()->id();
        $campaign = $this->model->where('id', $id)->where('user_id', $userId)->first();
        if (!$campaign) return redirect()->back()->with('error', 'Campaign not found.');
        $new = $campaign['status'] === 'active' ? 'paused' : 'active';
        $this->model->update($id, ['status' => $new]);
        return redirect()->to('/marketing')->with('success', $campaign['name'] . ' ' . $new . '.');
    }
}
