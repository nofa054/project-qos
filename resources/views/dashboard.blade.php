@extends('layouts.app')

@section('content')

<style>
    /* ===== FONTS ===== */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    .dashboard-wrap * { font-family: 'Inter', sans-serif; box-sizing: border-box; }

    /* ===== LAYOUT ===== */
    .dashboard-wrap { color: #e2e8f0; }

    .dash-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .dash-header h1 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #f8fafc;
        margin: 0;
    }
    .dash-header p { margin: 2px 0 0; font-size: 0.75rem; color: #94a3b8; }

    .live-badge {
        display: flex;
        align-items: center;
        gap: 7px;
        background: rgba(34,197,94,0.12);
        border: 1px solid rgba(34,197,94,0.3);
        border-radius: 999px;
        padding: 5px 14px;
        font-size: 0.8rem;
        color: #4ade80;
        font-weight: 600;
    }
    .live-dot {
        width: 8px; height: 8px;
        background: #4ade80;
        border-radius: 50%;
        animation: pulse-dot 1.4s ease-in-out infinite;
    }
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(0.7); }
    }

    /* ===== METRIC CARDS ===== */
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 12px;
    }
    .metric-card {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        border: 1px solid #334155;
        border-radius: 12px;
        padding: 14px;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .metric-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.4); }
    .metric-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        border-radius: 16px 16px 0 0;
    }
    .card-throughput::before  { background: linear-gradient(90deg, #3b82f6, #06b6d4); }
    .card-delay::before       { background: linear-gradient(90deg, #f59e0b, #f97316); }
    .card-jitter::before      { background: linear-gradient(90deg, #a855f7, #ec4899); }
    .card-packetloss::before  { background: linear-gradient(90deg, #ef4444, #f43f5e); }

    .card-icon {
        width: 32px; height: 32px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
        margin-bottom: 8px;
    }
    .card-throughput .card-icon  { background: rgba(59,130,246,0.15); }
    .card-delay .card-icon       { background: rgba(245,158,11,0.15); }
    .card-jitter .card-icon      { background: rgba(168,85,247,0.15); }
    .card-packetloss .card-icon  { background: rgba(239,68,68,0.15); }

    .card-label { font-size: 0.72rem; color: #94a3b8; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; }
    .card-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #f8fafc;
        line-height: 1.1;
        margin: 4px 0 2px;
        transition: all 0.4s ease;
    }
    .card-unit { font-size: 0.78rem; color: #64748b; }
    .card-trend {
        margin-top: 10px;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 999px;
        display: inline-block;
    }
    .trend-good  { background: rgba(34,197,94,0.15); color: #4ade80; }
    .trend-warn  { background: rgba(245,158,11,0.15); color: #fbbf24; }
    .trend-bad   { background: rgba(239,68,68,0.15);  color: #f87171; }

    /* ===== CHARTS ROW ===== */
    .charts-grid {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 12px;
        margin-bottom: 8px;
    }
    .chart-card {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        border: 1px solid #334155;
        border-radius: 12px;
        padding: 14px;
        display: flex; flex-direction: column;
    }
    .chart-card h3 {
        font-size: 0.85rem;
        font-weight: 600;
        color: #cbd5e1;
        margin: 0 0 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .chart-card h3 span.dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #3b82f6;
        display: inline-block;
    }

    /* ===== QUALITY PANEL ===== */
    .quality-panel { text-align: center; }
    .donut-wrap { position: relative; width: 130px; height: 130px; margin: 0 auto 12px; }
    .donut-center {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }
    .donut-score { font-size: 1.6rem; font-weight: 700; color: #f8fafc; }
    .donut-max   { font-size: 0.75rem; color: #64748b; }

    .quality-badge {
        display: inline-block;
        padding: 4px 16px;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 700;
        margin-bottom: 8px;
        transition: all 0.4s ease;
    }
    .q-sangat-baik { background: rgba(34,197,94,0.15);  color: #4ade80;  border: 1px solid rgba(34,197,94,0.4); }
    .q-baik        { background: rgba(59,130,246,0.15);  color: #60a5fa;  border: 1px solid rgba(59,130,246,0.4); }
    .q-sedang      { background: rgba(245,158,11,0.15);  color: #fbbf24;  border: 1px solid rgba(245,158,11,0.4); }
    .q-buruk       { background: rgba(239,68,68,0.15);   color: #f87171;  border: 1px solid rgba(239,68,68,0.4); }

    .quality-label { font-size: 0.72rem; color: #64748b; margin-bottom: 2px; }

    /* ===== PREDIKSI PANEL ===== */
    .prediksi-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        text-align: left;
    }
    .pred-item {
        background: rgba(255,255,255,0.03);
        border: 1px solid #1e3a5f;
        border-radius: 8px;
        padding: 6px 10px;
    }
    .pred-item .pred-label { font-size: 0.65rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
    .pred-item .pred-val   { font-size: 0.85rem; font-weight: 600; color: #93c5fd; }

    /* ===== STATUS BAR ===== */
    .status-bar {
        text-align: center;
        font-size: 0.75rem;
        color: #475569;
        margin-top: 4px;
    }
    #last-update { color: #4ade80; font-weight: 500; }

    /* ===== ERROR STATE ===== */
    #api-error {
        display: none;
        background: rgba(239,68,68,0.08);
        border: 1px solid rgba(239,68,68,0.3);
        border-radius: 10px;
        padding: 12px 18px;
        color: #f87171;
        font-size: 0.82rem;
        margin-bottom: 16px;
    }

    /* ===== HISTORY TABLE ===== */
    .history-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.8rem;
        text-align: left;
    }
    .history-table th, .history-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #334155;
    }
    .history-table th {
        color: #94a3b8;
        font-weight: 600;
        background: rgba(255,255,255,0.02);
    }
    .history-table td {
        color: #e2e8f0;
    }
    .history-table tr:hover td {
        background: rgba(255,255,255,0.03);
    }
</style>

<div class="dashboard-wrap">

    {{-- HEADER --}}
    <div class="dash-header">
        <div>
            <h1>📡 Dashboard Monitoring QoS</h1>
            <p>Network Quality of Service — Real-time Monitoring</p>
        </div>
        <div class="live-badge">
            <div class="live-dot"></div>
            LIVE
        </div>
    </div>

    {{-- ERROR --}}
    <div id="api-error">
        ⚠️ Tidak bisa terhubung ke API Python (<code>predict_api.py</code>). Pastikan server Python sudah berjalan.
    </div>

    {{-- METRIC CARDS --}}
    <div class="cards-grid">
        <div class="metric-card card-throughput">
            <div class="card-icon">⚡</div>
            <div class="card-label">Throughput</div>
            <div class="card-value" id="val-throughput">—</div>
            <div class="card-unit">Mbps</div>
            <div class="card-trend" id="trend-throughput">—</div>
        </div>
        <div class="metric-card card-delay">
            <div class="card-icon">⏱️</div>
            <div class="card-label">Delay</div>
            <div class="card-value" id="val-delay">—</div>
            <div class="card-unit">ms</div>
            <div class="card-trend" id="trend-delay">—</div>
        </div>
        <div class="metric-card card-jitter">
            <div class="card-icon">〰️</div>
            <div class="card-label">Jitter</div>
            <div class="card-value" id="val-jitter">—</div>
            <div class="card-unit">ms</div>
            <div class="card-trend" id="trend-jitter">—</div>
        </div>
        <div class="metric-card card-packetloss">
            <div class="card-icon">📉</div>
            <div class="card-label">Packet Loss</div>
            <div class="card-value" id="val-packetloss">—</div>
            <div class="card-unit">%</div>
            <div class="card-trend" id="trend-packetloss">—</div>
        </div>
    </div>

    {{-- CHARTS --}}
    <div class="charts-grid">

        {{-- LINE CHART --}}
        <div class="chart-card" style="position:relative; flex:1;">
            <h3><span class="dot"></span> Grafik QoS Realtime</h3>
            <div style="flex:1; position:relative; min-height: 200px;">
                <canvas id="qosChart"></canvas>
            </div>
        </div>

        {{-- RIGHT COLUMN (ACTUAL & PREDICTION PANELS) --}}
        <div style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
            
            {{-- ACTUAL SCORE --}}
            <div class="chart-card quality-panel">
                <h3 style="justify-content:center;"><span class="dot" style="background:#a855f7;"></span> Kualitas Aktual</h3>
                <div class="quality-label">Skor (TIPHON)</div>
                <div class="donut-wrap" style="margin-bottom: 6px;">
                    <canvas id="donutChart"></canvas>
                    <div class="donut-center">
                        <div class="donut-score" id="skor-aktual">—</div>
                        <div class="donut-max">/ 16</div>
                    </div>
                </div>
                <div class="quality-badge q-sangat-baik" id="badge-aktual">Memuat...</div>
            </div>

            {{-- PREDICTION PARAMETERS --}}
            <div class="chart-card quality-panel">
                <h3 style="justify-content:center;"><span class="dot" style="background:#f59e0b;"></span> Prediksi (1 Jam Kedepan)</h3>
                <div class="quality-badge q-baik" id="badge-prediksi" style="margin-bottom: 12px;">—</div>
                
                <div class="prediksi-row">
                    <div class="pred-item"><div class="pred-label">Throughput</div><div class="pred-val" id="pred-throughput">—</div></div>
                    <div class="pred-item"><div class="pred-label">Delay</div><div class="pred-val" id="pred-delay">—</div></div>
                    <div class="pred-item"><div class="pred-label">Jitter</div><div class="pred-val" id="pred-jitter">—</div></div>
                    <div class="pred-item"><div class="pred-label">Packet Loss</div><div class="pred-val" id="pred-packetloss">—</div></div>
                </div>
            </div>

            {{-- REKAP HARI INI --}}
            <div class="chart-card quality-panel">
                <div style="display: flex; justify-content: center; align-items: center; position: relative;">
                    <h3 style="margin: 0;"><span class="dot" style="background:#10b981;"></span> Analisis Hari Ini</h3>
                    <button onclick="resetAnalisis()" style="position: absolute; right: 0; background: none; border: 1px solid #334155; color: #94a3b8; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; cursor: pointer; transition: 0.2s;">Reset</button>
                </div>
                <div class="prediksi-row" style="grid-template-columns: repeat(4, 1fr); margin-top: 8px;">
                    <div class="pred-item" style="border-color: rgba(34,197,94,0.4);"><div class="pred-label" style="color:#4ade80;">Sangat Bagus</div><div class="pred-val" id="count-sangat-baik" style="color:#f8fafc; font-size:1.1rem;">0</div></div>
                    <div class="pred-item" style="border-color: rgba(59,130,246,0.4);"><div class="pred-label" style="color:#60a5fa;">Bagus</div><div class="pred-val" id="count-baik" style="color:#f8fafc; font-size:1.1rem;">0</div></div>
                    <div class="pred-item" style="border-color: rgba(245,158,11,0.4);"><div class="pred-label" style="color:#fbbf24;">Sedang</div><div class="pred-val" id="count-sedang" style="color:#f8fafc; font-size:1.1rem;">0</div></div>
                    <div class="pred-item" style="border-color: rgba(239,68,68,0.4);"><div class="pred-label" style="color:#f87171;">Buruk</div><div class="pred-val" id="count-buruk" style="color:#f8fafc; font-size:1.1rem;">0</div></div>
                </div>
            </div>

        </div>
    </div>

    {{-- BOTTOM ROW (TABLE RIWAYAT) --}}
    <div style="margin-bottom: 8px;">
        {{-- TABLE RIWAYAT --}}
        <div class="chart-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 style="margin: 0;"><span class="dot" style="background:#3b82f6;"></span> Riwayat QoS</h3>
                <button onclick="exportCSV()" style="background: #3b82f6; color: white; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: bold;">📥 Unduh Data CSV</button>
            </div>
            <div style="flex:1; overflow-y:auto; max-height: 230px;">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Throughput</th>
                            <th>Delay</th>
                            <th>Jitter</th>
                            <th>Packet Loss</th>
                            <th>Kategori</th>
                        </tr>
                    </thead>
                    <tbody id="tableData">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- STATUS --}}
    <div class="status-bar">
        Diperbarui otomatis tiap 5 detik &nbsp;|&nbsp; Terakhir update: <span id="last-update">—</span>
    </div>

</div>

{{-- ==================== SCRIPTS ==================== --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ===== CONFIG =====
const API_URL   = 'http://127.0.0.1:5000/monitor';
const INTERVAL  = 5000;  // ms
const MAX_POINTS = 20;   // titik data maksimum di grafik

// ===== STATE =====
let prev = { throughput: null, delay: null, jitter: null, packet_loss: null };
const history = {
    labels:      [],
    throughput:  [],
    delay:       [],
    jitter:      [],
    packet_loss: [],
};
const allHistory = []; // Untuk export CSV, menyimpan semua riwayat tanpa dihapus

// Daily Stats State (Persisted in localStorage)
let todayStats = { sangat_baik: 0, baik: 0, sedang: 0, buruk: 0 };
const savedStats = localStorage.getItem('qosTodayStats');
const savedDate = localStorage.getItem('qosStatsDate');
const todayDateStr = new Date().toDateString();

if (savedStats && savedDate === todayDateStr) {
    let parsedStats = JSON.parse(savedStats);
    if (parsedStats.sangat_baik !== undefined) {
        todayStats = parsedStats;
    } else {
        // Migration from old stats format
        todayStats.baik = parsedStats.baik || 0;
        todayStats.sedang = parsedStats.sedang || 0;
        todayStats.buruk = parsedStats.buruk || 0;
    }
} else {
    // Reset if it's a new day
    localStorage.setItem('qosStatsDate', todayDateStr);
    localStorage.setItem('qosTodayStats', JSON.stringify(todayStats));
}
// Init UI values
document.addEventListener("DOMContentLoaded", () => {
    document.getElementById('count-sangat-baik').textContent = todayStats.sangat_baik;
    document.getElementById('count-baik').textContent = todayStats.baik;
    document.getElementById('count-sedang').textContent = todayStats.sedang;
    document.getElementById('count-buruk').textContent = todayStats.buruk;
});

// ===== LINE CHART =====
const ctxLine = document.getElementById('qosChart').getContext('2d');
const lineChart = new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: history.labels,
        datasets: [
            {
                label: 'Throughput (Mbps)',
                data: history.throughput,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.08)',
                fill: true, tension: 0.4, pointRadius: 2, yAxisID: 'y1'
            },
            {
                label: 'Delay (ms)',
                data: history.delay,
                borderColor: '#f59e0b',
                backgroundColor: 'transparent',
                fill: false, tension: 0.4, pointRadius: 2, yAxisID: 'y2'
            },
            {
                label: 'Jitter (ms)',
                data: history.jitter,
                borderColor: '#a855f7',
                backgroundColor: 'transparent',
                fill: false, tension: 0.4, pointRadius: 2, yAxisID: 'y2'
            },
            {
                label: 'Packet Loss (%)',
                data: history.packet_loss,
                borderColor: '#ef4444',
                backgroundColor: 'transparent',
                fill: false, tension: 0.4, pointRadius: 2, yAxisID: 'y3'
            },
        ]
    },
    options: {
        animation: { duration: 400 },
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: '#94a3b8', font: { size: 11 }, boxWidth: 12 }
            }
        },
        scales: {
            x:  { ticks: { color: '#64748b', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.04)' } },
            y1: {
                type: 'linear', position: 'left',
                ticks: { color: '#3b82f6', font: { size: 10 } },
                grid:  { color: 'rgba(255,255,255,0.04)' },
                title: { display: true, text: 'Mbps', color: '#3b82f6', font: { size: 10 } }
            },
            y2: {
                type: 'linear', position: 'right',
                ticks: { color: '#f59e0b', font: { size: 10 } },
                grid: { drawOnChartArea: false },
                title: { display: true, text: 'ms', color: '#f59e0b', font: { size: 10 } }
            },
            y3: {
                type: 'linear', position: 'right',
                ticks: { color: '#ef4444', font: { size: 10 } },
                grid: { drawOnChartArea: false },
                title: { display: true, text: '%', color: '#ef4444', font: { size: 10 } }
            },
        }
    }
});

// ===== DONUT CHART =====
const ctxDonut = document.getElementById('donutChart');
if(ctxDonut) {
    window.donutChart = new Chart(ctxDonut.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Skor', 'Sisa'],
            datasets: [{
                data: [0, 16],
                backgroundColor: ['#4ade80', '#1e293b'],
                borderWidth: 0,
                cutout: '78%'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            animation: { duration: 600 }
        }
    });
}

// ===== HELPERS =====
let baseDate = new Date();
let currentHour = baseDate.getHours(); // Mulai simulasi dari jam asli saat ini
let currentMin  = baseDate.getMinutes().toString().padStart(2,'0');
let currentSec  = baseDate.getSeconds().toString().padStart(2,'0');

function nowTime() {
    return currentHour.toString().padStart(2,'0') + ':' + currentMin + ':' + currentSec;
}
function advanceTime() {
    currentHour++;
    if(currentHour > 23) currentHour = 0;
}

function trendClass(val, prevVal, higherIsBetter) {
    if (prevVal === null) return '';
    const better = higherIsBetter ? val > prevVal : val < prevVal;
    return better ? 'trend-good' : val === prevVal ? '' : 'trend-bad';
}

function trendText(val, prevVal, higherIsBetter) {
    if (prevVal === null) return '—';
    const diff = (val - prevVal).toFixed(2);
    const sign = diff > 0 ? '▲' : diff < 0 ? '▼' : '─';
    const absDiff = Math.abs(diff);
    return `${sign} ${absDiff}`;
}

function categoryClass(kategori) {
    const map = {
        'Sangat Bagus': 'q-sangat-baik',
        'Bagus':        'q-baik',
        'Sedang':      'q-sedang',
        'Buruk':       'q-buruk',
    };
    return map[kategori] || 'q-baik';
}

function donutColor(kategori) {
    return { 'Sangat Bagus':'#4ade80', 'Bagus':'#60a5fa', 'Sedang':'#fbbf24', 'Buruk':'#f87171' }[kategori] || '#60a5fa';
}

// ===== PUSH TO CHART =====
function pushHistory(data, t) {
    history.labels.push(t);
    history.throughput.push(data.throughput);
    history.delay.push(data.delay);
    history.jitter.push(data.jitter);
    history.packet_loss.push(data.packet_loss);

    if (history.labels.length > MAX_POINTS) {
        history.labels.shift();
        history.throughput.shift();
        history.delay.shift();
        history.jitter.shift();
        history.packet_loss.shift();
    }
    lineChart.update();
}

// ===== UPDATE UI =====
function updateUI(json) {
    const a = json.aktual;
    const p = json.prediksi;

    // --- Cards ---
    document.getElementById('val-throughput').textContent  = a.throughput;
    document.getElementById('val-delay').textContent       = a.delay;
    document.getElementById('val-jitter').textContent      = a.jitter;
    document.getElementById('val-packetloss').textContent  = a.packet_loss;

    // Trend badges
    const tT = document.getElementById('trend-throughput');
    tT.textContent = trendText(a.throughput,  prev.throughput,  true);
    tT.className   = 'card-trend ' + trendClass(a.throughput,  prev.throughput,  true);

    const tD = document.getElementById('trend-delay');
    tD.textContent = trendText(a.delay,       prev.delay,       false);
    tD.className   = 'card-trend ' + trendClass(a.delay,       prev.delay,       false);

    const tJ = document.getElementById('trend-jitter');
    tJ.textContent = trendText(a.jitter,      prev.jitter,      false);
    tJ.className   = 'card-trend ' + trendClass(a.jitter,      prev.jitter,      false);

    const tP = document.getElementById('trend-packetloss');
    tP.textContent = trendText(a.packet_loss, prev.packet_loss, false);
    tP.className   = 'card-trend ' + trendClass(a.packet_loss, prev.packet_loss, false);

    // Simpan prev
    prev = { throughput: a.throughput, delay: a.delay, jitter: a.jitter, packet_loss: a.packet_loss };

    // --- Donut & Score ---
    const skor = a.skor;
    document.getElementById('skor-aktual').textContent = skor;
    if(window.donutChart) {
        window.donutChart.data.datasets[0].data = [skor, 16 - skor];
        window.donutChart.data.datasets[0].backgroundColor[0] = donutColor(a.kategori);
        window.donutChart.update();
    }

    const badge = document.getElementById('badge-aktual');
    badge.textContent = a.kategori;
    badge.className   = 'quality-badge ' + categoryClass(a.kategori);

    // --- Prediksi ---
    const badgePred = document.getElementById('badge-prediksi');
    badgePred.textContent     = `${p.kategori} (${p.skor}/16)`;
    badgePred.className       = 'quality-badge ' + categoryClass(p.kategori);

    document.getElementById('pred-throughput').textContent    = p.throughput + ' Mbps';
    document.getElementById('pred-delay').textContent         = p.delay + ' ms';
    document.getElementById('pred-jitter').textContent        = p.jitter + ' ms';
    document.getElementById('pred-packetloss').textContent    = p.packet_loss + ' %';

    // --- Tabel History ---
    const t = nowTime();
    const rowPrediksi = `<tr>
        <td>${t}</td>
        <td>${p.throughput}</td>
        <td>${p.delay}</td>
        <td>${p.jitter}</td>
        <td>${p.packet_loss}</td>
        <td><span class="quality-badge ${categoryClass(p.kategori)}" style="margin:0;padding:2px 8px;font-size:0.7rem;">${p.kategori}</span></td>
    </tr>`;
    
    const tbody = document.getElementById('tableData');
    tbody.insertAdjacentHTML('afterbegin', rowPrediksi);
    
    while (tbody.children.length > 20) { // 20 baris prediksi
        tbody.removeChild(tbody.lastElementChild);
    }

    // --- Simpan ke allHistory untuk CSV (Tanpa dihapus) ---
    allHistory.push({
        waktu: t,
        throughput_aktual: a.throughput,
        delay_aktual: a.delay,
        jitter_aktual: a.jitter,
        packet_loss_aktual: a.packet_loss,
        kategori_aktual: a.kategori,
        throughput_prediksi: p.throughput,
        delay_prediksi: p.delay,
        jitter_prediksi: p.jitter,
        packet_loss_prediksi: p.packet_loss,
        kategori_prediksi: p.kategori
    });

    // --- Chart ---
    pushHistory(a, t);

    // --- Timestamp ---
    document.getElementById('last-update').textContent = t;
    document.getElementById('api-error').style.display = 'none';

    // --- Update Analisis Hari Ini ---
    const catLow = a.kategori.toLowerCase();
    if (catLow === 'sangat bagus') {
        todayStats.sangat_baik++;
    } else if (catLow === 'bagus') {
        todayStats.baik++;
    } else if (catLow === 'sedang') {
        todayStats.sedang++;
    } else if (catLow === 'buruk') {
        todayStats.buruk++;
    }
    
    // Save & Update UI
    localStorage.setItem('qosTodayStats', JSON.stringify(todayStats));
    updateAnalisisUI();

    // Majukan waktu 1 jam untuk siklus berikutnya
    advanceTime();
}

// ===== RESET UI =====
function updateAnalisisUI() {
    document.getElementById('count-sangat-baik').textContent = todayStats.sangat_baik;
    document.getElementById('count-baik').textContent = todayStats.baik;
    document.getElementById('count-sedang').textContent = todayStats.sedang;
    document.getElementById('count-buruk').textContent = todayStats.buruk;
}

function resetAnalisis() {
    if(confirm('Apakah Anda yakin ingin mereset Analisis Hari Ini?')) {
        todayStats = { sangat_baik: 0, baik: 0, sedang: 0, buruk: 0 };
        localStorage.setItem('qosTodayStats', JSON.stringify(todayStats));
        updateAnalisisUI();
    }
}

// ===== EXPORT CSV =====
function exportCSV() {
    if (allHistory.length === 0) {
        alert("Belum ada data riwayat untuk diunduh!");
        return;
    }
    
    let csv = "Waktu,Throughput(Mbps),Delay(ms),Jitter(ms),Packet Loss(%),Kategori\n";
    allHistory.forEach(row => {
        csv += `${row.waktu},${row.throughput_prediksi},${row.delay_prediksi},${row.jitter_prediksi},${row.packet_loss_prediksi},${row.kategori_prediksi}\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('href', url);
    a.setAttribute('download', 'Riwayat_QoS_Prediksi.csv');
    a.click();
}

// ===== FETCH =====
async function fetchMonitor() {
    try {
        const res = await fetch(API_URL, { cache: 'no-store' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const json = await res.json();
        if (json.status === 'success') updateUI(json);
    } catch (e) {
        document.getElementById('api-error').style.display = 'block';
        document.getElementById('last-update').textContent = 'Error — ' + e.message;
    }
}

// ===== START =====
fetchMonitor();
setInterval(fetchMonitor, INTERVAL);
</script>

@endsection