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

    public function index(): string
    {
        $userId = auth()->id();
        $campaigns = $this->model->where('user_id', $userId)->where('status !=', 'expired')->findAll();

        $activeCampaigns = array_filter($campaigns, fn($c) => $c['status'] === 'active');
        $totalCost = array_sum(array_column($activeCampaigns, 'daily_cost'));
        $totalVisitorBoost = array_sum(array_column($activeCampaigns, 'visitor_boost'));
        $totalRepBoost = array_sum(array_column($activeCampaigns, 'reputation_boost'));

        $campaignTypes = [
            'local_flyers' => ['name' => 'Local Flyers', 'icon' => 'fa-solid fa-newspaper', 'cost' => 500, 'visitors' => 3, 'rep' => 1, 'days' => 7, 'price' => 3500, 'desc' => 'Distribute flyers in nearby towns'],
            'radio_ad' => ['name' => 'Radio Advertisement', 'icon' => 'fa-solid fa-radio', 'cost' => 1500, 'visitors' => 8, 'rep' => 3, 'days' => 14, 'price' => 21000, 'desc' => 'Regional radio spots'],
            'social_media' => ['name' => 'Social Media Campaign', 'icon' => 'fa-solid fa-hashtag', 'cost' => 2000, 'visitors' => 12, 'rep' => 5, 'days' => 30, 'price' => 60000, 'desc' => 'Instagram, TikTok, YouTube ads'],
            'tv_commercial' => ['name' => 'TV Commercial', 'icon' => 'fa-solid fa-tv', 'cost' => 5000, 'visitors' => 20, 'rep' => 10, 'days' => 14, 'price' => 70000, 'desc' => 'National television advertising'],
            'influencer' => ['name' => 'Influencer Partnership', 'icon' => 'fa-solid fa-star', 'cost' => 3500, 'visitors' => 15, 'rep' => 8, 'days' => 21, 'price' => 73500, 'desc' => 'Partner with travel influencers'],
            'airline_deal' => ['name' => 'Airline Partnership', 'icon' => 'fa-solid fa-plane', 'cost' => 8000, 'visitors' => 25, 'rep' => 12, 'days' => 30, 'price' => 240000, 'desc' => 'Package deals with airlines'],
            'magazine_feature' => ['name' => 'Magazine Feature', 'icon' => 'fa-solid fa-book-open', 'cost' => 4000, 'visitors' => 10, 'rep' => 15, 'days' => 30, 'price' => 120000, 'desc' => 'Featured in ski magazines'],
            'billboard' => ['name' => 'Highway Billboards', 'icon' => 'fa-solid fa-sign-hanging', 'cost' => 3000, 'visitors' => 10, 'rep' => 5, 'days' => 60, 'price' => 180000, 'desc' => 'Billboard ads on major highways'],
        ];

        return view('marketing/index', [
            'campaigns' => $campaigns,
            'campaignTypes' => $campaignTypes,
            'totalCost' => $totalCost,
            'totalVisitorBoost' => $totalVisitorBoost,
            'totalRepBoost' => $totalRepBoost,
        ]);
    }

    public function launch()
    {
        $userId = auth()->id();
        $type = $this->request->getPost('type');

        $types = [
            'local_flyers' => ['name' => 'Local Flyers', 'cost' => 500, 'visitors' => 3, 'rep' => 1, 'days' => 7],
            'radio_ad' => ['name' => 'Radio Advertisement', 'cost' => 1500, 'visitors' => 8, 'rep' => 3, 'days' => 14],
            'social_media' => ['name' => 'Social Media Campaign', 'cost' => 2000, 'visitors' => 12, 'rep' => 5, 'days' => 30],
            'tv_commercial' => ['name' => 'TV Commercial', 'cost' => 5000, 'visitors' => 20, 'rep' => 10, 'days' => 14],
            'influencer' => ['name' => 'Influencer Partnership', 'cost' => 3500, 'visitors' => 15, 'rep' => 8, 'days' => 21],
            'airline_deal' => ['name' => 'Airline Partnership', 'cost' => 8000, 'visitors' => 25, 'rep' => 12, 'days' => 30],
            'magazine_feature' => ['name' => 'Magazine Feature', 'cost' => 4000, 'visitors' => 10, 'rep' => 15, 'days' => 30],
            'billboard' => ['name' => 'Highway Billboards', 'cost' => 3000, 'visitors' => 10, 'rep' => 5, 'days' => 60],
        ];

        if (!isset($types[$type])) {
            return redirect()->back()->with('error', 'Invalid campaign type.');
        }

        $t = $types[$type];
        $this->model->insert([
            'user_id' => $userId,
            'campaign_type' => $type,
            'name' => $t['name'],
            'daily_cost' => $t['cost'],
            'visitor_boost' => $t['visitors'],
            'reputation_boost' => $t['rep'],
            'days_remaining' => $t['days'],
            'status' => 'active',
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
