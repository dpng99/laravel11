#!/usr/bin/env python3
"""Pure-Python analysis engine for the AKIP admin BI dashboard."""

import json
import statistics
import sys
from collections import Counter, defaultdict
from datetime import datetime, timezone


def rounded(value):
    return round(float(value), 1)


def average(values):
    return sum(values) / len(values) if values else 0.0


def risk_level(score, trend, measured_count):
    if measured_count <= 0 or score < 70 or (score < 80 and trend < -10):
        return "Tinggi"
    if score < 90 or trend < -5:
        return "Sedang"
    return "Rendah"


def value_or_none(value):
    return None if value is None else float(value)


def analyze(payload):
    years = payload["years"]
    current_year = payload["selected_year"]
    previous_year = years[-2] if len(years) > 1 else current_year
    satkers = payload.get("satkers", [])
    ikss_rows = payload.get("ikss", [])
    strategic_objectives = payload.get("strategic_objectives", [])
    priorities = []
    region_rows = defaultdict(list)
    national_trend = []

    for year in years:
        national_trend.append({
            "year": year,
            "achievement": rounded(average([row["history"].get(year, 0) for row in satkers])),
        })

    for row in satkers:
        current = float(row["history"].get(current_year, 0))
        previous = float(row["history"].get(previous_year, 0))
        trend = current - previous
        history_values = [float(row["history"].get(year, 0)) for year in years]
        volatility = statistics.pstdev(history_values) if len(history_values) > 1 else 0
        measured = int(row.get("measured_ikss", 0))
        total_ikss = max(int(row.get("ikss_total", 0)), 1)
        missing_ratio = max(0, int(row.get("missing_ikss_count", 0))) / total_ikss
        risk = risk_level(current, trend, measured)
        risk_score = min(100, max(0, max(100 - current, 0) * 0.8 + max(-trend, 0) * 1.4 + volatility * 0.4 + missing_ratio * 25))
        result = {
            "id_satker": row["id_satker"],
            "satkernama": row["satkernama"],
            "kejati_name": row["kejati_name"],
            "achievement": rounded(current),
            "target_average": value_or_none(row.get("target_average")),
            "capaian_average": value_or_none(row.get("capaian_average")),
            "trend": rounded(trend),
            "risk": risk,
            "risk_score": rounded(risk_score),
            "measured_ikss": measured,
            "ikss_total": total_ikss,
            "under_target_count": int(row.get("under_target_count", 0)),
            "missing_ikss_count": int(row.get("missing_ikss_count", 0)),
            "attention_ikss": row.get("attention_ikss", [])[:5],
        }
        priorities.append(result)
        region_rows[row["kejati_name"]].append(result)

    priorities.sort(key=lambda item: (-item["risk_score"], item["achievement"], item["satkernama"]))
    risk_counts = Counter(row["risk"] for row in priorities)
    region_performance = []

    for name, rows in region_rows.items():
        achievement = average([row["achievement"] for row in rows])
        trend = average([row["trend"] for row in rows])
        high_risk = sum(row["risk"] == "Tinggi" for row in rows)
        region_performance.append({
            "name": name,
            "satkers": len(rows),
            "achievement": rounded(achievement),
            "trend": rounded(trend),
            "high_risk": high_risk,
        })

    region_performance.sort(key=lambda item: (item["achievement"], item["trend"]))
    region_values = [row["achievement"] for row in region_performance]
    region_mean = average(region_values)
    region_std = statistics.pstdev(region_values) if len(region_values) > 1 else 0
    anomalies = [
        {
            **row,
            "deviation": rounded((row["achievement"] - region_mean) / region_std) if region_std else 0,
        }
        for row in region_performance
        if region_std and abs((row["achievement"] - region_mean) / region_std) >= 1.5
    ]

    ikss_opportunities = sorted(
        ikss_rows,
        key=lambda item: (
            1 if item.get("average_achievement") is None else 0,
            item.get("average_achievement") if item.get("average_achievement") is not None else -1,
            item.get("coverage", 0),
            item.get("name", ""),
        ),
    )
    objective_performance = sorted(
        strategic_objectives,
        key=lambda item: (
            1 if item.get("average_achievement") is None else 0,
            item.get("average_achievement") if item.get("average_achievement") is not None else -1,
            item.get("name", ""),
        ),
    )
    improving = sum(row["trend"] > 2 for row in priorities)
    declining = sum(row["trend"] < -2 for row in priorities)
    current_average = national_trend[-1]["achievement"] if national_trend else 0
    previous_average = national_trend[-2]["achievement"] if len(national_trend) > 1 else current_average
    insights = []

    if ikss_opportunities:
        weakest = ikss_opportunities[0]
        achievement_text = "belum ada data" if weakest.get("average_achievement") is None else f"{weakest['average_achievement']}%"
        insights.append({
            "severity": "warning",
            "title": f"Fokus IKSS: {weakest['name']}",
            "description": f"Capaian terhadap target {achievement_text}; {weakest.get('below_target_satkers', 0)} satker masih di bawah target.",
            "action": "Mulai pendalaman pada satker di bawah target dan validasi kecukupan input capaian-target.",
        })
    if objective_performance:
        weakest_objective = objective_performance[0]
        if weakest_objective.get("average_achievement") is not None:
            insights.append({
                "severity": "warning" if weakest_objective["average_achievement"] < 100 else "info",
                "title": f"SS terlemah: {weakest_objective['name']}",
                "description": f"Rata-rata capaian terhadap target SS ini {weakest_objective['average_achievement']}% dari {weakest_objective.get('measured_ikss', 0)} IKSS terukur.",
                "action": "Gunakan rincian IKSS di SS ini sebagai pintu masuk evaluasi kinerja.",
            })
    if priorities:
        insights.append({
            "severity": "error" if risk_counts["Tinggi"] else "info",
            "title": f"{risk_counts['Tinggi']} satker berisiko tinggi",
            "description": "Risiko dihitung dari capaian terhadap target tahun berjalan, penurunan tahunan, volatilitas empat tahun, dan IKSS yang belum terukur.",
            "action": "Mulai tindak lanjut dari satker dengan gap capaian-target paling besar.",
        })
    if anomalies:
        weakest_anomaly = min(anomalies, key=lambda item: item["deviation"])
        insights.append({
            "severity": "warning",
            "title": f"Anomali wilayah: {weakest_anomaly['name']}",
            "description": f"Kinerja wilayah menyimpang {abs(weakest_anomaly['deviation'])} simpangan baku dari rata-rata nasional.",
            "action": "Verifikasi hambatan proses atau kualitas data pada wilayah tersebut.",
        })
    insights.append({
        "severity": "success" if current_average >= previous_average else "warning",
        "title": f"Tren nasional {rounded(current_average - previous_average):+g} poin",
        "description": f"Rata-rata capaian terhadap target berubah dari {previous_average}% menjadi {current_average}%.",
        "action": "Pertahankan praktik satker yang membaik dan replikasi ke satker tertinggal.",
    })

    return {
        "engine": {
            "name": "AKIP Python BI",
            "version": "2.0",
            "generated_at": datetime.now(timezone.utc).isoformat(),
            "method": "SS/IKSS achievement-to-target scoring, trend analysis, and regional z-score anomaly detection",
        },
        "summary": {
            "average_achievement": current_average,
            "yearly_change": rounded(current_average - previous_average),
            "high_risk_satkers": risk_counts["Tinggi"],
            "improving_satkers": improving,
            "declining_satkers": declining,
            "regions_attention": sum(row["achievement"] < 90 for row in region_performance),
            "ikss_below_target": sum((row.get("average_achievement") or 0) < 100 for row in ikss_rows),
            "ss_attention": sum((row.get("average_achievement") or 0) < 100 for row in strategic_objectives),
        },
        "trend": national_trend,
        "risk_distribution": [
            {"level": level, "count": risk_counts[level]}
            for level in ("Tinggi", "Sedang", "Rendah")
        ],
        "priority_satkers": priorities[:25],
        "region_performance": region_performance,
        "ikss_opportunities": ikss_opportunities,
        "strategic_objectives": objective_performance,
        "anomalies": anomalies,
        "insights": insights,
    }


if __name__ == "__main__":
    try:
        print(json.dumps(analyze(json.load(sys.stdin)), ensure_ascii=False, allow_nan=False))
    except Exception as exc:
        print(json.dumps({"error": str(exc)}), file=sys.stderr)
        raise
