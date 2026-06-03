# Ski Manager v2

A free-to-play browser-based ski resort management simulation game. Build slopes, hire staff, manage finances, and compete with players worldwide.

**Play now:** [skimanager.net](https://skimanager.net)

## Features

- **Interactive Trail Map** - Draw slopes and place lifts on a Leaflet.js-powered map
- **10 Staff Roles** - Ski patrol, groomers, instructors, chefs, mechanics, and more
- **Dynamic Weather** - Daily weather changes affect visitor count and snow conditions
- **30+ Game Systems** - Buildings, equipment, snowmaking, night skiing, terrain parks, energy, water, parking, insurance, government regulations, and more
- **Real Equipment Brands** - PistenBully, Prinoth, TechnoAlpin, Sufag, Demaclenko
- **Financial Management** - Revenue tracking, loans, insurance, marketing campaigns
- **Achievements & Leaderboards** - Track progress and compete globally
- **Resort Analysis** - Order detailed PDF reports analyzing your resort performance
- **VIP Guests** - Attract celebrities, film crews, and ski teams
- **Tutorial System** - Guided walkthrough for new players
- **Dark/Light Theme** - DaisyUI-powered theme switching
- **Mobile Responsive** - Play on any device
- **Google Sign-In** - Quick account creation

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | [CodeIgniter 4.7](https://codeigniter.com/) (PHP 8.5) |
| Auth | [CodeIgniter Shield](https://codeigniter4.github.io/shield/) |
| Frontend | [Tailwind CSS 4](https://tailwindcss.com/) + [DaisyUI 5](https://daisyui.com/) |
| Trail Map | [Leaflet.js](https://leafletjs.com/) with [Mapsynergy](https://www.skimap.org/) imagery |
| Icons | [Font Awesome 6](https://fontawesome.com/) |
| PDF | [Dompdf](https://github.com/dompdf/dompdf) + [qpdf](https://github.com/qpdf/qpdf) |
| Errors | [Sentry](https://sentry.io/) |
| Database | MySQL 8 |
| Server | VPS with Docker (OpenResty + PHP-FPM) |
| CDN/SSL | [Cloudflare](https://www.cloudflare.com/) |

## Game Systems

| Category | Systems |
|----------|---------|
| Resort | Trail Map, Slopes, Lifts, Scenic Lifts, Grooming |
| Operations | Staff, Snowmaking, Night Skiing, Terrain Parks, Parking |
| Buildings | Hotels, Restaurants, Rentals, Retail, Real Estate, Transportation, Ski Patrol |
| Business | Finances, Bank and Loans, Tickets, Marketing, Insurance, Equipment |
| Resources | Energy Management, Water Management |
| Compliance | Government Regulations, Environment |
| Progression | Achievements, Leaderboard, Tournaments, Daily Bonus, VIP Guests |
| Meta | Resort Analysis (PDF), Off-Season, Notifications, Tutorial |

## Setup

1. Clone the repo and install dependencies:

       git clone https://gitlab.com/contact1231/skimanager-v2.git
       cd skimanager-v2
       composer install

2. Copy the env template and configure your database:

       cp env .env

3. Run migrations:

       php spark migrate

4. Build CSS:

       npx @tailwindcss/cli -i public/css/input.css -o public/css/style.css --minify

5. Start the dev server:

       php spark serve

## Game Tick

The game engine runs daily via cron (processes salaries, revenue, weather, construction, etc.):

    0 4 * * * cd /path/to/project && php spark game:tick

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Open a Merge Request

## Links

- **Play:** [skimanager.net](https://skimanager.net)
- **Wiki:** [wiki.ski-manager.net](https://wiki.ski-manager.net)
- **Discord:** [Join](https://discord.gg/TyEnFdfd8w)
- **Bug Reports:** [GitLab Issues](https://gitlab.com/contact1231/skimanager-v2/-/issues)

## License

All rights reserved. Source code available for reference and contribution.
