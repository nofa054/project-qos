<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrediksiController extends Controller
{
    public function index()
    {
        return view('prediksi');
    }

    public function proses(Request $r)
    {
        $t = $r->throughput;
        $d = $r->delay;
        $j = $r->jitter;
        $b = $r->bandwidth;
        $p = $r->packet_loss;

        $rentang = $r->input('rentang_waktu', '5_detik');

        // Request ke Python API
        try {
            $response = \Illuminate\Support\Facades\Http::post('http://127.0.0.1:5000/predict', [
                'rentang_waktu' => $rentang,
                'throughput' => $t,
                'delay' => $d,
                'jitter' => $j,
                'bandwidth' => $b,
                'packet_loss' => $p
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Ambil hasil dari AI
                $t_pred = $data['throughput'];
                $d_pred = $data['delay'];
                $j_pred = $data['jitter'];
                $p_pred = $data['packet_loss'];

                // Hitung Skor (Tetap menggunakan aturan TIPHON untuk skornya)
                $skor = 0;
                
                // 1. Throughput (% BW)
                $utilitas = ($b > 0) ? ($t_pred / $b) * 100 : 0;
                if ($utilitas >= 75) $skor += 4;
                elseif ($utilitas >= 50) $skor += 3;
                elseif ($utilitas >= 25) $skor += 2;
                else $skor += 1;

                // 2. Delay (ms)
                if ($d_pred < 150) $skor += 4;
                elseif ($d_pred <= 300) $skor += 3;
                elseif ($d_pred <= 450) $skor += 2;
                else $skor += 1;

                // 3. Jitter (ms)
                if ($j_pred < 75) $skor += 4;
                elseif ($j_pred <= 125) $skor += 3;
                elseif ($j_pred <= 225) $skor += 2;
                else $skor += 1;

                // 4. Packet Loss (%)
                if ($p_pred < 3) $skor += 4;
                elseif ($p_pred <= 15) $skor += 3;
                elseif ($p_pred <= 25) $skor += 2;
                else $skor += 1;

                if ($skor >= 14) $hasil = "Sangat Baik";
                elseif ($skor >= 10) $hasil = "Baik";
                elseif ($skor >= 7) $hasil = "Sedang";
                else $hasil = "Buruk";

                // Teruskan semua data prediksi ke view
                return view('prediksi', compact('hasil', 'data', 'rentang'));
            } else {
                $hasil = "Error API Python: " . $response->status();
                return view('prediksi', compact('hasil'));
            }
        } catch (\Exception $e) {
            $hasil = "Koneksi ke API Python gagal. Pastikan predict_api.py sudah berjalan.";
            return view('prediksi', compact('hasil'));
        }
    }
}