@extends('layouts.userapp')

@section('main')
@php
  use Illuminate\Support\Facades\Storage;
@endphp

<div class="content-wrapper">
  <section class="content-header mb-3">
    <h1 class="mb-1">Data Activity</h1>
    <div class="text-muted">
      Team: {{ $checklist->team }} • User ID: {{ $checklist->user_id }}
    </div>
  </section>

  <form method="post"
        action="{{ route('checklists.item.bulkStore', $checklist->id) }}"
        enctype="multipart/form-data">
    @csrf

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger">
        <div class="mb-1 fw-bold">Periksa input:</div>
        <ul class="mb-0">
          @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
      </div>
    @endif

    <div class="row g-3">
      @foreach($activities as $i => $a)
        @php
          $already = $items->firstWhere('activity_id', $a->id);
          $isDone  = $already && $already->status === 'done';
          $beforeExists = $already && $already->before_photo && Storage::disk('public')->exists($already->before_photo);
          $afterExists  = $already && $already->after_photo && Storage::disk('public')->exists($already->after_photo);
        @endphp

        <div class="col-12">
          <div class="card activity-card shadow-sm {{ $isDone ? 'border-success' : '' }}">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <h5 class="mb-1">{{ $i+1 }}. {{ $a->name }}</h5>
                  @if($a->description)
                    <p class="small text-muted mb-1">{{ $a->description }}</p>
                  @endif
                  <span class="badge bg-info">{{ $a->point }} Star</span>
                  @if($already)
                    <span class="badge bg-success ms-1">Tersimpan</span>
                  @endif
                </div>
                <div class="form-check">
                  <input type="checkbox"
                         class="form-check-input toggle-done"
                         name="status[{{ $a->id }}]"
                         data-row="{{ $a->id }}"
                         @if($isDone) checked @endif>
                  <label class="form-check-label">Done</label>
                </div>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Foto Before</label>
                  <div class="d-flex gap-2 align-items-start flex-nowrap">
                    @if($beforeExists)
                      @php $url = Storage::disk('public')->url($already->before_photo); @endphp
                      <a href="{{ $url }}" target="_blank">
                        <img src="{{ $url }}" class="img-thumbnail rounded border" width="90" alt="before">
                      </a>
                    @endif
                    <input type="file"
                           name="before_photo[{{ $a->id }}]"
                           class="form-control file-before"
                           data-row="{{ $a->id }}"
                           @if(!$isDone) disabled @endif>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold">Foto After</label>
                  <div class="d-flex gap-2 align-items-start flex-nowrap">
                    @if($afterExists)
                      @php $url = Storage::disk('public')->url($already->after_photo); @endphp
                      <a href="{{ $url }}" target="_blank">
                        <img src="{{ $url }}" class="img-thumbnail rounded border" width="90" alt="after">
                      </a>
                    @endif
                    <input type="file"
                           name="after_photo[{{ $a->id }}]"
                           class="form-control file-after"
                           data-row="{{ $a->id }}"
                           @if(!$isDone) disabled @endif>
                  </div>
                </div>

                <div class="col-12">
                  <label class="form-label fw-semibold">Catatan</label>
                  <textarea name="note[{{ $a->id }}]"
                            class="form-control"
                            rows="2"
                            placeholder="opsional"
                            data-row="{{ $a->id }}"
                            @if(!$isDone) disabled @endif>{{ $already->note ?? '' }}</textarea>
                </div>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary px-4" name="finish" value="0">💾 Simpan</button>
      <button class="btn btn-success px-4" name="finish" value="1">✅ Simpan &amp; Selesaikan</button>
    </div>
  </form>
</div>

{{-- STYLE tambahan biar lebih menarik --}}
<style>
  @media (max-width: 576px) {
    h1, h3, h5 { font-size: 1rem !important; }     /* judul lebih kecil */
    .card-title { font-size: 0.95rem !important; }
    .card .form-label { font-size: 0.85rem !important; }
    .card .small, .text-muted { font-size: 0.75rem !important; }
    .badge { font-size: 0.7rem !important; }
    .btn { font-size: 0.85rem !important; padding: .35rem .75rem !important; }
    textarea, input, select { font-size: 0.85rem !important; }
  }
  .activity-card {
    transition: 0.2s;
    border-left: 5px solid #0dcaf0; /* biru default */
  }
  .activity-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,.1);
  }
  .activity-card.border-success {
    border-left: 5px solid #198754; /* hijau kalau sudah done */
  }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  // tetap sama, toggle enable/disable
  document.querySelectorAll('.toggle-done').forEach(function(cb){
    cb.addEventListener('change', function(){
      const id = this.getAttribute('data-row');
      const enable = this.checked;
      document.querySelector(`input.file-before[data-row="${id}"]`)?.toggleAttribute('disabled', !enable);
      document.querySelector(`input.file-after[data-row="${id}"]`)?.toggleAttribute('disabled', !enable);
      document.querySelector(`textarea[data-row="${id}"]`)?.toggleAttribute('disabled', !enable);
    });
  });
});
</script>
@endpush
@endsection
