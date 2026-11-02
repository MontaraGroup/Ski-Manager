import React, { useState } from 'react';
import {
  Button,
  Card,
  Chip,
  Badge,
  Alert,
} from '@heroui/react';

export default function HeroDemo({ initialCount = 0, resortName = 'Peak Mountain Resort' }) {
  const [count, setCount] = useState(initialCount);
  const [ticketSales, setTicketSales] = useState(1240);

  return (
    <div className="flex flex-col gap-6 p-6 max-w-4xl mx-auto text-foreground">
      {/* HeroUI Card */}
      <Card className="p-6 bg-surface border border-border shadow-2xl rounded-2xl">
        <div className="flex items-center justify-between border-b border-border pb-4 mb-4">
          <div>
            <div className="flex items-center gap-3">
              <h2 className="text-2xl font-bold tracking-tight">{resortName}</h2>
              <Chip variant="soft" color="success">
                Season 2 Active
              </Chip>
            </div>
            <p className="text-sm text-muted mt-1">
              Powered by real HeroUI v3 React components & Tailwind CSS v4
            </p>
          </div>
          <Badge content="Live" color="danger" shape="circle" placement="top-right">
            <div className="w-3 h-3 rounded-full bg-emerald-500 animate-pulse" />
          </Badge>
        </div>

        {/* Interactive Stats Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 my-4">
          <div className="p-4 rounded-xl bg-surface-secondary border border-border">
            <div className="text-xs uppercase font-semibold text-muted tracking-wider">
              Total Visitors
            </div>
            <div className="text-2xl font-extrabold mt-1 text-accent">
              {(ticketSales * 1.8).toLocaleString()}
            </div>
            <div className="text-xs text-emerald-400 mt-1">↑ +14.2% today</div>
          </div>

          <div className="p-4 rounded-xl bg-surface-secondary border border-border">
            <div className="text-xs uppercase font-semibold text-muted tracking-wider">
              Lift Pass Sales
            </div>
            <div className="text-2xl font-extrabold mt-1 text-foreground">
              ${(ticketSales * 85).toLocaleString()}
            </div>
            <div className="text-xs text-muted mt-1">Avg $85 / pass</div>
          </div>

          <div className="p-4 rounded-xl bg-surface-secondary border border-border">
            <div className="text-xs uppercase font-semibold text-muted tracking-wider">
              Operating Lifts
            </div>
            <div className="text-2xl font-extrabold mt-1 text-foreground">
              12 / 12 Open
            </div>
            <div className="text-xs text-emerald-400 mt-1">100% Capacity</div>
          </div>
        </div>

        {/* Real HeroUI Buttons with variants */}
        <div className="flex flex-wrap items-center gap-3 mt-6 pt-4 border-t border-border">
          <Button
            color="primary"
            className="font-medium"
            onPress={() => setTicketSales(s => s + 50)}
          >
            Sell +50 Passes
          </Button>

          <Button
            variant="secondary"
            onPress={() => setCount(c => c + 1)}
          >
            Clicked {count} times
          </Button>

          <Button
            variant="outline"
            onPress={() => {
              setCount(0);
              setTicketSales(1240);
            }}
          >
            Reset Stats
          </Button>
        </div>
      </Card>

      {/* HeroUI Alert Component */}
      <Alert
        status="info"
        title="HeroUI React Integration Verified"
        description="This component is rendered by React 19 and HeroUI v3 mounted into CodeIgniter 4."
      />
    </div>
  );
}
