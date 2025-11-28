<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>ESP8266 Smart Fish Pond — Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background: #f4f6f9;
    }

    .card {
      border-radius: 1rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }

    .led {
      width: 14px;
      height: 14px;
      border-radius: 50%;
      display: inline-block;
      margin-left: 8px;
      vertical-align: middle;
    }

    .led.green {
      background: #27ae60;
    }

    .led.red {
      background: #e74c3c;
    }

    .gauge {
      height: 160px;
      width: 100%;
    }

    canvas {
      background: #fff;
      border-radius: 0.75rem;
    }
  </style>
</head>

<body>
  <div class="container py-4">
    <h2 class="text-center mb-4">🐟 Smart Fish Pond Dashboard</h2>

    <!-- Feeding Settings -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">⚙️ Feeding Settings</h5>
        <form id="frm" class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Feeding Time (max 20 mins)</label>
            <input type="number" id="feeding_time" name="feeding_time" class="form-control" min="1" max="20">
          </div>
          <div class="col-md-3">
            <label class="form-label">Feed 1 (HH:MM)</label>
            <div class="d-flex gap-1">
              <input type="number" id="f1h" name="feed1_hour" class="form-control" min="0" max="23">
              <span class="pt-2">:</span>
              <input type="number" id="f1m" name="feed1_minute" class="form-control" min="0" max="59">
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Feed 2 (HH:MM)</label>
            <div class="d-flex gap-1">
              <input type="number" id="f2h" name="feed2_hour" class="form-control" min="0" max="23">
              <span class="pt-2">:</span>
              <input type="number" id="f2m" name="feed2_minute" class="form-control" min="0" max="59">
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Feed 3 (HH:MM)</label>
            <div class="d-flex gap-1">
              <input type="number" id="f3h" name="feed3_hour" class="form-control" min="0" max="23">
              <span class="pt-2">:</span>
              <input type="number" id="f3m" name="feed3_minute" class="form-control" min="0" max="59">
            </div>
          </div>

          <div class="col-12 mt-3">
            <button type="submit" class="btn btn-primary">
              💾 Save Settings
            </button>
            <span id="saveStatus" class="ms-2 text-muted"></span>
          </div>
        </form>
      </div>
    </div>

    <!-- Live Indicators -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">📊 Live Indicators</h5>
        <div class="row g-4">
          <div class="col-md-4 text-center">
            <div>🌡️ Temperature <span id="tempLed" class="led"></span></div>
            <canvas id="gaugeTemp" class="gauge mt-2"></canvas>
          </div>
          <div class="col-md-4 text-center">
            <div>💧 Turbidity <span id="turbLed" class="led"></span></div>
            <canvas id="gaugeTurb" class="gauge mt-2"></canvas>
          </div>
          <div class="col-md-4 text-center">
            <div>⚗️ pH Level <span id="phLed" class="led"></span></div>
            <canvas id="gaugePH" class="gauge mt-2"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- History -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">📈 History</h5>
        <div class="row g-3">
          <div class="col-md-4">
            <canvas id="chartTemp" height="200"></canvas>
          </div>
          <div class="col-md-4">
            <canvas id="chartTurb" height="200"></canvas>
          </div>
          <div class="col-md-4">
            <canvas id="chartPH" height="200"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
  <script>
    // ---- existing JS code (same as your version) ----
    async function loadSettings() {
      const r = await fetch('settings.php');
      const j = await r.json();
      document.getElementById('feeding_time').value = j.feeding_time;
      document.getElementById('f1h').value = j.feeds[0].hour;
      document.getElementById('f1m').value = j.feeds[0].minute;
      document.getElementById('f2h').value = j.feeds[1].hour;
      document.getElementById('f2m').value = j.feeds[1].minute;
      document.getElementById('f3h').value = j.feeds[2].hour;
      document.getElementById('f3m').value = j.feeds[2].minute;
    }

    document.getElementById('frm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const form = new FormData(this);
      const v = parseInt(form.get('feeding_time'));
      if (isNaN(v) || v < 0 || v > 20) {
        alert('feeding_time must be 0-20');
        return;
      }
      const resp = await fetch('update_settings.php', {
        method: 'POST',
        body: form
      });
      const j = await resp.json();
      document.getElementById('saveStatus').innerText = j.ok ? 'Saved ✅' : ('Error: ' + (j.msg || JSON.stringify(j.errors)));
    });

    function makeGauge(ctx, maxValue, unit = '') {
      const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          datasets: [{
            data: [0, maxValue], // 0 filled, rest empty
            backgroundColor: ['#3498db', '#e0e0e0'],
            borderWidth: 0,
            cutout: '70%'
          }]
        },
        options: {
          circumference: 180,
          rotation: 270,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              enabled: false
            },
            datalabels: {
              display: true,
              color: '#333',
              formatter: (value, ctx2) => {
                if (ctx2.dataIndex === 0) {
                  const val = ctx2.chart.data.datasets[0].data[0];
                  return val + unit;
                }
                return '';
              },
              font: {
                weight: 'bold',
                size: 16
              },
              align: 'center',
              anchor: 'center'
            }
          }
        },
        plugins: [ChartDataLabels]
      });
      chart.maxValue = maxValue;
      chart.unit = unit;
      return chart;
    }

    const gaugeTemp = makeGauge(document.getElementById('gaugeTemp'), 50, '°C');
    const gaugeTurb = makeGauge(document.getElementById('gaugeTurb'), 100, '%');
    const gaugePH = makeGauge(document.getElementById('gaugePH'), 14, '');

    function makeLine(ctx, label) {
      return new Chart(ctx, {
        type: 'line',
        data: {
          labels: [],
          datasets: [{
            label,
            data: [],
            fill: false,
            tension: 0.2
          }]
        },
        options: {
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            x: {
              display: false
            }
          }
        }
      });
    }

    const chartTemp = makeLine(document.getElementById('chartTemp'), 'Temperature');
    const chartTurb = makeLine(document.getElementById('chartTurb'), 'Turbidity');
    const chartPH = makeLine(document.getElementById('chartPH'), 'pH');

    async function loadHistory() {
      const r = await fetch('fetch_thingspeak.php?results=100');
      const j = await r.json();
      const feeds = j.feeds || [];
      const temps = [],
        turbs = [],
        phs = [],
        labels = [];
      feeds.forEach(f => {
        labels.push(f.created_at.replace('T', ' ').replace('Z', ''));
        temps.push(f.field1 ? parseFloat(f.field1) : null);
        phs.push(f.field2 ? parseFloat(f.field2) : null);
        turbs.push(f.field4 ? parseInt(f.field4) : null);
      });
      chartTemp.data.labels = labels;
      chartTemp.data.datasets[0].data = temps;
      chartTemp.update();
      chartTurb.data.labels = labels;
      chartTurb.data.datasets[0].data = turbs;
      chartTurb.update();
      chartPH.data.labels = labels;
      chartPH.data.datasets[0].data = phs;
      chartPH.update();

      const last = feeds[feeds.length - 1];
      if (!last) return;
      const temp = last.field1 ? parseFloat(last.field1) : null;
      const ph = last.field2 ? parseFloat(last.field2) : null;
      const turb = last.field4 ? parseFloat(last.field4) : null;

      updateGaugeAndLED(gaugeTemp, temp, 'tempLed', 26, 30, '°C');
      updateGaugeAndLED(gaugePH, ph, 'phLed', 6.5, 8.5, '');
      updateGaugeAndLED(gaugeTurb, turb, 'turbLed', -999, 60, '%', true);
    }

    function updateGaugeAndLED(chart, value, ledId, normalMin, normalMax, unit = '', isLowerBetter = false) {
      const val = (value === null || isNaN(value)) ? 0 : value;

      chart.data.datasets[0].data = [val, chart.maxValue - val];
      chart.update();

      const led = document.getElementById(ledId);
      let abnormal = false;
      if (value === null || isNaN(value)) abnormal = true;
      else if (isLowerBetter) abnormal = (value > normalMax);
      else abnormal = (value < normalMin || value > normalMax);
      led.className = 'led ' + (abnormal ? 'red' : 'green');
    }

    loadSettings();
    loadHistory();
    setInterval(loadHistory, 10000);
  </script>
</body>

</html>