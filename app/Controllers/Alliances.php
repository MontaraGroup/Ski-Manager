<?php

namespace App\Controllers;

use App\Libraries\Ledger;
use App\Models\AllianceModel;
use App\Models\AllianceMemberModel;
use CodeIgniter\HTTP\ResponseInterface;

class Alliances extends BaseController
{
    protected AllianceModel $allianceModel;
    protected AllianceMemberModel $memberModel;

    public function __construct()
    {
        AllianceModel::ensureSchema();
        $this->allianceModel = new AllianceModel();
        $this->memberModel   = new AllianceMemberModel();
    }

    public function index()
    {
        $userId = auth()->id();
        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Please log in to access Resort Alliances.');
        }

        $db = db_connect();
        $membership = $this->memberModel->where('user_id', $userId)->first();

        if (!$membership) {
            return $this->directory();
        }

        $alliance = $this->allianceModel->find($membership['alliance_id']);
        if (!$alliance) {
            // Broken membership link cleanup
            $this->memberModel->where('user_id', $userId)->delete();
            return $this->directory();
        }

        // Fetch all members with their live resort statistics
        $members = $db->query("
            SELECT 
                am.id as membership_id,
                am.user_id,
                am.role,
                am.donated_cash,
                am.cross_skiers_generated,
                am.joined_at,
                u.username,
                pf.cash,
                pf.reputation,
                pf.resort_map,
                (SELECT COUNT(*) FROM player_items pi WHERE pi.user_id = u.id AND pi.item_type = 'slope' AND pi.status = 'open') as open_slopes,
                (SELECT COUNT(*) FROM player_items pi WHERE pi.user_id = u.id AND pi.item_type = 'lift' AND pi.status = 'open') as open_lifts,
                (SELECT COALESCE(SUM(length_meters), 0) FROM player_items pi WHERE pi.user_id = u.id AND pi.item_type = 'slope') as total_slope_meters
            FROM alliance_members am
            JOIN users u ON u.id = am.user_id
            LEFT JOIN player_finances pf ON pf.user_id = u.id
            WHERE am.alliance_id = ?
            ORDER BY 
                CASE am.role 
                    WHEN 'founder' THEN 1 
                    WHEN 'officer' THEN 2 
                    ELSE 3 
                END ASC,
                am.donated_cash DESC
        ", [$alliance['id']])->getResultArray();

        // Calculate aggregate statistics
        $totalSlopes = array_sum(array_column($members, 'open_slopes'));
        $totalLifts  = array_sum(array_column($members, 'open_lifts'));
        $totalSlopeKm = round(array_sum(array_column($members, 'total_slope_meters')) / 1000, 1);
        $combinedTreasury = (int) $alliance['treasury_cash'] + array_sum(array_column($members, 'cash'));

        // Load active perks
        $unlockedPerksRaw = $db->table('alliance_unlocked_perks')
            ->where('alliance_id', $alliance['id'])
            ->get()->getResultArray();
        $unlockedPerks = [];
        foreach ($unlockedPerksRaw as $p) {
            $unlockedPerks[$p['perk_key']] = (int) $p['perk_level'];
        }

        // Load pending applications (if founder or officer)
        $isLeader = in_array($membership['role'], ['founder', 'officer']);
        $applications = [];
        if ($isLeader) {
            $applications = $db->query("
                SELECT aa.*, u.username, pf.cash, pf.reputation
                FROM alliance_applications aa
                JOIN users u ON u.id = aa.user_id
                LEFT JOIN player_finances pf ON pf.user_id = u.id
                WHERE aa.alliance_id = ? AND aa.status = 'pending'
                ORDER BY aa.created_at ASC
            ", [$alliance['id']])->getResultArray();
        }

        // Load activity feed
        $activities = $db->table('alliance_activity_log')
            ->where('alliance_id', $alliance['id'])
            ->orderBy('created_at', 'DESC')
            ->limit(15)
            ->get()->getResultArray();

        $currentUserFinance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();

        $curTierNum = (int) $alliance['pass_tier'];
        $passTiers = AllianceModel::PASS_TIERS;
        $curTier = $passTiers[$curTierNum] ?? $passTiers[1];
        $nextTierNum = $curTierNum + 1;
        $nextTier = $passTiers[$nextTierNum] ?? null;
        $sisterBonus = max(0, min(10, count($members) - 1));
        $totalPassVisitorBoost = (int) $curTier['visitor_pct'] + $sisterBonus;

        $rawCrest = $alliance['crest_icon'] ?? 'fa-shield';
        $crestIcon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', (string) $rawCrest);

        $nextTierData = null;
        if ($nextTier) {
            $nextTierData = [
                'tier' => $nextTierNum,
                'name' => (string) $nextTier['name'],
                'req_level' => (int) $nextTier['req_level'],
                'cost' => (int) $nextTier['cost'],
                'costText' => currency((int) $nextTier['cost']),
                'visitor_pct' => (int) $nextTier['visitor_pct'],
                'canAfford' => (int) $alliance['treasury_cash'] >= (int) $nextTier['cost'],
                'meetsLevel' => (int) $alliance['level'] >= (int) $nextTier['req_level'],
            ];
        }

        $perkDefs = AllianceModel::PERKS;
        $formattedPerks = [];
        foreach ($perkDefs as $pKey => $pDef) {
            $curPLevel = $unlockedPerks[$pKey] ?? 0;
            $nextPLevel = $curPLevel + 1;
            $hasMaxLevel = !isset($pDef['levels'][$nextPLevel]);
            $nextPConfig = $pDef['levels'][$nextPLevel] ?? null;
            $rawPIcon = $pDef['icon'] ?? 'fa-bolt';
            $pIcon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', (string) $rawPIcon);

            $formattedPerks[] = [
                'key' => (string) $pKey,
                'name' => (string) $pDef['name'],
                'desc' => (string) $pDef['desc'],
                'icon' => $pIcon,
                'curLevel' => $curPLevel,
                'curEffect' => $curPLevel > 0 ? (string) ($pDef['levels'][$curPLevel]['effect'] ?? '') : null,
                'isMax' => $hasMaxLevel,
                'nextLevel' => $nextPLevel,
                'nextCost' => $nextPConfig ? (int) $nextPConfig['cost'] : 0,
                'nextCostText' => $nextPConfig ? currency((int) $nextPConfig['cost']) : '',
                'nextEffect' => $nextPConfig ? (string) $nextPConfig['effect'] : '',
                'nextReqLevel' => $nextPConfig ? (int) $nextPConfig['req_level'] : 0,
                'canAfford' => $nextPConfig ? (int) $alliance['treasury_cash'] >= (int) $nextPConfig['cost'] : false,
                'meetsLevel' => $nextPConfig ? (int) $alliance['level'] >= (int) $nextPConfig['req_level'] : false,
            ];
        }

        $formattedMembers = array_map(function ($m) use ($userId) {
            return [
                'membership_id' => (int) $m['membership_id'],
                'user_id' => (int) $m['user_id'],
                'username' => (string) $m['username'],
                'role' => (string) $m['role'],
                'resort_map' => (string) ($m['resort_map'] ?? 'ParkCity'),
                'reputation' => (int) ($m['reputation'] ?? 0),
                'open_slopes' => (int) ($m['open_slopes'] ?? 0),
                'open_lifts' => (int) ($m['open_lifts'] ?? 0),
                'cash' => (int) ($m['cash'] ?? 0),
                'cashText' => currency((int) ($m['cash'] ?? 0)),
                'donated_cash' => (int) ($m['donated_cash'] ?? 0),
                'donatedText' => currency((int) ($m['donated_cash'] ?? 0)),
                'isCurrentUser' => (int) $m['user_id'] === $userId,
                'canManage' => (int) $m['user_id'] !== $userId && $m['role'] !== 'founder',
            ];
        }, $members);

        $formattedApps = array_map(function ($a) {
            return [
                'id' => (int) $a['id'],
                'user_id' => (int) $a['user_id'],
                'username' => (string) $a['username'],
                'reputation' => (int) ($a['reputation'] ?? 0),
                'message' => (string) ($a['message'] ?? ''),
                'cashText' => currency((int) ($a['cash'] ?? 0)),
            ];
        }, $applications);

        $formattedActs = array_map(function ($act) {
            return [
                'id' => (int) ($act['id'] ?? 0),
                'message' => (string) $act['message'],
                'timeAgoText' => timeAgo($act['created_at']),
            ];
        }, $activities);

        $page = [
            'alliance' => [
                'id' => (int) $alliance['id'],
                'name' => (string) $alliance['name'],
                'tag' => (string) $alliance['tag'],
                'motto' => (string) ($alliance['motto'] ?? ''),
                'level' => (int) $alliance['level'],
                'xp' => (int) $alliance['xp'],
                'treasury_cash' => (int) $alliance['treasury_cash'],
                'treasuryText' => currency((int) $alliance['treasury_cash']),
                'crest_icon' => $crestIcon,
                'crest_color' => (string) ($alliance['crest_color'] ?? '#3b82f6'),
                'pass_name' => (string) $alliance['pass_name'],
                'pass_tier' => $curTierNum,
                'max_members' => (int) $alliance['max_members'],
            ],
            'stats' => [
                'memberCount' => count($members),
                'maxMembers' => (int) $alliance['max_members'],
                'totalSlopes' => $totalSlopes,
                'totalLifts' => $totalLifts,
                'totalSlopeKm' => $totalSlopeKm,
                'combinedValuationText' => currency($combinedTreasury),
            ],
            'pass' => [
                'tier' => $curTierNum,
                'tierName' => (string) $curTier['name'],
                'visitorPct' => (int) $curTier['visitor_pct'],
                'sisterBonus' => $sisterBonus,
                'totalBoost' => $totalPassVisitorBoost,
                'desc' => (string) $curTier['desc'],
                'nextTier' => $nextTierData,
            ],
            'perks' => $formattedPerks,
            'members' => $formattedMembers,
            'applications' => $formattedApps,
            'activities' => $formattedActs,
            'user' => [
                'cash' => (int) ($currentUserFinance['cash'] ?? 0),
                'cashText' => currency((int) ($currentUserFinance['cash'] ?? 0)),
                'rep' => (int) ($currentUserFinance['reputation'] ?? 0),
                'role' => (string) $membership['role'],
                'isLeader' => $isLeader,
                'isFounder' => $membership['role'] === 'founder',
            ],
        ];

        return view('alliances/index', ['page' => $page]);
    }

    public function directory()
    {
        $userId = auth()->id();
        $db = db_connect();

        $alliances = $db->query("
            SELECT 
                a.*,
                u.username as founder_name,
                (SELECT COUNT(*) FROM alliance_members am WHERE am.alliance_id = a.id) as member_count,
                (SELECT COALESCE(SUM(pf.cash), 0) + a.treasury_cash 
                 FROM alliance_members am 
                 LEFT JOIN player_finances pf ON pf.user_id = am.user_id 
                 WHERE am.alliance_id = a.id) as valuation
            FROM alliances a
            JOIN users u ON u.id = a.founder_id
            ORDER BY a.level DESC, valuation DESC
        ")->getResultArray();

        $userFinance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        $userCash = (int) ($userFinance['cash'] ?? 0);
        $userRep = (int) ($userFinance['reputation'] ?? 0);

        $myApplication = $db->table('alliance_applications')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->get()->getRowArray();

        $passTiers = AllianceModel::PASS_TIERS;

        $formattedAlliances = array_map(function ($a) use ($passTiers, $userRep) {
            $tier = $passTiers[(int) $a['pass_tier']] ?? $passTiers[1];
            $isFull = (int) $a['member_count'] >= (int) $a['max_members'];
            $meetsRep = $userRep >= (int) $a['min_reputation'];
            $rawCrest = $a['crest_icon'] ?? 'fa-mountain-sun';
            $crestIcon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', (string) $rawCrest);

            return [
                'id' => (int) $a['id'],
                'name' => (string) $a['name'],
                'tag' => (string) $a['tag'],
                'motto' => (string) ($a['motto'] ?? ''),
                'founder_name' => (string) $a['founder_name'],
                'crest_icon' => $crestIcon,
                'crest_color' => (string) ($a['crest_color'] ?? '#3b82f6'),
                'level' => (int) $a['level'],
                'member_count' => (int) $a['member_count'],
                'max_members' => (int) $a['max_members'],
                'valuation' => (int) $a['valuation'],
                'valuationText' => currency((int) $a['valuation']),
                'pass_tier' => (int) $a['pass_tier'],
                'passTierName' => (string) $tier['name'],
                'passVisitorPct' => (int) $tier['visitor_pct'],
                'min_reputation' => (int) $a['min_reputation'],
                'is_recruiting' => (int) $a['is_recruiting'] === 1,
                'isFull' => $isFull,
                'meetsRep' => $meetsRep,
                'canJoin' => !$isFull && $meetsRep && (int) $a['is_recruiting'] === 1,
                'canApply' => !$isFull && $meetsRep && (int) $a['is_recruiting'] !== 1,
            ];
        }, $alliances);

        $page = [
            'alliances' => $formattedAlliances,
            'user' => [
                'cash' => $userCash,
                'cashText' => currency($userCash),
                'rep' => $userRep,
                'canFound' => $userCash >= 50000,
            ],
            'myApplication' => $myApplication ? [
                'id' => (int) $myApplication['id'],
                'alliance_id' => (int) $myApplication['alliance_id'],
                'created_at' => (string) $myApplication['created_at'],
            ] : null,
            'foundingFee' => 50000,
            'foundingFeeText' => currency(50000),
        ];

        return view('alliances/directory', ['page' => $page]);
    }

    public function create(): ResponseInterface
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to('/login');

        // Check if already in an alliance
        if ($this->memberModel->where('user_id', $userId)->first()) {
            return redirect()->to('/alliances')->with('error', 'You are already a member of an alliance.');
        }

        $rules = [
            'name'           => 'required|min_length[3]|max_length[30]',
            'tag'            => 'required|min_length[3]|max_length[5]|alpha_numeric',
            'motto'          => 'permit_empty|max_length[100]',
            'description'    => 'permit_empty|max_length[500]',
            'crest_icon'     => 'permit_empty|max_length[50]',
            'crest_color'    => 'permit_empty|max_length[20]',
            'min_reputation' => 'permit_empty|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $name = trim($this->request->getPost('name'));
        $tag  = strtoupper(trim($this->request->getPost('tag')));

        $db = db_connect();

        // Check unique name and tag
        if ($this->allianceModel->where('name', $name)->first()) {
            return redirect()->back()->withInput()->with('error', 'An alliance with that name already exists.');
        }
        if ($this->allianceModel->where('tag', $tag)->first()) {
            return redirect()->back()->withInput()->with('error', 'An alliance with that tag ticker already exists.');
        }

        $fee = 50000;
        $crestColor = (string) $this->request->getPost('crest_color');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $crestColor)) {
            $crestColor = '#3b82f6';
        }

        $crestIcon = (string) $this->request->getPost('crest_icon');
        if (!preg_match('/^fa-[a-z0-9\-]+$/', $crestIcon)) {
            $crestIcon = 'fa-mountain-sun';
        }

        // The fee, the alliance and the founder's membership succeed or fail together: a founding that
        // loses a race (membership is unique per player) must not keep the 50,000 €
        $db->transStart();
        if (!Ledger::charge($userId, $fee, 'Alliance', 'Alliance founding fee: ' . $name)) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'Insufficient capital. Founding an alliance requires 50,000 €.');
        }

        // Create alliance record
        $allianceId = $this->allianceModel->insert([
            'name'           => $name,
            'tag'            => $tag,
            'motto'          => trim($this->request->getPost('motto') ?: 'Alpine excellence united.'),
            'description'    => trim($this->request->getPost('description') ?: 'A cooperative network of premier ski resorts.'),
            'crest_icon'     => $crestIcon,
            'crest_color'    => $crestColor,
            'founder_id'     => $userId,
            'level'          => 1,
            'xp'             => 0,
            'treasury_cash'  => 0,
            'max_members'    => 5,
            'min_reputation' => max(0, (int) $this->request->getPost('min_reputation')),
            'is_recruiting'  => $this->request->getPost('is_recruiting') ? 1 : 0,
            'pass_name'      => $name . ' Multi-Pass',
            'pass_tier'      => 1,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        // Enroll founder (a duplicate membership fails the insert, or throws when DBDebug is on)
        try {
            $enrolled = $allianceId && $this->memberModel->insert([
                'alliance_id'   => $allianceId,
                'user_id'       => $userId,
                'role'          => 'founder',
                'donated_cash'  => 0,
                'joined_at'     => date('Y-m-d H:i:s'),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            $enrolled = false;
        }
        if (!$enrolled) {
            $db->transRollback();
            return redirect()->to('/alliances')->with('error', 'You are already a member of an alliance.');
        }

        // Log alliance activity
        $db->table('alliance_activity_log')->insert([
            'alliance_id' => $allianceId,
            'user_id'     => $userId,
            'type'        => 'create',
            'message'     => "Founded the {$name} [{$tag}] alliance.",
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        if (function_exists('log_activity')) {
            log_activity($userId, 'Alliance', "Founded the {$name} [{$tag}] alliance for " . currency($fee), 'fa-solid fa-handshake');
        }

        return redirect()->to('/alliances')->with('success', "Congratulations! {$name} [{$tag}] has been chartered!");
    }

    public function join(int $allianceId): ResponseInterface
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to('/login');

        if ($this->memberModel->where('user_id', $userId)->first()) {
            return redirect()->to('/alliances')->with('error', 'You are already in an alliance.');
        }

        $alliance = $this->allianceModel->find($allianceId);
        if (!$alliance) {
            return redirect()->to('/alliances')->with('error', 'Alliance not found.');
        }

        $currentMembers = $this->memberModel->where('alliance_id', $allianceId)->countAllResults();
        if ($currentMembers >= (int) $alliance['max_members']) {
            return redirect()->to('/alliances')->with('error', 'This alliance has reached its maximum member capacity.');
        }

        $db = db_connect();
        $userFinance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        $userRep = (int) ($userFinance['reputation'] ?? 0);
        if ($userRep < (int) $alliance['min_reputation']) {
            return redirect()->to('/alliances')->with('error', "Your resort reputation ({$userRep}) does not meet the minimum requirement ({$alliance['min_reputation']}).");
        }

        // Open recruitment vs Application
        if ((int) $alliance['is_recruiting'] === 1) {
            $this->memberModel->insert([
                'alliance_id'   => $allianceId,
                'user_id'       => $userId,
                'role'          => 'member',
                'donated_cash'  => 0,
                'joined_at'     => date('Y-m-d H:i:s'),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            $userName = auth()->user()->username ?? 'Director';
            $db->table('alliance_activity_log')->insert([
                'alliance_id' => $allianceId,
                'user_id'     => $userId,
                'type'        => 'join',
                'message'     => "{$userName} joined the alliance.",
                'created_at'  => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to('/alliances')->with('success', "Welcome to {$alliance['name']}!");
        } else {
            // Submit application
            $exists = $db->table('alliance_applications')
                ->where('alliance_id', $allianceId)
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->get()->getRow();
            if ($exists) {
                return redirect()->to('/alliances')->with('info', 'Your application is already pending leadership review.');
            }

            $message = trim($this->request->getPost('message') ?: 'Requesting entry into the syndicate.');
            $db->table('alliance_applications')->insert([
                'alliance_id' => $allianceId,
                'user_id'     => $userId,
                'status'      => 'pending',
                'message'     => $message,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to('/alliances')->with('success', 'Application submitted to alliance leadership!');
        }
    }

    public function leave(): ResponseInterface
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to('/login');

        $membership = $this->memberModel->where('user_id', $userId)->first();
        if (!$membership) {
            return redirect()->to('/alliances');
        }

        $allianceId = (int) $membership['alliance_id'];
        $db = db_connect();

        if ($membership['role'] === 'founder') {
            $otherMembers = $this->memberModel
                ->where('alliance_id', $allianceId)
                ->where('user_id !=', $userId)
                ->countAllResults();
            if ($otherMembers > 0) {
                return redirect()->to('/alliances')->with('error', 'As founder, you must transfer leadership to an officer before leaving the alliance.');
            } else {
                // Founder is the only member -> disband alliance cleanly
                $this->memberModel->where('alliance_id', $allianceId)->delete();
                $db->table('alliance_unlocked_perks')->where('alliance_id', $allianceId)->delete();
                $db->table('alliance_applications')->where('alliance_id', $allianceId)->delete();
                $db->table('alliance_activity_log')->where('alliance_id', $allianceId)->delete();
                $this->allianceModel->delete($allianceId);

                return redirect()->to('/alliances')->with('info', 'Alliance disbanded.');
            }
        }

        $userName = auth()->user()->username ?? 'Director';
        $this->memberModel->where('user_id', $userId)->delete();

        $db->table('alliance_activity_log')->insert([
            'alliance_id' => $allianceId,
            'user_id'     => $userId,
            'type'        => 'leave',
            'message'     => "{$userName} departed the alliance.",
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/alliances')->with('info', 'You have left the alliance.');
    }

    public function donate(): ResponseInterface
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to('/login');

        $membership = $this->memberModel->where('user_id', $userId)->first();
        if (!$membership) return redirect()->to('/alliances');

        $amount = (int) $this->request->getPost('amount');
        if ($amount < 1000) {
            return redirect()->to('/alliances')->with('error', 'Minimum contribution is 1,000 €.');
        }

        $db = db_connect();
        // Deduct from player
        $db->transStart();
        if (!Ledger::charge($userId, $amount, 'Alliance', 'Alliance donation')) {
            $db->transRollback();
            return redirect()->to('/alliances')->with('error', 'Insufficient funds in your resort treasury.');
        }

        // Add to alliance treasury & member lifetime donation
        $allianceId = (int) $membership['alliance_id'];
        $xpEarned = max(1, (int) round($amount / 1000));

        $this->memberModel->where('user_id', $userId)->set('donated_cash', "donated_cash + {$amount}", false)->update();
        $this->allianceModel->where('id', $allianceId)
            ->set('treasury_cash', "treasury_cash + {$amount}", false)
            ->set('xp', "xp + {$xpEarned}", false)
            ->update();
        $db->transComplete();

        // Check Level-Up threshold
        $alliance = $this->allianceModel->find($allianceId);
        $curLevel = (int) $alliance['level'];
        $curXp    = (int) $alliance['xp'];

        $thresholds = AllianceModel::LEVEL_THRESHOLDS;
        $newLevel = $curLevel;
        foreach ($thresholds as $lvl => $cfg) {
            if ($curXp >= $cfg['xp']) {
                $newLevel = max($newLevel, $lvl);
            }
        }

        if ($newLevel > $curLevel) {
            $maxMembers = $thresholds[$newLevel]['max_members'];
            $this->allianceModel->update($allianceId, [
                'level'       => $newLevel,
                'max_members' => $maxMembers,
            ]);
            $db->table('alliance_activity_log')->insert([
                'alliance_id' => $allianceId,
                'type'        => 'level_up',
                'message'     => "Alliance promoted to Level {$newLevel}! Member capacity increased to {$maxMembers}.",
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        $userName = auth()->user()->username ?? 'Director';
        $db->table('alliance_activity_log')->insert([
            'alliance_id' => $allianceId,
            'user_id'     => $userId,
            'type'        => 'donate',
            'message'     => "{$userName} contributed " . currency($amount) . " to the Alliance Treasury.",
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/alliances')->with('success', "Contributed " . currency($amount) . " to the Treasury (+{$xpEarned} Alliance XP)!");
    }

    public function upgradePass(): ResponseInterface
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to('/login');

        $membership = $this->memberModel->where('user_id', $userId)->first();
        if (!$membership || !in_array($membership['role'], ['founder', 'officer'])) {
            return redirect()->to('/alliances')->with('error', 'Only alliance leaders can upgrade the Syndicate Multi-Pass.');
        }

        $alliance = $this->allianceModel->find($membership['alliance_id']);
        $curTier = (int) $alliance['pass_tier'];
        $nextTier = $curTier + 1;

        if (!isset(AllianceModel::PASS_TIERS[$nextTier])) {
            return redirect()->to('/alliances')->with('info', 'Your Syndicate Multi-Pass is already at the maximum tier.');
        }

        $tierConfig = AllianceModel::PASS_TIERS[$nextTier];
        if ((int)$alliance['level'] < $tierConfig['req_level']) {
            return redirect()->to('/alliances')->with('error', "Alliance Level {$tierConfig['req_level']} required to unlock {$tierConfig['name']}.");
        }

        $treasury = (int) $alliance['treasury_cash'];
        if ($treasury < $tierConfig['cost']) {
            return redirect()->to('/alliances')->with('error', 'Insufficient alliance treasury funds. Upgrade costs ' . currency($tierConfig['cost']) . '.');
        }

        $this->allianceModel->where('id', $alliance['id'])
            ->set('treasury_cash', "treasury_cash - {$tierConfig['cost']}", false)
            ->set('pass_tier', $nextTier)
            ->update();

        $db = db_connect();
        $db->table('alliance_activity_log')->insert([
            'alliance_id' => $alliance['id'],
            'user_id'     => $userId,
            'type'        => 'pass_upgrade',
            'message'     => "Upgraded Syndicate Pass to Tier {$nextTier} ({$tierConfig['name']})! All member resorts now gain +{$tierConfig['visitor_pct']}% visitors.",
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/alliances')->with('success', "Syndicate Multi-Pass upgraded to Tier {$nextTier} ({$tierConfig['name']})!");
    }

    public function unlockPerk(): ResponseInterface
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to('/login');

        $membership = $this->memberModel->where('user_id', $userId)->first();
        if (!$membership || !in_array($membership['role'], ['founder', 'officer'])) {
            return redirect()->to('/alliances')->with('error', 'Only alliance leaders can unlock perks.');
        }

        $perkKey = $this->request->getPost('perk_key');
        if (!isset(AllianceModel::PERKS[$perkKey])) {
            return redirect()->to('/alliances')->with('error', 'Invalid perk selection.');
        }

        $alliance = $this->allianceModel->find($membership['alliance_id']);
        $db = db_connect();

        $existing = $db->table('alliance_unlocked_perks')
            ->where('alliance_id', $alliance['id'])
            ->where('perk_key', $perkKey)
            ->get()->getRowArray();

        $curLevel = $existing ? (int) $existing['perk_level'] : 0;
        $nextLevel = $curLevel + 1;

        $perkDef = AllianceModel::PERKS[$perkKey];
        if (!isset($perkDef['levels'][$nextLevel])) {
            return redirect()->to('/alliances')->with('info', 'This perk is already fully upgraded.');
        }

        $lvlConfig = $perkDef['levels'][$nextLevel];
        if ((int)$alliance['level'] < $lvlConfig['req_level']) {
            return redirect()->to('/alliances')->with('error', "Alliance Level {$lvlConfig['req_level']} required for Level {$nextLevel}.");
        }

        $treasury = (int) $alliance['treasury_cash'];
        if ($treasury < $lvlConfig['cost']) {
            return redirect()->to('/alliances')->with('error', 'Insufficient treasury funds. Upgrade costs ' . currency($lvlConfig['cost']) . '.');
        }

        // Deduct treasury
        $this->allianceModel->where('id', $alliance['id'])
            ->set('treasury_cash', "treasury_cash - {$lvlConfig['cost']}", false)
            ->update();

        if ($existing) {
            $db->table('alliance_unlocked_perks')->where('id', $existing['id'])->update([
                'perk_level'  => $nextLevel,
                'unlocked_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $db->table('alliance_unlocked_perks')->insert([
                'alliance_id' => $alliance['id'],
                'perk_key'    => $perkKey,
                'perk_level'  => $nextLevel,
                'unlocked_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $db->table('alliance_activity_log')->insert([
            'alliance_id' => $alliance['id'],
            'user_id'     => $userId,
            'type'        => 'perk_unlocked',
            'message'     => "Upgraded {$perkDef['name']} to Level {$nextLevel} ({$lvlConfig['effect']})!",
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/alliances')->with('success', "{$perkDef['name']} upgraded to Level {$nextLevel}!");
    }

    public function manageMember(): ResponseInterface
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to('/login');

        $myMembership = $this->memberModel->where('user_id', $userId)->first();
        if (!$myMembership || !in_array($myMembership['role'], ['founder', 'officer'])) {
            return redirect()->to('/alliances')->with('error', 'Unauthorized.');
        }

        $targetUserId = (int) $this->request->getPost('target_user_id');
        $action = $this->request->getPost('action');

        if ($targetUserId === $userId) {
            return redirect()->to('/alliances')->with('error', 'Cannot perform leadership actions on yourself.');
        }

        $target = $this->memberModel
            ->where('alliance_id', $myMembership['alliance_id'])
            ->where('user_id', $targetUserId)
            ->first();

        if (!$target) {
            return redirect()->to('/alliances')->with('error', 'Target member not found in this alliance.');
        }

        // Officer restrictions: cannot kick/promote another officer or founder
        if ($myMembership['role'] === 'officer' && $target['role'] !== 'member') {
            return redirect()->to('/alliances')->with('error', 'Officers can only manage regular members.');
        }

        $db = db_connect();
        $targetUser = $db->table('users')->where('id', $targetUserId)->get()->getRowArray();
        $targetName = $targetUser['username'] ?? 'Director';

        switch ($action) {
            case 'promote':
                if ($myMembership['role'] !== 'founder') {
                    return redirect()->to('/alliances')->with('error', 'Only the Founder can appoint Officers.');
                }
                $this->memberModel->where('id', $target['id'])->update(['role' => 'officer']);
                $db->table('alliance_activity_log')->insert([
                    'alliance_id' => $myMembership['alliance_id'],
                    'message'     => "{$targetName} was promoted to Officer.",
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
                return redirect()->to('/alliances')->with('success', "Promoted {$targetName} to Officer.");

            case 'demote':
                if ($myMembership['role'] !== 'founder') {
                    return redirect()->to('/alliances')->with('error', 'Only the Founder can demote Officers.');
                }
                $this->memberModel->where('id', $target['id'])->update(['role' => 'member']);
                $db->table('alliance_activity_log')->insert([
                    'alliance_id' => $myMembership['alliance_id'],
                    'message'     => "{$targetName} was demoted to Member.",
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
                return redirect()->to('/alliances')->with('success', "Demoted {$targetName} to Member.");

            case 'kick':
                $this->memberModel->where('id', $target['id'])->delete();
                $db->table('alliance_activity_log')->insert([
                    'alliance_id' => $myMembership['alliance_id'],
                    'message'     => "{$targetName} was removed from the alliance.",
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
                return redirect()->to('/alliances')->with('info', "Removed {$targetName} from the alliance.");

            case 'transfer_founder':
                if ($myMembership['role'] !== 'founder') {
                    return redirect()->to('/alliances')->with('error', 'Only the Founder can transfer leadership.');
                }
                $this->memberModel->where('id', $myMembership['id'])->update(['role' => 'officer']);
                $this->memberModel->where('id', $target['id'])->update(['role' => 'founder']);
                $this->allianceModel->update($myMembership['alliance_id'], ['founder_id' => $targetUserId]);
                $db->table('alliance_activity_log')->insert([
                    'alliance_id' => $myMembership['alliance_id'],
                    'message'     => "Leadership transferred to {$targetName}!",
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
                return redirect()->to('/alliances')->with('success', "Transferred leadership to {$targetName}.");

            default:
                return redirect()->to('/alliances');
        }
    }

    public function reviewApplication(): ResponseInterface
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to('/login');

        $myMembership = $this->memberModel->where('user_id', $userId)->first();
        if (!$myMembership || !in_array($myMembership['role'], ['founder', 'officer'])) {
            return redirect()->to('/alliances')->with('error', 'Unauthorized.');
        }

        $appId = (int) $this->request->getPost('application_id');
        $action = $this->request->getPost('action');

        $db = db_connect();
        $application = $db->table('alliance_applications')
            ->where('id', $appId)
            ->where('alliance_id', $myMembership['alliance_id'])
            ->get()->getRowArray();

        if (!$application) {
            return redirect()->to('/alliances')->with('error', 'Application not found.');
        }

        if ($action === 'accept') {
            $alliance = $this->allianceModel->find($myMembership['alliance_id']);
            $currentCount = $this->memberModel->where('alliance_id', $alliance['id'])->countAllResults();
            if ($currentCount >= (int) $alliance['max_members']) {
                return redirect()->to('/alliances')->with('error', 'Alliance has reached maximum member capacity.');
            }

            // Enroll
            $this->memberModel->insert([
                'alliance_id'   => $alliance['id'],
                'user_id'       => $application['user_id'],
                'role'          => 'member',
                'donated_cash'  => 0,
                'joined_at'     => date('Y-m-d H:i:s'),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            $db->table('alliance_applications')->where('id', $appId)->update(['status' => 'accepted']);

            $applicant = $db->table('users')->where('id', $application['user_id'])->get()->getRowArray();
            $appName = $applicant['username'] ?? 'Director';
            $db->table('alliance_activity_log')->insert([
                'alliance_id' => $alliance['id'],
                'type'        => 'join',
                'message'     => "{$appName}'s application was accepted.",
                'created_at'  => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to('/alliances')->with('success', "Accepted {$appName} into the alliance!");
        } else {
            $db->table('alliance_applications')->where('id', $appId)->update(['status' => 'rejected']);
            return redirect()->to('/alliances')->with('info', 'Application declined.');
        }
    }

    public function leaderboard()
    {
        $db = db_connect();

        $alliances = $db->query("
            SELECT 
                a.*,
                u.username as founder_name,
                COUNT(am.id) as member_count,
                COALESCE(SUM(pf.cash), 0) + a.treasury_cash as valuation,
                COALESCE(SUM(pf.reputation), 0) as total_reputation,
                (SELECT COUNT(*) FROM player_items pi 
                 JOIN alliance_members am2 ON am2.user_id = pi.user_id 
                 WHERE am2.alliance_id = a.id AND pi.item_type = 'slope' AND pi.status = 'open') as total_slopes,
                (SELECT COUNT(*) FROM player_items pi 
                 JOIN alliance_members am2 ON am2.user_id = pi.user_id 
                 WHERE am2.alliance_id = a.id AND pi.item_type = 'lift' AND pi.status = 'open') as total_lifts,
                (SELECT COALESCE(SUM(pi.length_meters), 0) FROM player_items pi 
                 JOIN alliance_members am2 ON am2.user_id = pi.user_id 
                 WHERE am2.alliance_id = a.id AND pi.item_type = 'slope') as total_slope_meters
            FROM alliances a
            JOIN users u ON u.id = a.founder_id
            LEFT JOIN alliance_members am ON am.alliance_id = a.id
            LEFT JOIN player_finances pf ON pf.user_id = am.user_id
            GROUP BY a.id
            ORDER BY valuation DESC, total_slopes DESC
        ")->getResultArray();

        $myAllianceId = 0;
        if (auth()->loggedIn()) {
            $m = $this->memberModel->where('user_id', auth()->id())->first();
            if ($m) $myAllianceId = (int) $m['alliance_id'];
        }

        $passTiers = AllianceModel::PASS_TIERS;
        $formattedAlliances = [];
        foreach ($alliances as $idx => $a) {
            $rank = $idx + 1;
            $tierNum = (int) $a['pass_tier'];
            $tier = $passTiers[$tierNum] ?? $passTiers[1];
            $rawCrest = $a['crest_icon'] ?? 'fa-mountain-sun';
            $crestIcon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', (string) $rawCrest);
            $slopeKm = round((int) ($a['total_slope_meters'] ?? 0) / 1000, 1);
            $isMy = ((int) $a['id'] === $myAllianceId);

            $formattedAlliances[] = [
                'rank' => $rank,
                'id' => (int) $a['id'],
                'name' => (string) $a['name'],
                'tag' => (string) $a['tag'],
                'founder_name' => (string) $a['founder_name'],
                'crest_icon' => $crestIcon,
                'crest_color' => (string) ($a['crest_color'] ?? '#3b82f6'),
                'level' => (int) $a['level'],
                'member_count' => (int) $a['member_count'],
                'max_members' => (int) $a['max_members'],
                'total_slopes' => (int) $a['total_slopes'],
                'total_lifts' => (int) $a['total_lifts'],
                'total_slope_meters' => (int) ($a['total_slope_meters'] ?? 0),
                'slopeKm' => $slopeKm,
                'total_reputation' => (int) $a['total_reputation'],
                'valuation' => (int) $a['valuation'],
                'valuationText' => currency((int) $a['valuation']),
                'pass_tier' => $tierNum,
                'passTierName' => (string) $tier['name'],
                'passVisitorPct' => (int) $tier['visitor_pct'],
                'passTierBadge' => (string) ($tier['badge'] ?? 'badge-ghost'),
                'isMyAlliance' => $isMy,
            ];
        }

        $page = [
            'alliances' => $formattedAlliances,
            'myAllianceId' => $myAllianceId,
        ];

        return view('alliances/leaderboard', ['page' => $page]);
    }

    public function view(int $id)
    {
        $alliance = $this->allianceModel->find($id);
        if (!$alliance) {
            return redirect()->to('/alliances')->with('error', 'Alliance not found.');
        }

        $db = db_connect();
        $founder = $db->table('users')->where('id', $alliance['founder_id'])->get()->getRowArray();

        $members = $db->query("
            SELECT 
                am.*,
                u.username,
                pf.reputation,
                pf.resort_map,
                (SELECT COUNT(*) FROM player_items pi WHERE pi.user_id = u.id AND pi.item_type = 'slope' AND pi.status = 'open') as open_slopes,
                (SELECT COUNT(*) FROM player_items pi WHERE pi.user_id = u.id AND pi.item_type = 'lift' AND pi.status = 'open') as open_lifts
            FROM alliance_members am
            JOIN users u ON u.id = am.user_id
            LEFT JOIN player_finances pf ON pf.user_id = u.id
            WHERE am.alliance_id = ?
            ORDER BY am.role ASC, am.donated_cash DESC
        ", [$id])->getResultArray();

        $myMembership = null;
        if (auth()->loggedIn()) {
            $myMembership = $this->memberModel->where('user_id', auth()->id())->first();
        }

        $passTiers = AllianceModel::PASS_TIERS;
        $curTierNum = (int) $alliance['pass_tier'];
        $curTier = $passTiers[$curTierNum] ?? $passTiers[1];

        $rawCrest = $alliance['crest_icon'] ?? 'fa-mountain-sun';
        $crestIcon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', (string) $rawCrest);

        $formattedMembers = array_map(function ($m) {
            return [
                'user_id' => (int) $m['user_id'],
                'username' => (string) $m['username'],
                'role' => (string) $m['role'],
                'resort_map' => (string) ($m['resort_map'] ?? 'ParkCity'),
                'reputation' => (int) ($m['reputation'] ?? 0),
                'open_slopes' => (int) ($m['open_slopes'] ?? 0),
                'open_lifts' => (int) ($m['open_lifts'] ?? 0),
            ];
        }, $members);

        $canJoin = !$myMembership && (int) $alliance['is_recruiting'] === 1 && count($members) < (int) $alliance['max_members'];

        $page = [
            'alliance' => [
                'id' => (int) $alliance['id'],
                'name' => (string) $alliance['name'],
                'tag' => (string) $alliance['tag'],
                'motto' => (string) ($alliance['motto'] ?? ''),
                'description' => (string) ($alliance['description'] ?? ''),
                'level' => (int) $alliance['level'],
                'treasury_cash' => (int) $alliance['treasury_cash'],
                'treasuryText' => currency((int) $alliance['treasury_cash']),
                'crest_icon' => $crestIcon,
                'crest_color' => (string) ($alliance['crest_color'] ?? '#3b82f6'),
                'pass_name' => (string) $alliance['pass_name'],
                'pass_tier' => $curTierNum,
                'max_members' => (int) $alliance['max_members'],
                'is_recruiting' => (int) $alliance['is_recruiting'] === 1,
            ],
            'founder' => [
                'id' => (int) ($founder['id'] ?? 0),
                'username' => (string) ($founder['username'] ?? 'Unknown'),
            ],
            'pass' => [
                'tier' => $curTierNum,
                'name' => (string) $curTier['name'],
                'visitorPct' => (int) $curTier['visitor_pct'],
                'desc' => (string) $curTier['desc'],
            ],
            'members' => $formattedMembers,
            'isMember' => $myMembership !== null && (int) $myMembership['alliance_id'] === (int) $alliance['id'],
            'canJoin' => $canJoin,
        ];

        return view('alliances/view', ['page' => $page]);
    }
}
