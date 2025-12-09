<div class="page">
  <h2>Sales Analytics</h2>
  <div style="display:grid;gap:1rem;margin:.75rem 0 1.25rem;grid-template-columns:1fr;">
    <div style="background:#fff;border:1px solid #dde2e8;border-radius:10px;padding:1rem;">
      <h3 style="margin:.25rem 0 1rem;">Top Products (by qty)</h3>
      <canvas id="topProductsChart" height="160" aria-label="Top products chart" role="img"></canvas>
    </div>
    <div style="background:#fff;border:1px solid #dde2e8;border-radius:10px;padding:1rem;">
      <h3 style="margin:.25rem 0 1rem;">Daily Revenue (Last 30 Days)</h3>
      <canvas id="dailyRevenueChart" height="160" aria-label="Daily revenue chart" role="img"></canvas>
    </div>
  </div>

  <h3>Top Selling Products</h3>
  <table class="cart-table">
    <thead><tr><th>ID</th><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
    <tbody>
    <?php foreach($topProducts as $p): ?>
      <tr>
        <td><?= (int)$p['id'] ?></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td><?= (int)$p['qty'] ?></td>
        <td>$<?= number_format($p['revenue'],2) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <h3 style="margin-top:1.5rem;">Product Sales Summary</h3>
  <table class="cart-table">
    <thead><tr><th>ID</th><th>Product</th><th>Total Qty</th><th>Total Revenue</th><th>Last Order</th></tr></thead>
    <tbody>
    <?php foreach($productSummary as $p): ?>
      <tr>
        <td><?= (int)$p['id'] ?></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td><?= (int)$p['qty'] ?></td>
        <td>$<?= number_format($p['revenue'],2) ?></td>
        <td><?= htmlspecialchars($p['last_order']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <h3 style="margin-top:1.5rem;">Daily Revenue (Last 30 Days)</h3>
  <table class="cart-table">
    <thead><tr><th>Date</th><th>Orders</th><th>Revenue</th></tr></thead>
    <tbody>
    <?php foreach($daily as $d): ?>
      <tr>
        <td><?= htmlspecialchars($d['day']) ?></td>
        <td><?= (int)$d['orders'] ?></td>
        <td>$<?= number_format($d['revenue'],2) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  // Prepare data safely in PHP without arrow functions (compatibility)
  <?php
    $tpLabels = array_map('strval', array_column($topProducts, 'name'));
    $tpQty = array_map('intval', array_column($topProducts, 'qty'));
    $tpRevenue = array_map('floatval', array_column($topProducts, 'revenue'));
    $dailyLabels = array_map('strval', array_column($daily, 'day'));
    $dailyRevenue = array_map('floatval', array_column($daily, 'revenue'));
  ?>
  (function(){
    try {
      const tpLabels = <?= json_encode($tpLabels) ?>;
      const tpQty = <?= json_encode($tpQty) ?>;
      const tpRevenue = <?= json_encode($tpRevenue) ?>;

      const dailyLabels = <?= json_encode($dailyLabels) ?>;
      const dailyRevenue = <?= json_encode($dailyRevenue) ?>;

      const tpCtx = document.getElementById('topProductsChart');
      if (tpCtx && window.Chart) {
        new Chart(tpCtx, {
          type: 'bar',
          data: {
            labels: tpLabels,
            datasets: [
              {
                label: 'Qty Sold',
                data: tpQty,
                backgroundColor: 'rgba(45, 106, 79, 0.8)'
              },
              {
                label: 'Revenue',
                data: tpRevenue,
                yAxisID: 'y1',
                type: 'line',
                borderColor: 'rgba(16, 63, 47, 0.9)',
                backgroundColor: 'rgba(16, 63, 47, 0.15)',
                tension: .25,
                fill: true
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
              y: { beginAtZero: true, title: { display: true, text: 'Quantity' } },
              y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Revenue ($)' } }
            },
            plugins: { legend: { display: true }, tooltip: { enabled: true } }
          }
        });
      }

      const drCtx = document.getElementById('dailyRevenueChart');
      if (drCtx && window.Chart) {
        new Chart(drCtx, {
          type: 'line',
          data: {
            labels: dailyLabels,
            datasets: [{
              label: 'Revenue ($)',
              data: dailyRevenue,
              borderColor: 'rgba(45, 106, 79, 1)',
              backgroundColor: 'rgba(45, 106, 79, 0.15)',
              tension: .25,
              fill: true
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: true }, tooltip: { enabled: true } }
          }
        });
      }
    } catch (e) { console.error('Analytics charts failed', e); }
  })();
</script>
