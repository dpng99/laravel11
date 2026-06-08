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


def risk_level(score, trend):
    if score < 45 or (score < 60 and trend < -10):
        return "Tinggi"
    if score < 75 or trend < -5:
        return "Sedang"
    return "Rendah"


def analyze(payload):
    years = payload["years"]
    current_year = payload["selected_year"]
    previous_year = years[-2] if len(years) > 1 else current_year
    satkers = payload.get("satkers", [])
    documents = payload.get("documents", [])
    priorities = []
    region_rows = defaultdict(list)
    national_trend = []

    for year in years:
        national_trend.append({
            "year": year,
            "completion": rounded(average([row["history"].get(year, 0) for row in satkers])),
        })

    for row in satkers:
        current = float(row["history"].get(current_year, 0))
        previous = float(row["history"].get(previous_year, 0))
        trend = current - previous
        history_values = [float(row["history"].get(year, 0)) for year in years]
        volatility = statistics.pstdev(history_values) if len(history_values) > 1 else 0
        risk = risk_level(current, trend)
        risk_score = min(100, max(0, (100 - current) * 0.75 + max(-trend, 0) * 1.5 + volatility * 0.5))
        result = {
            "id_satker": row["id_satker"],
            "satkernama": row["satkernama"],
            "kejati_name": row["kejati_name"],
            "completion": rounded(current),
            "trend": rounded(trend),
            "risk": risk,
            "risk_score": rounded(risk_score),
            "missing_count": len(row.get("missing_documents", [])),
            "missing_documents": row.get("missing_documents", [])[:5],
        }
        priorities.append(result)
        region_rows[row["kejati_name"]].append(result)

    priorities.sort(key=lambda item: (-item["risk_score"], item["completion"], item["satkernama"]))
    risk_counts = Counter(row["risk"] for row in priorities)
    region_performance = []

    for name, rows in region_rows.items():
        completion = average([row["completion"] for row in rows])
        trend = average([row["trend"] for row in rows])
        high_risk = sum(row["risk"] == "Tinggi" for row in rows)
        region_performance.append({
            "name": name,
            "satkers": len(rows),
            "completion": rounded(completion),
            "trend": rounded(trend),
            "high_risk": high_risk,
        })

    region_performance.sort(key=lambda item: (item["completion"], item["trend"]))
    region_values = [row["completion"] for row in region_performance]
    region_mean = average(region_values)
    region_std = statistics.pstdev(region_values) if len(region_values) > 1 else 0
    anomalies = [
        {
            **row,
            "deviation": rounded((row["completion"] - region_mean) / region_std) if region_std else 0,
        }
        for row in region_performance
        if region_std and abs((row["completion"] - region_mean) / region_std) >= 1.5
    ]

    document_opportunities = sorted(
        documents,
        key=lambda item: (item["coverage"], item["change"], item["label"]),
    )
    improving = sum(row["trend"] > 2 for row in priorities)
    declining = sum(row["trend"] < -2 for row in priorities)
    current_average = national_trend[-1]["completion"] if national_trend else 0
    previous_average = national_trend[-2]["completion"] if len(national_trend) > 1 else current_average
    insights = []

    if document_opportunities:
        weakest = document_opportunities[0]
        insights.append({
            "severity": "warning",
            "title": f"Fokus dokumen: {weakest['label']}",
            "description": f"Cakupan baru {weakest['coverage']}%, masih ada {weakest['missing_satkers']} satker belum lengkap.",
            "action": "Prioritaskan pengingat dan pendampingan untuk dokumen ini.",
        })
    if priorities:
        insights.append({
            "severity": "error" if risk_counts["Tinggi"] else "info",
            "title": f"{risk_counts['Tinggi']} satker berisiko tinggi",
            "description": "Risiko dihitung dari kelengkapan tahun berjalan, penurunan tahunan, dan volatilitas empat tahun.",
            "action": "Mulai tindak lanjut dari daftar prioritas dengan skor risiko tertinggi.",
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
        "description": f"Rata-rata kelengkapan berubah dari {previous_average}% menjadi {current_average}%.",
        "action": "Pertahankan praktik wilayah yang membaik dan replikasi ke wilayah tertinggal.",
    })

    return {
        "engine": {
            "name": "AKIP Python BI",
            "version": "1.0",
            "generated_at": datetime.now(timezone.utc).isoformat(),
            "method": "Risk scoring, trend analysis, and regional z-score anomaly detection",
        },
        "summary": {
            "average_completion": current_average,
            "yearly_change": rounded(current_average - previous_average),
            "high_risk_satkers": risk_counts["Tinggi"],
            "improving_satkers": improving,
            "declining_satkers": declining,
            "regions_attention": sum(row["completion"] < 60 for row in region_performance),
        },
        "trend": national_trend,
        "risk_distribution": [
            {"level": level, "count": risk_counts[level]}
            for level in ("Tinggi", "Sedang", "Rendah")
        ],
        "priority_satkers": priorities[:25],
        "region_performance": region_performance,
        "document_opportunities": document_opportunities,
        "anomalies": anomalies,
        "insights": insights,
    }


if __name__ == "__main__":
    try:
        print(json.dumps(analyze(json.load(sys.stdin)), ensure_ascii=False, allow_nan=False))
    except Exception as exc:
        print(json.dumps({"error": str(exc)}), file=sys.stderr)
        raise
