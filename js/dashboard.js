const ctx = document.getElementById('chartQoS');
const ctx2 = document.getElementById('chartPrediksiHariJam');

let labels = [];
let dataQoS = [];

// LINE CHART
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Throughput',
            data: dataQoS,
            borderWidth: 2,
            tension: 0.4,
            fill: true
        }]
    }
});

// PREDIKSI BAR CHART (PER HARI/JAM)
const chartPrediksi = new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'],
        datasets: [{
            label: 'Prediksi Throughput',
            data: [5.2, 6.1, 4.8, 7.3, 8.5, 6.9],
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

let score = 80;

// SIMULASI
function updateData() {
    function getStatusColor(value) {
        if (value < 60) return "text-danger";
        if (value < 75) return "text-warning";
        return "text-success";
    }

    // contoh di throughput
    let tpEl = document.getElementById("tp");
    let delay1 = document.getElementById("delay");
    let jitterE1 = document.getElementById("jitter");
    let pEl = document.getElementById("pl");
    
    score = Math.floor(Math.random() * 30) + 70; // random score for color
    
    tpEl.className = getStatusColor(score);
    delay1.className = getStatusColor(score);
    jitterE1.className = getStatusColor(score);
    pEl.className = getStatusColor(score);
    let now = new Date().toLocaleTimeString();

    let tp = (Math.random() * 5 + 4).toFixed(2);
    let delay = (Math.random() * 30).toFixed(1);
    let jitter = (Math.random() * 10).toFixed(1);
    let pl = (Math.random() * 3).toFixed(1);

    // update card
    document.getElementById("tp").innerText = tp + " Mbps";
    document.getElementById("delay").innerText = delay + " ms";
    document.getElementById("jitter").innerText = jitter + " ms";
    document.getElementById("pl").innerText = pl + " %";

    // chart
    labels.push(now);
    dataQoS.push(tp);

    if (labels.length > 10) {
        labels.shift();
        dataQoS.shift();
    }

    chart.update();

    // tabel
    let row = `
    <tr>
      <td>${now}</td>
      <td>${tp}</td>
      <td>${delay}</td>
      <td>${jitter}</td>
      <td>${pl}</td>
    </tr>
  `;

    let table = document.getElementById("tableData");
    table.innerHTML = row + table.innerHTML;

    if (table.rows.length > 5) {
        table.deleteRow(5);
    }
}

setInterval(updateData, 2000);