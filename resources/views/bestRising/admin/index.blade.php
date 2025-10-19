@extends('layouts.appBestRising')

@section('main')
@php $adminName = $adminName ?? (auth()->user()->nama ?? 'Admin'); @endphp

<div class="content-wrapper">
  <section class="content-header">
    <div class="d-flex align-items-center justify-content-between flex-wrap w-100">
      <div class="mb-2">
        <h1 class="mb-1">Selamat datang, {{ $adminName }}</h1>
        <div class="text-muted">
          <span id="greeting">Halo!</span> • <span id="todayLabel"></span> • <span id="clock"></span>
        </div>
      </div>

      {{-- FILTER REGION: muncul hanya jika TIDAK terkunci oleh session --}}
      @if(empty($sessionRegionId))
        <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center gap-2">
          <label for="region_id" class="mb-0 me-2 text-nowrap">Filter Region:</label>
          <select name="region_id" id="region_id" class="form-control form-control-sm" onchange="this.form.submit()">
            <option value="">All Region</option>
            @foreach($regionOptions as $rg)
              <option value="{{ $rg->id_region }}" {{ (string)$rg->id_region === (string)$activeRegionId ? 'selected' : '' }}>
                {{ $rg->nama_region }}
              </option>
            @endforeach
          </select>
          @if(request()->has('region_id') && request('region_id') !== '')
            <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary ms-2">Reset</a>
          @endif
        </form>
      @else
        {{-- Info region terkunci --}}
        <div class="text-end small text-muted">
          Region aktif:
          <strong>
            {{ optional(\App\Models\Region::find($sessionRegionId))->nama_region ?? ('#'.$sessionRegionId) }}
          </strong>
        </div>
      @endif
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      {{-- KPI utama --}}
      <div class="row g-3">
        <div class="col-12 col-sm-6 col-lg-3">
          <a class="small-box kpi-box" href="{{ route('admin.user-bestrising.index') }}">
            <div class="inner"><h3>{{ $counts['users'] }}</h3><p>User</p></div>
            <div class="icon"><i class="fas fa-users"></i></div>
          </a>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
          <a class="small-box kpi-box" href="{{ route('admin.region.index') }}">
            <div class="inner"><h3>{{ $counts['regions'] }}</h3><p>Region</p></div>
            <div class="icon"><i class="fas fa-map"></i></div>
          </a>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
          <a class="small-box kpi-box" href="{{ route('admin.serpo.index') }}">
            <div class="inner"><h3>{{ $counts['serpos'] }}</h3><p>Serpo</p></div>
            <div class="icon"><i class="fas fa-tools"></i></div>
          </a>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
          <a class="small-box kpi-box" href="{{ route('admin.segmen.index') }}">
            <div class="inner"><h3>{{ $counts['segmens'] }}</h3><p>Segmen</p></div>
            <div class="icon"><i class="fas fa-layer-group"></i></div>
          </a>
        </div>
      </div>

      {{-- KPI status checklist (sinkron gaya KPI utama) --}}
      <div class="row g-3 mt-2 kpi-status">
        <div class="col-12 col-sm-3">
          <a class="small-box kpi-box kpi-acc" href="{{ route('admin.checklists.index') }}">
            <div class="inner">
              <h3>{{ $statusStats['acc'] }}</h3>
              <p>Approved</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
          </a>
        </div>
        <div class="col-12 col-sm-3">
          <a class="small-box kpi-box kpi-pending" href="{{ route('admin.checklists.index') }}">
            <div class="inner">
              <h3>{{ $statusStats['pending'] }}</h3>
              <p>Pending</p>
            </div>
            <div class="icon"><i class="fas fa-hourglass-half"></i></div>
          </a>
        </div>
        <div class="col-12 col-sm-3">
          <a class="small-box kpi-box kpi-review-admin" href="{{ route('admin.checklists.index') }}">
            <div class="inner">
              <h3>{{ $statusStats['review_admin'] }}</h3>
              <p>Review Admin</p>
            </div>
            <div class="icon"><i class="fas fa-hourglass-half"></i></div>
          </a>
        </div>
        <div class="col-12 col-sm-3">
          <a class="small-box kpi-box kpi-reject" href="{{ route('admin.checklists.index') }}">
            <div class="inner">
              <h3>{{ $statusStats['rejected'] }}</h3>
              <p>Rejected</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
          </a>
        </div>
      </div>

      {{-- Grafik ringkas --}}
      <div class="row mt-3">
        <div class="col-xl-6">
          <div class="card h-100">
            <div class="card-header"><strong>Distribusi User per Kategori</strong></div>
            <div class="card-body">
              <div class="chart-box"><canvas id="chartUserKategori"></canvas></div>
            </div>
          </div>
        </div>

        <div class="col-xl-6 mt-3 mt-xl-0">
          <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
              <strong>Jumlah Serpo per Region</strong>
              <a href="{{ route('admin.region.index') }}" class="btn btn-xs btn-outline-secondary">Detail</a>
            </div>
            <div class="card-body">
              <div class="chart-box"><canvas id="chartSerpoRegion"></canvas></div>
            </div>
          </div>
        </div>
      </div>

      {{-- Top Serpo by Points (Periode 3 Bulan dari Anchor) --}}
      <div class="row mt-3">
        <div class="col-xl-8">
          <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
              <strong>Top Serpo by Points — {{ $periodLabel }}</strong>
            </div>
            <div class="card-body">
              <div class="chart-box"><canvas id="chartSerpoPointsQuarter"></canvas></div>
              <small class="text-muted d-block mt-2">
                Periode: {{ $periodStart->format('d M Y') }} s/d {{ $periodEnd->format('d M Y') }}
              </small>
            </div>
          </div>
        </div>
        <div class="col-xl-4 mt-3 mt-xl-0">
          <div class="card h-100">
            <div class="card-header"><strong>Top 7 (Detail)</strong></div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-sm mb-0">
                  <thead>
                    <tr>
                      <th>Serpo</th>
                      <th>Region</th>
                      <th class="text-right">Points</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($serpoPointsQuarter as $row)
                      <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['sub'] ?? '-' }}</td>
                        <td class="text-right pr-2">{{ number_format($row['value']) }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="3" class="text-center text-muted p-3">Belum ada data periode ini</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Distribusi (Top 5) --}}
      <div class="row mt-3">
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
              <strong>Top Region by Serpo</strong>
              <a href="{{ route('admin.region.index') }}" class="btn btn-xs btn-outline-secondary">Kelola Region</a>
            </div>
            <div class="card-body p-0">
              <table class="table table-hover mb-0">
                <thead><tr><th>Region</th><th class="text-right pr-3"># Serpo</th></tr></thead>
                <tbody>
                  @forelse($serpoByRegion as $r)
                    <tr><td>{{ $r->nama_region }}</td><td class="text-right pr-3">{{ $r->serpos_count }}</td></tr>
                  @empty
                    <tr><td colspan="2" class="text-center text-muted p-3">Belum ada data</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-lg-6 mt-3 mt-lg-0">
          <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
              <strong>Top Serpo by Segmen</strong>
              <a href="{{ route('admin.serpo.index') }}" class="btn btn-xs btn-outline-secondary">Kelola Serpo</a>
            </div>
            <div class="card-body p-0">
              <table class="table table-hover mb-0">
                <thead><tr><th>Serpo</th><th class="text-right pr-3"># Segmen</th></tr></thead>
                <tbody>
                  @forelse($segmenBySerpo as $s)
                    <tr><td>{{ $s->nama_serpo }}</td><td class="text-right pr-3">{{ $s->segmens_count }}</td></tr>
                  @empty
                    <tr><td colspan="2" class="text-center text-muted p-3">Belum ada data</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      {{-- Terbaru (opsional) --}}

    </div>
  </section>
</div>

<style>
  .table td, .table th { vertical-align: middle; }
  .table td.text-break { word-break: break-word; max-width: 180px; }

  /* Small-box seragam */
  .kpi-box.small-box{
    background:#fff;
    border:1px solid #e9ecef;
    border-radius:.5rem;
    box-shadow:0 1px 1px rgba(0,0,0,.03);
    overflow:hidden;
  }
  .kpi-box.small-box .inner{ position:relative; z-index:1; min-height:64px; padding-right:56px; }
  .kpi-box.small-box .inner h3{ font-weight:700; color:#1459d2; }
  .kpi-box.small-box .inner p{ margin-bottom:0; color:#6c757d; }

  .kpi-box.small-box .icon{
    position:absolute !important;
    top:14px !important;
    right:14px !important;
    width:auto !important;
    height:auto !important;
    line-height:1 !important;
    font-size:28px !important;
    color:#28a745 !important;
    opacity:.75 !important;
    transform:none !important;
  }
  .kpi-box.small-box .icon i{ font-size:28px; }

  /* Variasi status */
  .kpi-status .kpi-acc     { border-left:4px solid #28a745; }
  .kpi-status .kpi-acc .icon { color:#28a745 !important; }

  .kpi-status .kpi-pending { border-left:4px solid #f0ad4e; }
  .kpi-status .kpi-pending .icon { color:#f0ad4e !important; }

  .kpi-status .kpi-review-admin { border-left:4px solid #ac07ff; }
  .kpi-status .kpi-review-admin .icon { color:#ac07ff !important; }

  .kpi-status .kpi-reject  { border-left:4px solid #dc3545; }
  .kpi-status .kpi-reject .icon { color:#dc3545 !important; }

  /* Chart sizing */
  .chart-box{height:280px; position:relative;}
  @media (max-width: 575.98px){ .chart-box{height:240px;} }
</style>

{{-- Pin versi Chart.js dan tambahkan lazy-init + animasi --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function(){
  const g=document.getElementById('greeting'), c=document.getElementById('clock'), t=document.getElementById('todayLabel');
  function greet(){const h=new Date().getHours(); g.textContent=(h<11?'Selamat pagi':h<15?'Selamat siang':h<19?'Selamat sore':'Selamat malam');}
  function tick(){const d=new Date(),pad=n=>n.toString().padStart(2,'0'); c.textContent=`${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;}
  function today(){const d=new Date(),opt={weekday:'long',year:'numeric',month:'long',day:'numeric'}; t.textContent=d.toLocaleDateString('id-ID',opt);}
  greet(); today(); tick(); setInterval(tick,1000);
})();

// ====== Chart.js defaults: paksa animasi aktif ======
if (window.Chart) {
  Chart.defaults.animation = { duration: 900, easing: 'easeOutQuart' };
}

// ====== Helper: buat chart saat kanvas terlihat (biar animasi selalu kelihatan)
function makeChartWhenVisible(canvasId, makeConfig){
  const el = document.getElementById(canvasId);
  if (!el) return;

  const create = () => {
    if (el._chartInstance) return;
    const cfg = makeConfig();
    if (!cfg) return;
    el._chartInstance = new Chart(el, cfg);
  };

  const rect = el.getBoundingClientRect();
  const visible = rect.top < innerHeight && rect.bottom > 0;
  if (visible) { create(); return; }

  const io = new IntersectionObserver((entries, obs) => {
    for (const e of entries) {
      if (e.isIntersecting) { create(); obs.unobserve(e.target); break; }
    }
  }, { threshold: 0.15, rootMargin: '0px 0px -10% 0px' });
  io.observe(el);
}

// ====== Data dari controller
const userKategori       = @json($userKategori ?? []);
const serpoPerRegion     = @json($serpoPerRegion ?? []);
const serpoPointsQuarter = @json($serpoPointsQuarter ?? []);

// ====== Donut: user per kategori
makeChartWhenVisible('chartUserKategori', () => {
  if (!userKategori.length) return null;
  return {
    type: 'doughnut',
    data: {
      labels: userKategori.map(x => x.label),
      datasets: [{ data: userKategori.map(x => x.value) }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom' } },
      cutout: '55%',
      animation: { duration: 900, easing: 'easeOutQuart', animateRotate: true, animateScale: true }
    }
  };
});

// ====== Horizontal bar: serpo per region
makeChartWhenVisible('chartSerpoRegion', () => {
  if (!serpoPerRegion.length) return null;
  return {
    type: 'bar',
    data: {
      labels: serpoPerRegion.map(x => x.label),
      datasets: [{ label: 'Serpo', data: serpoPerRegion.map(x => x.value) }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      indexAxis: 'y',
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
      animation: { duration: 900, easing: 'easeOutQuart' },
      animations: { x: { from: 0 }, y: { from: 0 } }
    }
  };
});

// ====== Horizontal bar: top serpo by points (quarter)
makeChartWhenVisible('chartSerpoPointsQuarter', () => {
  if (!serpoPointsQuarter.length) return null;
  return {
    type: 'bar',
    data: {
      labels: serpoPointsQuarter.map(x => x.sub ? `${x.label} — ${x.sub}` : x.label),
      datasets: [{ label: 'Points', data: serpoPointsQuarter.map(x => x.value) }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      indexAxis: 'y',
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (ctx)=> ` ${ctx.raw} poin` } }
      },
      scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
      animation: { duration: 900, easing: 'easeOutQuart' },
      animations: { x: { from: 0 }, y: { from: 0 } }
    }
  };
});
</script>
@endsection
