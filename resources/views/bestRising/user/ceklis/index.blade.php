@extends('layouts.userapp')

@section('main')
@php
  use Illuminate\Support\Facades\Storage;
@endphp

<div class="content-wrapper">
  <section class="content-header">
    <h1>Ceklis Aktivitas</h1>
    <div class="text-muted">
      Team: {{ $checklist->team }} • User ID: {{ $checklist->user_id }}
    </div>
  </section>

  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h3 class="mb-0">Isi Checklist</h3>
      <small class="text-muted">Centang <strong>Done</strong> untuk mengaktifkan upload foto</small>
    </div>

    <form method="post"
          action="{{ route('checklists.item.bulkStore', $checklist->id) }}"
          enctype="multipart/form-data">
      @csrf

      <div class="card-body">
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

        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead>
              <tr>
                <th style="width:40px;">#</th>
                <th>Aktivitas</th>
                <th style="width:90px;">Done?</th>
                <th style="width:260px;">Foto Before</th>
                <th style="width:260px;">Foto After</th>
                <th>Catatan</th>
              </tr>
            </thead>
            <tbody>
              @foreach($activities as $i => $a)
                @php
                  $already = $items->firstWhere('activity_id', $a->id);
                  $isDone  = $already && $already->status === 'done';
                  $beforeExists = $already && Storage::disk('public')->exists($already->before_photo);
                  $afterExists  = $already && Storage::disk('public')->exists($already->after_photo);
                @endphp
                <tr class="{{ $isDone ? 'table-success' : '' }}">
                  <td class="text-center">{{ $i+1 }}</td>
                  <td>
                    <div class="fw-bold mb-1">{{ $a->name }}</div>
                    <div class="small text-muted">{{ $a->description }}</div>
                    <span class="badge badge-info mt-1 d-inline-block">{{ $a->point }} Star</span>
                    @if($already)
                      <div class="small text-success mt-1">Sudah tersimpan ({{ ucfirst($already->status) }})</div>
                    @endif
                  </td>

                  <td class="text-center">
                    <input type="checkbox"
                           class="form-check-input toggle-done"
                           name="status[{{ $a->id }}]"
                           data-row="{{ $a->id }}"
                           aria-label="Aktifkan Done untuk {{ $a->name }}"
                           @if($isDone) checked @endif>
                  </td>

                  <td>
                    <div class="d-flex gap-2 align-items-start">
                      <div style="width:90px">
                        @if($beforeExists)
                          @php $url = Storage::disk('public')->url($already->before_photo); @endphp
                          <a href="{{ $url }}" target="_blank">
                            <img src="{{ $url }}" class="img-fluid rounded border" alt="before">
                          </a>
                        @endif
                      </div>
                      <input type="file"
                             name="before_photo[{{ $a->id }}]"
                             class="form-control file-before"
                             data-row="{{ $a->id }}"
                             @if(!$isDone) disabled @endif>
                    </div>
                  </td>

                  <td>
                    <div class="d-flex gap-2 align-items-start">
                      <div style="width:90px">
                        @if($afterExists)
                          @php $url = Storage::disk('public')->url($already->after_photo); @endphp
                          <a href="{{ $url }}" target="_blank">
                            <img src="{{ $url }}" class="img-fluid rounded border" alt="after">
                          </a>
                        @endif
                      </div>
                      <input type="file"
                             name="after_photo[{{ $a->id }}]"
                             class="form-control file-after"
                             data-row="{{ $a->id }}"
                             @if(!$isDone) disabled @endif>
                    </div>
                  </td>

                  <td>
                    <textarea name="note[{{ $a->id }}]"
                              class="form-control"
                              rows="1"
                              placeholder="opsional"
                              data-row="{{ $a->id }}"
                              @if(!$isDone) disabled @endif>{{ $already->note ?? '' }}</textarea>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer d-flex flex-wrap gap-2">
        <button class="btn btn-primary" name="finish" value="0">Simpan</button>
        <button class="btn btn-success" name="finish" value="1">Simpan &amp; Selesaikan</button>
      </div>
    </form>
  </div>

  @if($items->count())
    <div class="card mt-3">
      <div class="card-header"><h3 class="mb-0">Item yang sudah tersimpan</h3></div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr><th>Waktu</th><th>Aktivitas</th><th>Status</th><th>Star</th><th>Before</th><th>After</th></tr>
            </thead>
            <tbody>
              @foreach($items as $it)
                <tr>
                  <td data-label="Waktu">{{ $it->submitted_at }}</td>
                  <td data-label="Aktivitas">{{ $it->activity->name ?? '-' }}</td>
                  <td data-label="Status">{{ ucfirst($it->status) }}</td>
                  <td data-label="Poin">{{ $it->point_earned }}</td>
                  <td data-label="Before">
                    @if($it->before_photo && Storage::disk('public')->exists($it->before_photo))
                      @php $url = Storage::disk('public')->url($it->before_photo); @endphp
                      <a href="{{ $url }}" target="_blank">
                        <img src="{{ $url }}" width="80" class="img-thumbnail" alt="before">
                      </a>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td data-label="After">
                    @if($it->after_photo && Storage::disk('public')->exists($it->after_photo))
                      @php $url = Storage::disk('public')->url($it->after_photo); @endphp
                      <a href="{{ $url }}" target="_blank">
                        <img src="{{ $url }}" width="80" class="img-thumbnail" alt="after">
                      </a>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  // Enable/disable file & note saat Done dicentang
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
