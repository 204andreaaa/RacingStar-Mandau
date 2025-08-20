@extends('layouts.userapp')

@section('main')
@php
  /** @var array|null $getUserSession */
  $getUserSession = session('auth_user'); // manual auth (array)

  $sessId       = $getUserSession['id']        ?? null;
  $sessRegion   = $getUserSession['id_region'] ?? null;
  $sessSerpo    = $getUserSession['id_serpo']  ?? null;
  $sessSegmen   = $getUserSession['id_segmen'] ?? null;
  $sessKatNama  = strtoupper(trim($getUserSession['kategori_nama'] ?? '')); // <-- penting
  $isNOC        = $sessKatNama === 'NOC';
  $isSERPO      = $sessKatNama === 'SERPO';

  // aturan tampil
  $showRegion        = $isSERPO || $isNOC || $sessKatNama === '';
  $showSerpoAndSeg   = $isSERPO;
@endphp

<div class="content-wrapper">

  {{-- HERO / TITLE --}}
  <section class="content-header mb-2">
    <div class="br-hero shadow-sm">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <h1 class="mb-1">
            Mulai Ceklis <span class="text-muted">({{ $team }})</span>
          </h1>
          <div class="text-muted small">Pilih detail di bawah ini sebelum lanjut ke ceklis aktivitas.</div>
        </div>
        <div class="text-end d-none d-md-block">
          <span class="badge bg-primary-soft me-1">Langkah 1</span>
          <span class="badge bg-success-soft">Persiapan</span>
        </div>
      </div>
    </div>
  </section>

  {{-- CARD FORM --}}
  <div class="card br-card shadow-lg-sm">
    <div class="card-body">
      <form method="post" action="{{ route('checklists.store') }}">
        @csrf

        <input type="hidden" name="team" value="{{ $team }}">

        {{-- Hidden backup jika select disabled karena session --}}
        @if ($getUserSession)
          @if($sessId)     <input type="hidden" name="user_id"   value="{{ $sessId }}"> @endif
          @if($sessRegion) <input type="hidden" name="id_region" value="{{ $sessRegion }}"> @endif
          @if($isSERPO && $sessSerpo)  <input type="hidden" name="id_serpo"  value="{{ $sessSerpo }}"> @endif
          @if($isSERPO && $sessSegmen) <input type="hidden" name="id_segmen" value="{{ $sessSegmen }}"> @endif
        @endif

        {{-- SECTION: IDENTITAS --}}
        <div class="br-section-title">Identitas</div>
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label">Nama</label>
            <select name="user_id" class="form-control br-control" {{ $sessId ? 'disabled' : '' }} required>
              <option value="">— pilih nama —</option>
              @foreach($users as $u)
                <option value="{{ $u->id }}" {{ $sessId == $u->id ? 'selected' : '' }}>
                  {{ $u->nama }}
                </option>
              @endforeach
            </select>
            <small class="text-muted">Nama akan terkunci bila akun sudah login otomatis.</small>
          </div>

          @if($showRegion)
          <div class="col-md-6">
            <label class="form-label">Region</label>
            <select name="id_region" id="id_region" class="form-control br-control" {{ $sessRegion ? 'disabled' : '' }} required>
              <option value="">— pilih region —</option>
              @foreach($regions as $r)
                <option value="{{ $r->id_region }}" {{ $sessRegion == $r->id_region ? 'selected' : '' }}>
                  {{ $r->nama_region }}
                </option>
              @endforeach
            </select>
          </div>
          @endif
        </div>

        {{-- SECTION: LOKASI KERJA --}}
        <div class="br-section-title">Lokasi Kerja</div>
        <div class="row g-3">
          @if($showSerpoAndSeg)
          <div class="col-md-6">
            <label class="form-label">Serpo</label>
            <select name="id_serpo" id="id_serpo" class="form-control br-control" {{ $sessSerpo ? 'disabled' : '' }} required>
              <option value="">— pilih serpo —</option>
              {{-- diisi via JS / preload session --}}
            </select>
            <small class="text-muted">Daftar serpo mengikuti region yang dipilih.</small>
          </div>

          <div class="col-md-6">
            <label class="form-label">Segmen</label>
            <select name="id_segmen" id="id_segmen" class="form-control br-control" {{ $sessSegmen ? 'disabled' : '' }} required>
              <option value="">— pilih segmen —</option>
              {{-- diisi via JS / preload session --}}
            </select>
            <small class="text-muted">Pilih segmen tempat pekerjaan dilakukan.</small>
          </div>
          @endif
        </div>

        <div class="d-flex align-items-center justify-content-between mt-4">
          <div class="text-muted small">
            Pastikan pilihan sudah benar sebelum melanjutkan.
          </div>
          <button class="btn btn-primary br-btn-primary">
            Lanjut ke Ceklis
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- STYLE (tetap) --}}
<style>
  .shadow-lg-sm { box-shadow: 0 6px 24px rgba(16,24,40,.08); }
  .br-hero{ background: linear-gradient(135deg,#f7f9fc,#eff6ff); border:1px solid #e9eef7; border-radius: 16px; padding: 16px 18px; }
  .bg-primary-soft { background:#e9f2ff; color:#1f5eff; }
  .bg-success-soft { background:#e6f7ef; color:#16a34a; }
  .br-card { border:1px solid #eef2f7; border-radius: 18px; }
  .br-section-title{ font-weight:700; font-size:.95rem; letter-spacing:.3px; text-transform:uppercase; color:#64748b; margin:8px 0 10px; position:relative; }
  .br-section-title:after{ content:''; display:block; height:2px; width:52px; background:#c7d2fe; margin-top:6px; border-radius:2px; }
  .br-control{ border-radius:12px; border-color:#e2e8f0; background:#fff; }
  .br-control:focus{ border-color:#94a3b8; box-shadow:0 0 0 .2rem rgba(59,130,246,.15); }
  .br-control[disabled]{ background:#f8fafc; color:#64748b; cursor:not-allowed; }
  .form-label{ font-weight:600; color:#334155; }
  .br-btn-primary{ padding:10px 18px; border-radius:12px; box-shadow:0 6px 16px rgba(37,99,235,.18); }
  .mb-2{margin-bottom:.5rem!important}
  .mt-4{margin-top:1.25rem!important}
</style>

{{-- JS (ditambah guard utk NOC) --}}
<script>
$(function () {
  const routeSerpoByRegion = rid => "{{ route('api.serpo.byRegion', ':rid') }}".replace(':rid', rid);
  const routeSegmenBySerpo = sid => "{{ route('api.segmen.bySerpo', ':sid') }}".replace(':sid', sid);

  const sess_region = @json($sessRegion ?? '');
  const sess_serpo  = @json($sessSerpo  ?? '');
  const sess_segmen = @json($sessSegmen ?? '');
  const sess_kat    = @json($sessKatNama ?? '');

  function resetSelect($el, placeholder) {
    $el.empty().append(new Option(placeholder, ''));
  }

  // Kalau bukan SERPO, kita selesai sampai sini (NOC: gak ada serpo/segmen)
  if (sess_kat !== 'SERPO') {
    return;
  }

  // Event region → load serpo
  $('#id_region').on('change', function () {
    const rid = $(this).val();
    resetSelect($('#id_serpo'), '— pilih serpo —');
    resetSelect($('#id_segmen'), '— pilih segmen —');
    if (!rid) return;

    $.get(routeSerpoByRegion(rid))
      .done(rows => { rows.forEach(x => $('#id_serpo').append(new Option(x.text, x.id))); })
      .fail(() => { alert('Gagal memuat data Serpo.'); });
  });

  // Event serpo → load segmen
  $('#id_serpo').on('change', function () {
    const sid = $(this).val();
    resetSelect($('#id_segmen'), '— pilih segmen —');
    if (!sid) return;

    $.get(routeSegmenBySerpo(sid))
      .done(rows => { rows.forEach(x => $('#id_segmen').append(new Option(x.text, x.id))); })
      .fail(() => { alert('Gagal memuat data Segmen.'); });
  });

  // PRELOAD via session (khusus SERPO)
  if (sess_region) {
    resetSelect($('#id_serpo'), '— pilih serpo —');
    resetSelect($('#id_segmen'), '— pilih segmen —');

    $.get(routeSerpoByRegion(sess_region))
      .done(rows => {
        rows.forEach(x => {
          const opt = new Option(x.text, x.id, false, (x.id == sess_serpo));
          $('#id_serpo').append(opt);
        });
        if (sess_serpo) {
          $.get(routeSegmenBySerpo(sess_serpo))
            .done(rows2 => {
              rows2.forEach(y => {
                const opt2 = new Option(y.text, y.id, false, (y.id == sess_segmen));
                $('#id_segmen').append(opt2);
              });
            })
            .fail(() => { alert('Gagal memuat data Segmen (preload).'); });
        }
      })
      .fail(() => { alert('Gagal memuat data Serpo (preload).'); });
  }
});
</script>
@endsection
