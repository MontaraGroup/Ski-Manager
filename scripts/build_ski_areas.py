#!/usr/bin/env python3
"""
Builds app/Database/Seeds/data/ski_areas_na.csv, the candidate list for the monthly Resort Vote.

Source: OpenSkiMap ski areas (https://openskimap.org), built from OpenStreetMap data.
License: Open Database License 1.0 (https://opendatacommons.org/licenses/odbl/1-0/).
Attribution: © OpenStreetMap contributors, OpenSkiMap.

Keeps operating downhill ski areas with a name in the United States, Canada and Mexico.
Standard library only. Run it by hand when the list needs a refresh:

    curl -o /tmp/ski_areas.geojson https://tiles.openskimap.org/geojson/ski_areas.geojson
    python3 scripts/build_ski_areas.py /tmp/ski_areas.geojson
"""
import csv
import json
import os
import sys

COUNTRIES = {'US', 'CA', 'MX'}
OUT = os.path.join(os.path.dirname(__file__), '..', 'app', 'Database', 'Seeds', 'data', 'ski_areas_na.csv')


def first_point(geom):
    """A representative [lng, lat] for Point, Polygon and Multi* geometries"""
    if not geom:
        return None
    c = geom.get('coordinates')
    while isinstance(c, list) and c and isinstance(c[0], list):
        c = c[0]
    return c if isinstance(c, list) and len(c) >= 2 else None


def main(path):
    with open(path, encoding='utf-8') as f:
        features = json.load(f)['features']

    rows = []
    for feat in features:
        p = feat.get('properties') or {}
        name = (p.get('name') or '').strip()
        if not name or p.get('status') not in ('operating', None):
            continue
        if 'downhill' not in (p.get('activities') or []):
            continue
        place = next((pl for pl in (p.get('places') or []) if pl.get('iso3166_1Alpha2') in COUNTRIES), None)
        if not place:
            continue
        loc = ((place.get('localized') or {}).get('en') or {})
        stats = p.get('statistics') or {}
        downhill = ((stats.get('runs') or {}).get('byActivity') or {}).get('downhill') or {}
        runs = sum(int(d.get('count') or 0) for d in (downhill.get('byDifficulty') or {}).values())
        lifts = sum(int(t.get('count') or 0) for t in ((stats.get('lifts') or {}).get('byType') or {}).values())
        top, bottom = stats.get('maxElevation'), stats.get('minElevation')
        vertical = round(top - bottom) if isinstance(top, (int, float)) and isinstance(bottom, (int, float)) else ''
        center = (p.get('viewportHint') or {}).get('center') or first_point(feat.get('geometry'))
        websites = p.get('websites') or []
        rows.append({
            'source_id': p.get('id') or feat.get('id') or '',
            'name': name,
            'country': place['iso3166_1Alpha2'],
            'region_code': place.get('iso3166_2') or '',
            'region': loc.get('region') or '',
            'locality': loc.get('locality') or '',
            'runs': runs,
            'lifts': lifts,
            'vertical_m': vertical,
            'top_m': round(top) if isinstance(top, (int, float)) else '',
            'lat': round(center[1], 5) if center else '',
            'lng': round(center[0], 5) if center else '',
            'website': (websites[0] if websites else '')[:255],
        })

    # OSM sometimes maps one resort twice; keep the entry with the most runs
    best = {}
    for r in rows:
        key = (r['name'].lower(), r['region_code'])
        if key not in best or r['runs'] > best[key]['runs']:
            best[key] = r
    rows = sorted(best.values(), key=lambda r: (r['country'], r['region'], r['name'].lower()))
    os.makedirs(os.path.dirname(OUT), exist_ok=True)
    with open(OUT, 'w', encoding='utf-8', newline='') as f:
        f.write('# OpenSkiMap ski areas (https://openskimap.org), © OpenStreetMap contributors, ODbL 1.0\n')
        w = csv.DictWriter(f, fieldnames=list(rows[0].keys()))
        w.writeheader()
        w.writerows(rows)
    print(f'{len(rows)} ski areas written to {os.path.normpath(OUT)}')


if __name__ == '__main__':
    if len(sys.argv) != 2:
        sys.exit('usage: build_ski_areas.py <ski_areas.geojson>')
    main(sys.argv[1])
