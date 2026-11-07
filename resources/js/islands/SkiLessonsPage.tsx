import { Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Figure, Page, Panel } from '@/components/Page';

export type LessonProgramItem = {
  key: string;
  name: string;
  duration: string;
  price: number;
  priceText: string;
  instructors: number;
  maxStudents: number;
  icon: string;
};

export type InstructorItem = {
  id: number;
  name: string;
  status: string;
  level: number;
  salaryText: string;
  morale: number;
  assignedTo: string | null;
};

export type SkiLessonsPageProps = {
  stats: {
    activeInstructors: number;
    totalInstructors: number;
    dailyCapacity: number;
    lessonsToday: number;
    openSlopesCount: number;
    estimatedRevenueText: string;
  };
  recentLessonLog: string | null;
  lessonPrograms: LessonProgramItem[];
  instructors: InstructorItem[];
};

export default function SkiLessonsPage({
  stats,
  recentLessonLog,
  lessonPrograms,
  instructors,
}: SkiLessonsPageProps) {
  return (
    <Page
      title="Ski school & lessons"
      icon="fa-chalkboard-user"
      iconTone="text-accent"
      subtitle="Offer professional lessons to boost resort satisfaction and daily revenue"
      width="max-w-5xl"
      actions={
        <a
          href="/staff/hire"
          className="inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-accent px-3 text-sm font-medium text-accent-foreground hover:bg-accent-hover"
        >
          <Icon name="fa-user-plus" className="mr-0.5" />
          Hire instructors
        </a>
      }
    >
      {/* 3 Quick stats figures */}
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <Figure
          label="Active instructors"
          value={stats.activeInstructors}
          tone="text-accent"
          note={`${stats.dailyCapacity} students daily capacity`}
        />
        <Figure
          label="Classes / day"
          value={stats.lessonsToday}
          note={`${stats.openSlopesCount} open slopes available`}
        />
        <Figure
          label="Est. daily lesson income"
          value={stats.estimatedRevenueText}
          tone="text-success"
          note="From daily simulation"
        />
      </div>

      {/* Activity / Inactive Alerts */}
      {stats.activeInstructors === 0 ? (
        <div className="rounded-xl border border-warning/40 bg-warning/10 p-4 text-sm flex items-start gap-3">
          <Icon name="fa-triangle-exclamation" className="text-warning mt-0.5 text-base shrink-0" />
          <div className="space-y-1">
            <div className="font-semibold text-foreground">No active ski instructors on staff</div>
            <div className="text-muted">
              Your ski school is currently dormant.{' '}
              <a href="/staff/hire" className="text-link underline hover:no-underline">
                Hire instructors
              </a>{' '}
              to start teaching beginner through advanced skiers.
            </div>
          </div>
        </div>
      ) : recentLessonLog ? (
        <div className="rounded-xl border border-success/40 bg-success/10 p-4 text-sm flex items-start gap-3">
          <Icon name="fa-circle-check" className="text-success mt-0.5 text-base shrink-0" />
          <div>
            <div className="font-semibold text-foreground">Recent ski school activity</div>
            <div className="text-muted">{recentLessonLog}</div>
          </div>
        </div>
      ) : null}

      {/* Lesson Programs Table */}
      <Panel
        title={
          <span className="flex items-center gap-2">
            <Icon name="fa-graduation-cap" className="text-accent" />
            Lesson programs & pricing
          </span>
        }
      >
        <div className="overflow-x-auto -mx-4 -mb-4 px-4 pb-4">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-border text-xs text-muted">
              <tr>
                <th className="py-2.5 pr-4 font-medium">Program</th>
                <th className="py-2.5 px-4 font-medium">Duration</th>
                <th className="py-2.5 px-4 font-medium">Ticket price</th>
                <th className="py-2.5 px-4 font-medium">Instructors</th>
                <th className="py-2.5 pl-4 font-medium">Max class size</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {lessonPrograms.map((prog) => (
                <tr key={prog.key} className="hover:bg-surface-secondary/50 transition-colors">
                  <td className="py-3 pr-4 font-medium text-foreground">
                    <span className="flex items-center gap-2">
                      <Icon name={prog.icon} className="text-muted" />
                      {prog.name}
                    </span>
                  </td>
                  <td className="py-3 px-4 text-muted">{prog.duration}</td>
                  <td className="py-3 px-4 font-semibold text-success tabular-nums">{prog.priceText}</td>
                  <td className="py-3 px-4">
                    <Chip size="sm" variant="soft" className="text-xs">
                      {prog.instructors} instructor{prog.instructors !== 1 ? 's' : ''}
                    </Chip>
                  </td>
                  <td className="py-3 pl-4 text-muted tabular-nums">{prog.maxStudents} students</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Panel>

      {/* Hired Instructors Roster */}
      <Panel
        title={
          <span className="flex items-center gap-2">
            <Icon name="fa-users" className="text-accent" />
            Instructor roster
          </span>
        }
        actions={
          <a
            href="/staff"
            className="text-xs text-link flex items-center gap-1 hover:underline pointer-coarse:min-h-11 pointer-coarse:flex pointer-coarse:items-center"
          >
            Manage in staff
            <Icon name="fa-arrow-right" className="text-[10px]" />
          </a>
        }
      >
        {instructors.length === 0 ? (
          <Empty icon="fa-person-skiing" title="No instructors hired yet">
            Visit the{' '}
            <a href="/staff/hire" className="text-link underline hover:no-underline">
              hiring page
            </a>{' '}
            to recruit instructors.
          </Empty>
        ) : (
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
            {instructors.map((inst) => (
              <div
                key={inst.id}
                className="rounded-xl border border-border bg-surface-secondary p-3 flex flex-col justify-between gap-2"
              >
                <div className="flex items-center justify-between gap-2">
                  <span className="font-semibold text-sm truncate">{inst.name}</span>
                  <Chip
                    size="sm"
                    variant="soft"
                    color={inst.status === 'active' ? 'success' : 'default'}
                  >
                    {inst.status === 'active' ? 'Active' : 'Off'}
                  </Chip>
                </div>
                <div className="text-xs text-muted space-y-1">
                  <div className="flex items-center gap-1.5">
                    <Icon name="fa-medal" className="text-warning text-[10px]" />
                    <span>Level {inst.level} · Salary {inst.salaryText} / day</span>
                  </div>
                  <div className="flex items-center gap-1.5">
                    <Icon name="fa-face-smile" className="text-[10px]" />
                    <span>Morale: {inst.morale}%</span>
                  </div>
                  <div className="flex items-center gap-1.5 text-link">
                    <Icon name="fa-location-dot" className="text-[10px]" />
                    <span className="truncate">{inst.assignedTo || 'General Ski School'}</span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </Panel>

      {/* How Ski School Operates Info Box */}
      <div className="rounded-xl border border-border bg-surface p-4 text-xs text-muted flex items-start gap-3">
        <Icon name="fa-circle-info" className="text-accent mt-0.5 text-base shrink-0" />
        <div className="space-y-1">
          <div className="font-semibold text-sm text-foreground">How ski school operates</div>
          <p className="leading-relaxed">
            During the daily simulation tick, visitors enrolled in lessons pay full class fees directly into resort revenue. More experienced instructors and higher resort ratings draw larger class enrollments.
          </p>
        </div>
      </div>
    </Page>
  );
}
