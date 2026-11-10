from flask import Flask, request, jsonify
from flask_cors import CORS
import numpy as np
import os
import random
import time
try:
    from tensorflow.keras.models import load_model
    import joblib
    HAS_TF = True
except ImportError:
    HAS_TF = False

app = Flask(__name__)
CORS(app)

# Direktori model disimpan
MODEL_DIR = os.path.dirname(os.path.abspath(__file__))

# Cache model agar tidak diload berulang kali
models = {}
scalers = {}

# State untuk simulasi realtime monitor (agar data berubah secara halus, bukan random brutal)
_monitor_state = {
    "throughput": 1.5,
    "delay": 320.0,
    "jitter": 85.0,
    "packet_loss": 16.0,
    "bandwidth": 10.0
}

def load_ml_assets():
    if not HAS_TF:
        return
    try:
        # Load scaler X dan Y
        if os.path.exists(os.path.join(MODEL_DIR, "scaler_X.pkl")):
            scalers['X'] = joblib.load(os.path.join(MODEL_DIR, "scaler_X.pkl"))
            scalers['y'] = joblib.load(os.path.join(MODEL_DIR, "scaler_y.pkl"))
        
        # Load model berdasarkan rentang
        model_paths = {
            "5_detik": "model_5_detik.h5",
            "1_jam": "model_1_jam.h5",
            "1_hari": "model_1_hari.h5"
        }
        for rentang, file_name in model_paths.items():
            path = os.path.join(MODEL_DIR, file_name)
            if os.path.exists(path):
                models[rentang] = load_model(path)
                print(f"Loaded {rentang} model.")
    except Exception as e:
        print(f"Error loading models: {e}")

load_ml_assets()

def _hitung_skor_kualitas(throughput, delay, jitter, packet_loss, bandwidth=10.0):
    """Hitung skor dan kategori kualitas berdasarkan standar TIPHON yang direvisi (disamakan dengan LSTM.ipynb)."""
    # 1. Throughput (% BW)
    util_pct = (throughput / bandwidth) * 100 if bandwidth > 0 else 0
    if util_pct >= 100:
        idx_t = 4
    elif util_pct >= 75:
        idx_t = 3
    elif util_pct >= 25:
        idx_t = 2
    else:
        idx_t = 1

    # 2. Delay (ms)
    if delay < 150:
        idx_d = 4
    elif delay < 300:
        idx_d = 3
    elif delay < 450:
        idx_d = 2
    else:
        idx_d = 1

    # 3. Jitter (ms)
    if jitter == 0:
        idx_j = 4
    elif jitter < 75:
        idx_j = 3
    elif jitter < 125:
        idx_j = 2
    else:
        idx_j = 1

    # 4. Packet Loss (%)
    if packet_loss == 0:
        idx_p = 4
    elif packet_loss < 15:
        idx_p = 3
    elif packet_loss < 25:
        idx_p = 2
    else:
        idx_p = 1

    # Hitung rata-rata indeks dan floor (sama dengan df.mean(axis=1).apply(np.floor))
    import math
    mean_idx = (idx_t + idx_d + idx_j + idx_p) / 4.0
    indeks_qos = int(math.floor(mean_idx))
    
    # Clip ke 1-4
    if indeks_qos < 1: indeks_qos = 1
    elif indeks_qos > 4: indeks_qos = 4

    # Map ke kategori
    if indeks_qos == 4:
        kategori = "Sangat Bagus"
    elif indeks_qos == 3:
        kategori = "Bagus"
    elif indeks_qos == 2:
        kategori = "Sedang"
    else:
        kategori = "Buruk"

    # Kita kembalikan nilai skor asli (sum) juga jika frontend butuh, atau indeks_qos
    skor_sum = idx_t + idx_d + idx_j + idx_p
    return skor_sum, kategori

import pandas as pd

# Global state untuk membaca dataset CSV
csv_path = r"d:\TA\dataset_qos_bersih_total (2).csv"
df_dataset = None
current_row_idx = 0

try:
    if os.path.exists(csv_path):
        df_dataset = pd.read_csv(csv_path)
        print(f"Berhasil memuat dataset aktual dari: {csv_path}")
except Exception as e:
    print(f"Gagal memuat dataset: {e}")

@app.route('/monitor', methods=['GET'])
def monitor():
    """
    Endpoint untuk dashboard realtime.
    Mengembalikan data QoS aktual dari dataset CSV yang dibaca baris per baris.
    """
    global _monitor_state, df_dataset, current_row_idx

    # Jika dataset berhasil di-load, ambil data dari baris per baris
    if df_dataset is not None and not df_dataset.empty:
        if current_row_idx >= len(df_dataset):
            current_row_idx = 0 # Loop kembali dari awal jika sudah habis
        
        row = df_dataset.iloc[current_row_idx]
        _monitor_state["throughput"] = float(row.get('throughput', _monitor_state["throughput"]))
        _monitor_state["delay"] = float(row.get('delay', _monitor_state["delay"]))
        _monitor_state["jitter"] = float(row.get('jitter', _monitor_state["jitter"]))
        _monitor_state["packet_loss"] = float(row.get('packet_loss', _monitor_state["packet_loss"]))
        _monitor_state["bandwidth"] = float(row.get('bandwidth', _monitor_state["bandwidth"]))
        
        # Maju ke baris berikutnya untuk request selanjutnya
        current_row_idx += 1
    else:
        # Fallback jika CSV tidak ada (menggunakan drift seperti sebelumnya)
        def drift(val, step, min_val, max_val):
            import random
            val += random.uniform(-step, step)
            return round(max(min_val, min(max_val, val)), 2)
            
        _monitor_state["throughput"]  = drift(_monitor_state["throughput"],  0.1, 0.5, 2.5)
        _monitor_state["delay"]       = drift(_monitor_state["delay"],        4.0, 200.0, 450.0)
        _monitor_state["jitter"]      = drift(_monitor_state["jitter"],       1.5, 50.0, 125.0)
        _monitor_state["packet_loss"] = drift(_monitor_state["packet_loss"],  0.5, 10.0, 24.0)

    t = round(_monitor_state["throughput"], 2)
    d = round(_monitor_state["delay"], 2)
    j = round(_monitor_state["jitter"], 2)
    p = round(_monitor_state["packet_loss"], 2)
    b = round(_monitor_state["bandwidth"], 2)

    # Hitung prediksi menggunakan model jika tersedia
    prediksi = None
    if "5_detik" in models and 'X' in scalers:
        try:
            if df_dataset is not None and not df_dataset.empty:
                start_idx = max(0, current_row_idx - 5)  # Ingat, saat dipanggil current_row_idx sudah +1, jadi ini mengambil baris [current-5 : current]
                # Pastikan current_row_idx valid, jika baru load current_row_idx = 1
                if current_row_idx < 5:
                    start_idx = 0
                df_seq = df_dataset.iloc[start_idx : current_row_idx]
                
                seq_features = []
                for _, r in df_seq.iterrows():
                    _t = float(r.get('throughput', t))
                    _d = float(r.get('delay', d))
                    _j = float(r.get('jitter', j))
                    _p = float(r.get('packet_loss', p))
                    _b = float(r.get('bandwidth', b))
                    _ut = (_t / _b) * 100 if _b > 0 else 0
                    _kat = float(r.get('kategori_sesi', 2))
                    seq_features.append([_t, _d, _j, _p, _ut, _kat])
                
                # Jika sequence kurang dari 5 (misal di baris 0-3), pad dengan mengkopi baris pertama
                while len(seq_features) < 5:
                    seq_features.insert(0, seq_features[0] if len(seq_features) > 0 else [t, d, j, p, (t/b)*100 if b>0 else 0, 2])
                
                input_features = np.array(seq_features)
                input_scaled = scalers['X'].transform(input_features)
                
                # === TEKNIK AUTOREGRESSIVE LOOPING (1 JAM) ===
                # Dosen meminta prediksi 1 jam, tapi model di-training untuk 5 detik.
                # Solusi ilmiah: Lakukan perulangan tebakan 720 kali beruntun (720 * 5 detik = 3600 detik = 1 jam)
                current_scaled_seq = input_scaled.copy()
                for step in range(720):
                    input_seq = np.expand_dims(current_scaled_seq, axis=0)
                    pred_scaled = models["5_detik"].predict(input_seq, verbose=0)
                    
                    # Kembalikan ke nilai asli sementara untuk menghitung fitur tambahan
                    pred_real_step = scalers['y'].inverse_transform(pred_scaled)[0]
                    p_t, p_d, p_j, p_pl = pred_real_step[0], pred_real_step[1], pred_real_step[2], pred_real_step[3]
                    
                    # Hitung 2 fitur sisanya secara dinamis
                    p_util = (p_t / b) * 100 if b > 0 else 0
                    p_kat = 2 # Kategori sesi konstan
                    
                    # Skalakan kembali 6 fitur lengkap
                    next_step_features = np.array([[p_t, p_d, p_j, p_pl, p_util, p_kat]])
                    next_step_scaled = scalers['X'].transform(next_step_features)[0]
                    
                    # Geser window sequence (buang masa lalu tertua, masukkan masa depan terbaru)
                    current_scaled_seq = np.vstack((current_scaled_seq[1:], next_step_scaled))

                # Hasil setelah melompat 720 kali
                pred_real = pred_real_step
                
            else:
                utilitas_bandwidth = (t / b) * 100 if b > 0 else 0
                kategori_sesi = 2
                input_features = np.array([[t, d, j, p, utilitas_bandwidth, kategori_sesi]])
                input_scaled = scalers['X'].transform(input_features)
                input_seq = np.expand_dims(np.array([input_scaled[0]] * 5), axis=0)
                pred_scaled = models["5_detik"].predict(input_seq, verbose=0)
                pred_real = scalers['y'].inverse_transform(pred_scaled)[0]
            
            prediksi = {
                "throughput": round(float(pred_real[0]), 2),
                "delay":      round(float(pred_real[1]), 2),
                "jitter":     round(float(pred_real[2]), 2),
                "packet_loss":round(float(pred_real[3]), 2),
                "is_mock": False
            }
        except Exception as e:
            print(f"Error prediksi: {e}")
            prediksi = None

    # Fallback mock prediksi jika model belum ada
    if prediksi is None:
        prediksi = {
            "throughput":  round(t * 1.05, 2),
            "delay":       round(d * 0.97, 2),
            "jitter":      round(j * 0.98, 2),
            "packet_loss": round(p * 1.02, 2),
            "is_mock": True
        }

    skor_aktual, kategori_aktual     = _hitung_skor_kualitas(t, d, j, p, b)
    skor_prediksi, kategori_prediksi = _hitung_skor_kualitas(
        prediksi["throughput"], prediksi["delay"],
        prediksi["jitter"], prediksi["packet_loss"], b
    )

    return jsonify({
        "status": "success",
        "timestamp": int(time.time()),
        "aktual": {
            "throughput":  t,
            "delay":       d,
            "jitter":      j,
            "packet_loss": p,
            "bandwidth":   b,
            "skor":        skor_aktual,
            "kategori":    kategori_aktual
        },
        "prediksi": {
            **prediksi,
            "skor":     skor_prediksi,
            "kategori": kategori_prediksi
        }
    })

@app.route('/predict', methods=['POST'])
def predict():
    # Terima dari JSON maupun form-data
    data = request.get_json(silent=True) or request.form

    rentang = data.get('rentang_waktu', '5_detik') or '5_detik'

    def safe_float(val, default=0.0):
        try:
            return float(val) if val not in (None, '', 'null') else default
        except (TypeError, ValueError):
            return default

    throughput   = safe_float(data.get('throughput'),   0.0)
    delay        = safe_float(data.get('delay'),         0.0)
    jitter       = safe_float(data.get('jitter'),        0.0)
    packet_loss  = safe_float(data.get('packet_loss'),   0.0)
    bandwidth    = safe_float(data.get('bandwidth'),    10.0)
    
    # Feature engineering sesuaikan dengan notebook
    utilitas_bandwidth = (throughput / bandwidth) * 100 if bandwidth > 0 else 0
    kategori_sesi = 2 # Default siang misalnya
    
    # Format input array fitur: [throughput, delay, jitter, packet_loss, utilitas_bandwidth, kategori_sesi]
    input_features = np.array([[throughput, delay, jitter, packet_loss, utilitas_bandwidth, kategori_sesi]])

    if rentang in models and 'X' in scalers:
        try:
            input_scaled = scalers['X'].transform(input_features)
            input_seq = np.array([input_scaled[0] for _ in range(5)])
            input_seq = np.expand_dims(input_seq, axis=0)
            model = models[rentang]
            pred_scaled = model.predict(input_seq)
            pred_real = scalers['y'].inverse_transform(pred_scaled)
            
            return jsonify({
                "status": "success",
                "rentang": rentang,
                "throughput": float(pred_real[0][0]),
                "delay": float(pred_real[0][1]),
                "jitter": float(pred_real[0][2]),
                "packet_loss": float(pred_real[0][3]),
                "is_mock": False
            })
        except Exception as e:
            return jsonify({"status": "error", "message": str(e)}), 500
    else:
        multiplier = 1
        if rentang == "1_jam": multiplier = 1.2
        elif rentang == "1_hari": multiplier = 1.5
        
        return jsonify({
            "status": "success",
            "message": "WARNING: Menggunakan data dummy karena model .h5 belum disimpan dari Jupyter Notebook",
            "rentang": rentang,
            "throughput": round(throughput * multiplier, 2),
            "delay": round(delay * multiplier, 2),
            "jitter": round(jitter * multiplier, 2),
            "packet_loss": round(packet_loss * multiplier, 2),
            "is_mock": True
        })

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5000, debug=True)
