@extends('layouts.appBestRising')

@section('main')
@php $adminName = $adminName ?? (auth()->user()->nama ?? 'Admin'); @endphp

<div class="content-wrapper">
  <section class="content-header">
    <div class="d-flex align-items-center justify-content-between flex-wrap">
      <div>
        <h1 class="mb-1">Selamat datang, {{ $adminName }}</h1>
        <div class="text-muted">
          <span id="greeting">Halo!</span> • <span id="todayLabel"></span> • <span id="clock"></span>
        </div>
      </div>
      <a href="#" class="btn btn-outline-success mt-2 mt-md-0">
        <i class="fas fa-question-circle mr-1"></i> Bantuan
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      {{-- KPI --}}
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

      {{-- Distribusi --}}
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

      {{-- Terbaru --}}
      <div class="row mt-3">
        <div class="col-xl-4">
          <div class="card h-100">
            <div class="card-header"><strong>User Terbaru</strong></div>
            <div class="card-body p-0">
              <table class="table table-sm mb-0">
                <thead><tr><th>Nama</th><th>Kategori</th><th>Email</th></tr></thead>
                <tbody>
                  @forelse($latestUsers as $u)
                    <tr>
                      <td>{{ $u->nama }}</td>
                      <td>{{ $u->kategoriUser->nama_kategoriuser ?? '-' }}</td>
                      <td>{{ $u->email }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="3" class="text-center text-muted p-3">—</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-xl-4 mt-3 mt-xl-0">
          <div class="card h-100">
            <div class="card-header"><strong>Serpo Terbaru</strong></div>
            <div class="card-body p-0">
              <table class="table table-sm mb-0">
                <thead><tr><th>Serpo</th><th>Region</th></tr></thead>
                <tbody>
                  @forelse($latestSerpo as $s)
                    <tr><td>{{ $s->nama_serpo }}</td><td>{{ $s->region->nama_region ?? '-' }}</td></tr>
                  @empty
                    <tr><td colspan="2" class="text-center text-muted p-3">—</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-xl-4 mt-3 mt-xl-0">
          <div class="card h-100">
            <div class="card-header"><strong>Segmen Terbaru</strong></div>
            <div class="card-body p-0">
              <table class="table table-sm mb-0">
                <thead><tr><th>Segmen</th><th>Serpo (Region)</th></tr></thead>
                <tbody>
                  @forelse($latestSegmen as $g)
                    <tr>
                      <td>{{ $g->nama_segmen }}</td>
                      <td>
                        {{ $g->serpo->nama_serpo ?? '-' }}
                        @if($g->serpo && $g->serpo->region)
                          <span class="text-muted">({{ $g->serpo->region->nama_region }})</span>
                        @endif
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="2" class="text-center text-muted p-3">—</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<style>
/* -- FIX icon small-box biar ga “keluar” -- */
.kpi-box.small-box{ overflow: hidden; } /* jaga-jaga */
.kpi-box.small-box .icon{
  position: absolute !important;
  top: 14px !important;
  right: 14px !important;
  width: auto !important;
  height: auto !important;
  line-height: 1 !important;
  font-size: 28px !important;   /* ubah ukuran ikon */
  color: #28a745 !important;
  opacity: .75 !important;
  transform: none !important;   /* matikan animasi scale bawaan */
}
.kpi-box.small-box .inner{ position: relative; z-index: 1; }

</style>

<script>
(function(){
  const g=document.getElementById('greeting'), c=document.getElementById('clock'), t=document.getElementById('todayLabel');
  function greet(){const h=new Date().getHours(); g.textContent=(h<11?'Selamat pagi':h<15?'Selamat siang':h<19?'Selamat sore':'Selamat malam');}
  function tick(){const d=new Date(),pad=n=>n.toString().padStart(2,'0'); c.textContent=`${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;}
  function today(){const d=new Date(),opt={weekday:'long',year:'numeric',month:'long',day:'numeric'}; t.textContent=d.toLocaleDateString('id-ID',opt);}
  greet(); today(); tick(); setInterval(tick,1000);
})();
</script>
@endsection
