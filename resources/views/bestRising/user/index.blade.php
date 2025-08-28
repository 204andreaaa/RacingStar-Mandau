@extends('layouts.userapp')

@section('main')
@php
    $userName = $userName ?? (auth()->user()->nama ?? 'di Racing Star');
    $nocUrl   = route('checklists.start', ['team' => 'NOC']);
    $serpoUrl = route('checklists.start', ['team' => 'SERPO']);
    $helpUrl  = $helpUrl  ?? '#';

    // --- tentukan team dari session:kategori_nama ---
    $sess = session('auth_user') ?? [];
    $kat  = strtolower((string)($sess['kategori_nama'] ?? '')); // contoh: "Serpo", "NOC", "Serpo User", dst.

    $currentTeam = null;
    if ($kat !== '') {
        if (strpos($kat, 'serpo') !== false) {
            $currentTeam = 'SERPO';
        } elseif (strpos($kat, 'noc') !== false) {
            $currentTeam = 'NOC';
        }
    }

    // kalau ketemu → tampilkan hanya itu; kalau tidak → tampilkan dua-duanya
    $allowed = $currentTeam ? [$currentTeam] : ['NOC','SERPO'];
@endphp

<div class="content-wrapper">
    <section class="content-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div>
                <h1 class="mb-1">Selamat datang, {{ $userName }}</h1>
                <div class="text-muted">
                    <span id="greeting">Halo!</span> •
                    <span id="todayLabel"></span> •
                    <span id="clock"></span>
                </div>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="{{ $helpUrl }}" class="btn btn-outline-success">
                    <i class="fas fa-question-circle mr-1"></i> Bantuan
                </a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body select-area">
                    <div class="text-center mb-4">
                        <div class="h5 text-muted mb-1">Silakan Lanjutkan</div>
                    </div>

                    <div class="row justify-content-center g-3">
                        {{-- NOC TEAM --}}
                        @if(in_array('NOC', $allowed))
                        <div class="col-12 col-md-5 col-lg-4 d-flex justify-content-center">
                            <a href="{{ $nocUrl }}" class="select-card">
                                <div class="select-icon"><i class="fas fa-network-wired"></i></div>
                                <div class="select-title">NOC TEAM</div>
                                <div class="select-sub">Monitoring & koordinasi</div>
                            </a>
                        </div>
                        @endif

                        {{-- SERPO TEAM --}}
                        @if(in_array('SERPO', $allowed))
                        <div class="col-12 col-md-5 col-lg-4 d-flex justify-content-center">
                            <a href="{{ $serpoUrl }}" class="select-card">
                                <div class="select-icon"><i class="fas fa-tools"></i></div>
                                <div class="select-title">SERPO TEAM</div>
                                <div class="select-sub">On-site & maintenance</div>
                            </a>
                        </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </section>
</div>


<style>
/* area kotak besar biar lapang seperti di contoh */
.select-area{
    min-height: 380px;
    display:flex; flex-direction:column; justify-content:center;
}

/* kartu pilihan */
.select-card{
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:10px; width: 340px; min-height: 190px; text-decoration:none;
    border:2px dashed #c9d1d9; border-radius:16px; background:#fff; padding:22px 18px;
    transition: all .2s ease; box-shadow: 0 2px 6px rgba(0,0,0,.04);
}
.select-card:hover{
    transform: translateY(-2px);
    border-color:#28a745; box-shadow:0 8px 22px rgba(40,167,69,.18);
}

/* ikon bulat */
.select-icon{
    width:72px; height:72px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    background:#eaf6ee;
}
.select-icon i{ font-size:30px; color:#28a745; }

/* teks */
.select-title{ font-weight:700; color:#2b2f33; letter-spacing:.5px; }
.select-sub{ font-size:.9rem; color:#6b7280; }

@media (max-width: 575.98px){
    .select-card{ width:100%; }
}
</style>

<script>
// salam, tanggal & jam live
(function(){
    const g = document.getElementById('greeting');
    const c = document.getElementById('clock');
    const t = document.getElementById('todayLabel');

    function greet(){
        const h = new Date().getHours();
        g.textContent = (h<11?'Selamat pagi':h<15?'Selamat siang':h<19?'Selamat sore':'Selamat malam');
    }
    function tick(){
        const d = new Date(), pad = n => n.toString().padStart(2,'0');
        c.textContent = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    }
    function today(){
        const d=new Date(), opts={weekday:'long', year:'numeric', month:'long', day:'numeric'};
        t.textContent = d.toLocaleDateString('id-ID', opts);
    }
    greet(); today(); tick(); setInterval(tick,1000);
})();
</script>
@endsection
