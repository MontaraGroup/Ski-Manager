import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page, Panel } from '@/components/Page';

export type TourSlope = {
  id: number;
  name: string;
  difficulty: string;
  difficultyLabel: string;
  condition_pct: number;
  snow_depth_formatted: string;
  snow_quality: string;
  status: string;
};

export type TourLift = {
  id: number;
  name: string;
  subtype: string;
  subtypeName: string;
  condition_pct: number;
  status: string;
};

export type TourSector = {
  sectorNum: number;
  slopes: TourSlope[];
  lifts: TourLift[];
};

export type TourPageProps = {
  owner: {
    id: number;
    username: string;
  };
  alliance: {
    id: number;
    name: string;
    tag: string;
    crest_icon: string;
    crest_color: string;
    member_role: string;
  } | null;
  stats: {
    openSlopes: number;
    openLifts: number;
    staffCount: number;
    buildingCount: number;
    parks: number;
  };
  sectors: TourSector[];
  likeCount: number;
  hasLiked: boolean;
  isSelf: boolean;
  rating: {
    stars: number;
    score: number;
    max: number;
  };
};

export default function TourPage({
  owner = { id: 0, username: 'Player' },
  alliance = null,
  stats = {
    openSlopes: 0,
    openLifts: 0,
    staffCount: 0,
    buildingCount: 0,
    parks: 0,
  },
  sectors = [],
  likeCount = 0,
  hasLiked = false,
  isSelf = false,
  rating = { stars: 0, score: 0, max: 200 },
}: TourPageProps) {
  const getDifficultyBadge = (diff: string) => {
    switch (diff) {
      case 'green':
        return 'bg-success/15 text-success border-success/30';
      case 'red':
        return 'bg-danger/15 text-danger border-danger/30';
      case 'black':
      case 'double_black':
        return 'bg-neutral/20 text-foreground border-neutral/40';
      default:
        return 'bg-primary/15 text-primary border-primary/30';
    }
  };

  return (
    <Page
      title={`${owner.username}'s Resort`}
      icon="fa-binoculars"
      iconTone="text-primary"
      back="/leaderboard"
      width="max-w-5xl"
      subtitle={
        <div className="flex items-center gap-2 flex-wrap">
          {alliance && (
            <a
              href={`/alliances/view/${alliance.id}`}
              className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-bold text-white font-mono"
              style={{ backgroundColor: alliance.crest_color }}
            >
              <Icon name={alliance.crest_icon} className="text-xs" />
              [{alliance.tag}] {alliance.name}
            </a>
          )}
          <span className="flex items-center text-xs text-muted">
            {Array.from({ length: 5 }).map((_, i) => (
              <Icon
                key={i}
                name="fa-star"
                className={`text-xs ${
                  i < rating.stars ? 'text-warning' : 'text-muted/30'
                }`}
              />
            ))}
            <span className="ml-1.5 tabular-nums">
              {rating.score}/{rating.max} rating
            </span>
          </span>
        </div>
      }
      actions={
        !isSelf ? (
          <form action={`/tour/${owner.id}/like`} method="post">
            <CsrfInput />
            <Button
              type="submit"
              size="sm"
              variant={hasLiked ? 'danger' : 'outline'}
              className="font-semibold text-xs min-h-[36px] gap-1.5"
            >
              <Icon name="fa-heart" className={hasLiked ? 'text-white' : 'text-danger'} />
              {hasLiked ? 'Liked' : 'Like'}
              <span className="px-1.5 py-0.2 rounded-full bg-base-100/30 text-[10px] tabular-nums font-bold ml-1">
                {likeCount}
              </span>
            </Button>
          </form>
        ) : null
      }
    >
      <div className="space-y-6">
        {/* 5 KPI Stats */}
        <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
          <div className="rounded-xl border border-border bg-base-100 p-3 text-center">
            <div className="text-2xl font-bold text-primary tabular-nums">{stats.openSlopes}</div>
            <div className="text-xs text-muted">Open Slopes</div>
          </div>
          <div className="rounded-xl border border-border bg-base-100 p-3 text-center">
            <div className="text-2xl font-bold text-success tabular-nums">{stats.openLifts}</div>
            <div className="text-xs text-muted">Open Lifts</div>
          </div>
          <div className="rounded-xl border border-border bg-base-100 p-3 text-center">
            <div className="text-2xl font-bold text-warning tabular-nums">{stats.staffCount}</div>
            <div className="text-xs text-muted">Staff</div>
          </div>
          <div className="rounded-xl border border-border bg-base-100 p-3 text-center">
            <div className="text-2xl font-bold text-secondary tabular-nums">
              {stats.buildingCount}
            </div>
            <div className="text-xs text-muted">Buildings</div>
          </div>
          <div className="rounded-xl border border-border bg-base-100 p-3 text-center">
            <div className="text-2xl font-bold text-foreground tabular-nums">{stats.parks}</div>
            <div className="text-xs text-muted">Terrain Parks</div>
          </div>
        </div>

        {/* Sectors */}
        {sectors.map((sec) => (
          <Panel
            key={sec.sectorNum}
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-mountain" className="text-primary" />
                Sector {sec.sectorNum}
              </span>
            }
          >
            <div className="space-y-6">
              {sec.slopes.length > 0 && (
                <div>
                  <h4 className="font-semibold text-xs text-muted uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <Icon name="fa-person-skiing" className="text-info text-xs" /> Slopes (
                    {sec.slopes.length})
                  </h4>
                  <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="w-full text-left text-xs">
                      <thead>
                        <tr className="border-b border-border text-muted uppercase text-[10px] font-bold">
                          <th className="py-2.5 px-3">Slope</th>
                          <th className="py-2.5 px-3">Difficulty</th>
                          <th className="py-2.5 px-3">Snow Depth</th>
                          <th className="py-2.5 px-3">Condition</th>
                          <th className="py-2.5 px-3 text-right">Status</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border/40">
                        {sec.slopes.map((slope) => (
                          <tr key={slope.id} className="hover:bg-base-200/40 transition-colors">
                            <td className="py-2.5 px-3 font-semibold text-foreground">
                              {slope.name}
                            </td>
                            <td className="py-2.5 px-3">
                              <span
                                className={`px-2 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wider ${getDifficultyBadge(
                                  slope.difficulty
                                )}`}
                              >
                                {slope.difficultyLabel}
                              </span>
                            </td>
                            <td className="py-2.5 px-3 text-muted tabular-nums">
                              {slope.snow_depth_formatted} · {slope.snow_quality}
                            </td>
                            <td className="py-2.5 px-3">
                              <span
                                className={`font-semibold tabular-nums ${
                                  slope.condition_pct >= 70
                                    ? 'text-success'
                                    : slope.condition_pct >= 40
                                    ? 'text-warning'
                                    : 'text-danger'
                                }`}
                              >
                                {slope.condition_pct}%
                              </span>
                            </td>
                            <td className="py-2.5 px-3 text-right">
                              <span
                                className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${
                                  slope.status === 'open'
                                    ? 'bg-success/20 text-success'
                                    : 'bg-muted/20 text-muted'
                                }`}
                              >
                                {slope.status}
                              </span>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {sec.lifts.length > 0 && (
                <div>
                  <h4 className="font-semibold text-xs text-muted uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <Icon name="fa-cable-car" className="text-success text-xs" /> Ski Lifts (
                    {sec.lifts.length})
                  </h4>
                  <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="w-full text-left text-xs">
                      <thead>
                        <tr className="border-b border-border text-muted uppercase text-[10px] font-bold">
                          <th className="py-2.5 px-3">Lift</th>
                          <th className="py-2.5 px-3">Type</th>
                          <th className="py-2.5 px-3">Condition</th>
                          <th className="py-2.5 px-3 text-right">Status</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border/40">
                        {sec.lifts.map((lift) => (
                          <tr key={lift.id} className="hover:bg-base-200/40 transition-colors">
                            <td className="py-2.5 px-3 font-semibold text-foreground">
                              {lift.name}
                            </td>
                            <td className="py-2.5 px-3 text-muted">{lift.subtypeName}</td>
                            <td className="py-2.5 px-3">
                              <span
                                className={`font-semibold tabular-nums ${
                                  lift.condition_pct >= 70
                                    ? 'text-success'
                                    : lift.condition_pct >= 40
                                    ? 'text-warning'
                                    : 'text-danger'
                                }`}
                              >
                                {lift.condition_pct}%
                              </span>
                            </td>
                            <td className="py-2.5 px-3 text-right">
                              <span
                                className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${
                                  lift.status === 'open'
                                    ? 'bg-success/20 text-success'
                                    : lift.status === 'broken'
                                    ? 'bg-danger/20 text-danger'
                                    : 'bg-muted/20 text-muted'
                                }`}
                              >
                                {lift.status}
                              </span>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}
            </div>
          </Panel>
        ))}
      </div>
    </Page>
  );
}
