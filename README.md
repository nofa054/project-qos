# Project QoS Monitoring & Prediksi LSTM

Aplikasi monitoring **Quality of Service (QoS)** jaringan Wi-Fi dan prediksi parameter QoS menggunakan model **Long Short-Term Memory (LSTM)** berbasis Laravel dan Python Flask API.

## Notebook Training Model (Kaggle)

Proses *preprocessing* dataset, *training*, dan evaluasi model LSTM dapat diakses langsung melalui Kaggle Notebook berikut:

- **Kaggle Notebook:** [https://www.kaggle.com/code/nofaamanda/lstmqos2](https://www.kaggle.com/code/nofaamanda/lstmqos2)

[![Open in Kaggle](https://kaggle.com/static/images/open-in-kaggle.svg)](https://www.kaggle.com/code/nofaamanda/lstmqos2)

## Struktur Proyek

- **`app/`, `resources/`, `routes/`** — Aplikasi web dashboard monitoring QoS (Laravel).
- **`api/`** — REST API prediksi QoS (`predict_api.py`), model LSTM (`model_5_detik.h5`), serta scaler (`scaler_X.pkl`, `scaler_y.pkl`).
- **`jalankan.bat`** — Script untuk menjalankan server Laravel dan Python API secara bersamaan.
