@extends('layouts.appBestRising')

@section('main')
<div class="content-wrapper">

  <div class="card">
    <div class="card-header">
      <h3 class="mb-0">All Activity Results</h3>
    </div>

    <div class="card-body">

      {{-- FILTERS --}}
      <div class="row g-2 mb-3 align-items-end">
        <div class="col-md-2">
          <label class="form-label mb-1">Region</label>
          <select id="f_region" class="form-control">
            <option value="">Semua</option>
            @foreach(\DB::table('regions')->orderBy('nama_region')->get() as $rg)
              <option value="{{ $rg->id_region }}">{{ $rg->nama_region }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Serpo</label>
          <select id="f_serpo" class="form-control">
            <option value="">Semua</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Segmen</label>
          <select id="f_segmen" class="form-control">
            <option value="">Semua</option>
            <option value="0">Tanpa Segmen</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Tanggal From</label>
          <input type="date" id="f_from" class="form-control">
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Tanggal To</label>
          <input type="date" id="f_to" class="form-control">
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Nama / Email</label>
          <input type="text" id="f_name" class="form-control" placeholder="cari nama/email">
        </div>

        <div class="col-12 mt-2 d-flex gap-2">
          <button id="btnApply" class="btn btn-primary btn-sm mr-1">Terapkan</button>
          <button id="btnReset" class="btn btn-outline-secondary btn-sm mr-1">Reset</button>
          <a id="btnExport" class="btn btn-success btn-sm mr-1" href="#">Export Excel</a>
        </div>
      </div>

      <table id="table-results" class="table table-bordered table-striped w-100">
        <thead>
        <tr>
          <th>No</th>
          {{-- hidden (tetap di-fetch) --}}
          <th>ID</th>
          <th>Checklist</th>
          <th>Submitted At</th>
          {{-- shown --}}
          <th>User</th>
          <th>Activity</th>
          <th>Sub Activity</th>
          <th>Region</th>
          <th>Serpo</th>
          <th>Segmen</th>
          <th>Status</th>
          <th>Before Photos</th>
          <th>After Photos</th>
          <th>Star</th>
          <th>Note</th>
          <th>Created At</th>
        </tr>
        </thead>
      </table>

    </div>
  </div>

</div>

{{-- =========================
     MODAL + CAROUSEL PREVIEW
   ========================= --}}
<div class="modal fade" id="photoModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content border-0">
      <div class="modal-header">
        <h5 class="modal-title" id="photoModalTitle">Preview</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-0">
        <div id="photoCarousel" class="carousel slide" data-ride="carousel" data-interval="0" data-keyboard="true">
          <ol class="carousel-indicators"></ol>
          <div class="carousel-inner"></div>
          <a class="carousel-control-prev" href="#photoCarousel" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Prev</span>
          </a>
          <a class="carousel-control-next" href="#photoCarousel" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
          </a>
        </div>
      </div>
      <div class="modal-footer py-2">
        <small class="text-muted" id="photoCaption"></small>
      </div>
    </div>
  </div>
</div>

<style>
  .thumb-img{
    width:64px;height:64px;object-fit:cover;border-radius:6px;border:1px solid rgba(0,0,0,.12);
    cursor:pointer;
  }
  table.dataTable td, table.dataTable th { vertical-align: middle; }

  .photo-scroller{
    display:flex; gap:6px; overflow-x:auto; padding-bottom:4px;
    scroll-snap-type:x proximity; max-width:220px;
  }
  .photo-scroller::-webkit-scrollbar{ height:8px; }
  .photo-scroller::-webkit-scrollbar-thumb{ background:rgba(0,0,0,.18); border-radius:8px; }
  .photo-item{ scroll-snap-align:start; position:relative; display:inline-block; }
  .photo-badge{
    position:absolute; left:4px; bottom:4px; background:rgba(0,0,0,.6);
    color:#fff; font-size:10px; padding:1px 4px; border-radius:3px; line-height:1;
  }
  #photoCarousel .carousel-item{ background:#000; min-height:60vh; }
  #photoCarousel .carousel-item img{
    max-height:80vh; width:auto; height:auto; object-fit:contain; display:block; margin:auto;
  }
</style>
<style>
/* Modal viewer rapi */
#photoModal .modal-dialog { max-width: 900px; }
#photoModal .modal-content { background-color:#000; border:none; border-radius:10px; overflow:hidden; }
#photoModal .modal-header { background:#111; color:#fff; border-bottom:none; padding:10px 15px; }
#photoModal .modal-header .close { color:#fff; opacity:.8; font-size:26px; }
#photoModal .modal-body { padding:0; position:relative; }
#photoModal .modal-footer { background:#111; border-top:none; justify-content:center; color:#aaa; font-size:13px; }
#photoCarousel .carousel-item { background:#000; min-height:70vh; }
#photoCarousel .carousel-item.active,
#photoCarousel .carousel-item-next,
#photoCarousel .carousel-item-prev { display:flex; align-items:center; justify-content:center; transition:transform .4s ease; }
#photoCarousel .carousel-item img { max-height:75vh; width:auto; object-fit:contain; box-shadow:0 0 10px rgba(0,0,0,.5); border-radius:4px; }
#photoCarousel .carousel-control-prev, #photoCarousel .carousel-control-next { width:8%; }
#photoCarousel .carousel-control-prev-icon, #photoCarousel .carousel-control-next-icon { filter: drop-shadow(0 0 4px rgba(0,0,0,.8)); }
#photoCarousel .carousel-indicators { bottom:8px; }
#photoCarousel .carousel-indicators li { background-color:#999; width:20px; height:4px; border-radius:2px; transition:all .3s ease; }
#photoCarousel .carousel-indicators .active { background-color:#fff; width:30px; }
.thumb-img { cursor:pointer; transition: transform .2s ease, box-shadow .2s ease; }
.thumb-img:hover { transform:scale(1.05); box-shadow:0 2px 8px rgba(0,0,0,.25); }
</style>

<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
  // Inject dari controller
  const SESSION_REGION_ID = {{ (int)($sessionRegionId ?? 0) }};

  function buildExportUrl(){
    const params = new URLSearchParams({
      region:    $('#f_region').val() || '',
      serpo:     $('#f_serpo').val()  || '',
      segmen:    $('#f_segmen').val() || '',
      date_from: $('#f_from').val()   || '',
      date_to:   $('#f_to').val()     || '',
      keyword:   $('#f_name').val()   || '',
    });
    return "{{ route('admin.checklists.allresult.export') }}?" + params.toString();
  }
  $('#btnExport').on('click', function(e){
    e.preventDefault();
    window.location.href = buildExportUrl();
  });

  $(function(){

    // Helper isi <select>
    function fillOptions($select, items, placeholder='Semua') {
      $select.empty().append(`<option value="">${placeholder}</option>`);
      if (!items) return;
      items.forEach(row => {
        const id   = row.id ?? row.id_serpo ?? row.id_segmen ?? row.value ?? null;
        const text = row.text ?? row.nama_serpo ?? row.nama_segmen ?? row.nama ?? row.label ?? '';
        if (id !== null && text !== '') {
          $select.append(`<option value="${id}">${text}</option>`);
        }
      });
    }

    // Jika session region ada → preset & lock region filter
    if (SESSION_REGION_ID > 0) {
      $('#f_region').val(String(SESSION_REGION_ID)).prop('disabled', true).trigger('change');
      // preload serpo list buat region ini
      $.get("{{ route('admin.serpo.byRegion', ['id_region' => 'IDR']) }}".replace('IDR', SESSION_REGION_ID))
        .done(res => fillOptions($('#f_serpo'), (res?.data ?? res), 'Semua'));
    }

    // Dependent dropdowns
    $('#f_region').on('change', function(){
      const id = $(this).val();
      fillOptions($('#f_serpo'),  null);
      fillOptions($('#f_segmen'), null);
      if (!id) return;

      $.get("{{ route('admin.serpo.byRegion', ['id_region' => 'IDR']) }}".replace('IDR', id))
        .done(res => fillOptions($('#f_serpo'), (res?.data ?? res), 'Semua'))
        .fail(() => fillOptions($('#f_serpo'), null, 'Semua'));
    });

    $('#f_serpo').on('change', function(){
      const id = $(this).val();
      fillOptions($('#f_segmen'), null);
      if (!id) return;

      $.get("{{ route('admin.segmen.bySerpo', ['id_serpo' => 'IDS']) }}".replace('IDS', id))
        .done(res => fillOptions($('#f_segmen'), (res?.data ?? res), 'Semua'))
        .fail(() => fillOptions($('#f_segmen'), null, 'Semua'));
    });

    // DataTable
    const table = $('#table-results').DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      ajax: {
        url: "{{ route('admin.checklists.allresult') }}",
        type: 'GET',
        data: function(d){
          // kalau session region ada, kirimkan juga (server tetap enforce)
          d.region    = SESSION_REGION_ID > 0 ? SESSION_REGION_ID : ($('#f_region').val() || '');
          d.serpo     = $('#f_serpo').val()  || '';
          d.segmen    = $('#f_segmen').val() || '';
          d.date_from = $('#f_from').val()   || '';
          d.date_to   = $('#f_to').val()     || '';
          d.keyword   = $('#f_name').val()   || '';
          return d;
        }
      },
      order: [[14,'desc']], // Created At (index 14)
      columns: [
        { data:'DT_RowIndex', orderable:false, searchable:false },

        // hidden
        { data:'id',           visible:false, searchable:false, defaultContent:'-' },
        { data:'checklist_id', visible:false, searchable:false, defaultContent:'-' },
        { data:'submitted_at', visible:false, searchable:false, defaultContent:'-' },

        // shown
        { data:'user_nama',     name:'u.nama',        defaultContent:'-' },
        { data:'activity_nama', name:'act.name',      defaultContent:'-' },
        { 
          data: 'sub_activity_nama', 
          name: 'ar.sub_activities', 
          defaultContent: '-', 
          render: function(data, type, row) {
              // Jika data adalah array atau JSON, gabungkan menjadi string
              if (Array.isArray(data)) {
                  return data.join(', ').replace(/"/g, '');  // Menggabungkan array menjadi string dengan koma dan menghapus tanda kutip
              }
              // Hapus tanda kutip jika data adalah string biasa
              return (data || '').replace(/"/g, '') || '-';  // Menghilangkan kutip dan menampilkan '-' jika data kosong
          }
        },
        { data:'region_nama',   name:'r.nama_region', defaultContent:'-' },
        { data:'serpo_nama',    name:'sp.nama_serpo', defaultContent:'-' },
        { data:'segmen_nama',   searchable:false,     defaultContent:'-' },
        { data:'status',        name:'ar.status',     defaultContent:'-' },

        // kolom foto
        { data:'before_photos', orderable:false, searchable:false, defaultContent:'-' },
        { data:'after_photos',  orderable:false, searchable:false, defaultContent:'-' },

        { data:'point_earned',  name:'ar.point_earned', className:'text-end', defaultContent:'0' },
        { data:'note',          name:'ar.note',       defaultContent:'-' },
        { data:'created_at',    name:'ar.created_at', defaultContent:'-' },
      ],
      pageLength: 25
    });

    // Apply / Reset
    $('#btnApply').on('click', function(){ table.ajax.reload(null, false); });

    $('#btnReset').on('click', function(){
      // kalau region forced, jangan direset
      if (SESSION_REGION_ID > 0) {
        $('#f_region').val(String(SESSION_REGION_ID));
      } else {
        $('#f_region').val('');
      }
      fillOptions($('#f_serpo'),  null);
      fillOptions($('#f_segmen'), null);
      $('#f_from,#f_to,#f_name').val('');
      table.ajax.reload(null, false);
    });

    // Preview Modal
    $(document).on('click', '#table-results img.thumb-img', function () {
      const $imgClicked = $(this);
      const $cell       = $imgClicked.closest('td');
      const $row        = $imgClicked.closest('tr');

      const badge = ($imgClicked.siblings('.photo-badge').text() || '').trim().toUpperCase();
      const groupLabel = (badge === 'B') ? 'Before Photos' : (badge === 'A' ? 'After Photos' : 'Photos');

      const $imgs = $cell.find('img.thumb-img');
      if (!$imgs.length) return;

      let startIndex = 0;
      const indicators = [];
      const slides     = [];

      $imgs.each(function(i){
        const $im  = $(this);
        const full = $im.attr('data-full') || $im.attr('src');
        const alt  = $im.attr('alt') || `${groupLabel} ${i+1}`;
        if (this === $imgClicked[0]) startIndex = i;
        indicators.push(`<li data-target="#photoCarousel" data-slide-to="${i}" class="${i===0?'active':''}"></li>`);
        slides.push(
          `<div class="carousel-item ${i===0?'active':''}">
            <img src="${full}" alt="${alt}">
          </div>`
        );
      });

      try { $('#photoCarousel').carousel('dispose'); } catch(e) {}

      $('#photoCarousel .carousel-indicators').html(indicators.join(''));
      $('#photoCarousel .carousel-inner').html(slides.join(''));
      $('#photoCarousel').carousel({ interval: false, keyboard: true, ride: false });

      const many = $imgs.length > 1;
      $('#photoCarousel .carousel-control-prev, #photoCarousel .carousel-control-next').toggle(many);
      $('#photoCarousel .carousel-indicators').toggle(many);

      const user    = $row.find('td').eq(4).text().trim();
      const act     = $row.find('td').eq(5).text().trim();
      const created = $row.find('td').eq(14).text().trim();
      $('#photoModalTitle').text(groupLabel);
      $('#photoCaption').text([user||'-', act||'-', created||'-'].join(' • '));

      $('#photoModal').modal('show');
      $('#photoModal').one('shown.bs.modal', function(){
        $('#photoCarousel').carousel(startIndex);
      });
    });

    // Swipe support
    (function(){
      const $carousel = $('#photoCarousel');
      let startX = 0, dx = 0;
      $carousel.on('touchstart', function(e){ startX = e.originalEvent.touches[0].clientX; });
      $carousel.on('touchmove',  function(e){ dx = e.originalEvent.touches[0].clientX - startX; });
      $carousel.on('touchend',   function(){
        if (Math.abs(dx) > 40){ $(this).carousel(dx < 0 ? 'next' : 'prev'); }
        startX = 0; dx = 0;
      });
    })();

    // Keyboard nav saat modal terbuka
    $(document).on('keydown', function(e){
      if (!$('#photoModal').hasClass('show')) return;
      if (e.key === 'ArrowLeft')  $('#photoCarousel').carousel('prev');
      if (e.key === 'ArrowRight') $('#photoCarousel').carousel('next');
    });

  });
</script>
@endsection
