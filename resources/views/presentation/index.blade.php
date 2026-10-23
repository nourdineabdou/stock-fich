<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Présentation — {{ \App\Models\CompanySetting::get('company_name', 'FreezePro') }}</title>
{{-- Page publique (sans connexion) : elle ne reutilise pas layouts.app, qui exige un utilisateur connecte --}}
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { theme: { extend: { colors: {
        primary: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' },
    } } } }
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Rubik:wght@400..700&family=Public+Sans:wght@400..700&family=Cairo:wght@400..800&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Inter', sans-serif; }
    [x-cloak] { display: none !important; }
    .pz-stage { width: 1920px; height: 1080px; transform-origin: top left; position: absolute; top: 0; left: 0; }
    .pz-slide { position: absolute; inset: 0; padding: 128px; display: flex; flex-direction: column; gap: 48px; overflow: hidden; }
    .pz-slide[dir="rtl"] { font-family: 'Cairo', Tahoma, sans-serif !important; }
    .pz-slide[dir="rtl"] .pz-head { font-family: 'Cairo', Tahoma, sans-serif !important; letter-spacing: 0 !important; }
    .pz-slide[dir="rtl"] .pz-eyebrow { letter-spacing: 0 !important; text-transform: none !important; }
    .pz-light { background: #F3F6F7; color: #13232E; font-family: 'Public Sans', Arial, sans-serif; }
    .pz-dark { background: linear-gradient(135deg, #0E2A3B 0%, #12384E 100%); color: #F3F6F7; font-family: 'Public Sans', Arial, sans-serif; }
    .pz-head { font-family: 'Rubik', Arial, sans-serif; font-weight: 600; font-size: 72px; line-height: 1.2; }
    .pz-eyebrow { font-size: 26px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: #0F7EA3; }
    .pz-dark .pz-eyebrow { color: #5CC8E8; }
    .pz-card { background: #FFFFFF; border: 1px solid #DCE5E9; border-radius: 20px; padding: 40px; display: flex; flex-direction: column; gap: 16px; }
    .pz-muted { color: #4A5B66; }
    .pz-frame:fullscreen { background: #0E2A3B; }
</style>
</head>
<body class="bg-slate-100 min-h-screen">
@php $slides = \App\Support\PresentationSlides::all(); $count = count($slides['fr']); @endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6" x-data="{
        lang: (() => { const q = new URLSearchParams(location.search).get('lang'); if (q === 'ar' || q === 'fr') return q; try { return localStorage.getItem('pz-lang') || 'fr' } catch (e) { return 'fr' } })(),
        i: Math.max(0, Math.min({{ $count - 1 }}, (parseInt(new URLSearchParams(location.search).get('slide')) || 1) - 1)),
        total: {{ $count }}, scale: 1, offsetX: 0, offsetY: 0,
        fit() {
            const f = this.$refs.frame, full = document.fullscreenElement === f;
            const w = f.clientWidth, h = full ? f.clientHeight : w * 1080 / 1920;
            this.scale = Math.min(w / 1920, h / 1080);
            this.offsetX = (w - 1920 * this.scale) / 2;
            this.offsetY = full ? (h - 1080 * this.scale) / 2 : 0;
        },
        go(n) { this.i = Math.max(0, Math.min(this.total - 1, n)); },
        setLang(l) { this.lang = l; try { localStorage.setItem('pz-lang', l) } catch (e) {} },
        full() { document.fullscreenElement ? document.exitFullscreen() : this.$refs.frame.requestFullscreen(); },
     }"
     x-init="$nextTick(() => fit()); new ResizeObserver(() => fit()).observe($refs.frame); document.addEventListener('fullscreenchange', () => $nextTick(() => fit()))"
     @keydown.window.arrow-right.prevent="lang === 'ar' ? go(i - 1) : go(i + 1)"
     @keydown.window.arrow-left.prevent="lang === 'ar' ? go(i + 1) : go(i - 1)"
     @keydown.window.space.prevent="go(i + 1)">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-3">
            <div class="h-11 w-11 rounded-xl bg-gradient-to-br from-sky-400 to-primary-600 flex items-center justify-center shadow">
                <i class="fas fa-snowflake text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">{{ \App\Models\CompanySetting::get('company_name', 'FreezePro') }} — Présentation</h1>
                <p class="text-sm text-slate-500">Flèches du clavier pour avancer · plein écran pour présenter</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @auth
            <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="fas fa-gauge-high mr-1"></i> Ouvrir l'application</a>
            @else
            <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="fas fa-right-to-bracket mr-1"></i> Se connecter</a>
            @endauth
            <div class="flex rounded-xl border border-slate-200 bg-white p-0.5 text-sm font-semibold">
                <button type="button" @click="setLang('fr')" :class="lang === 'fr' ? 'bg-primary-600 text-white' : 'text-slate-600'" class="px-4 py-1.5 rounded-lg">Français</button>
                <button type="button" @click="setLang('ar')" :class="lang === 'ar' ? 'bg-primary-600 text-white' : 'text-slate-600'" class="px-4 py-1.5 rounded-lg" style="font-family:'Cairo',Tahoma,sans-serif">العربية</button>
            </div>
            <button type="button" @click="full()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold"><i class="fas fa-expand mr-1"></i> Plein écran</button>
        </div>
    </div>

    {{-- Scene 16:9 --}}
    <div x-ref="frame" class="pz-frame relative w-full overflow-hidden rounded-2xl shadow-lg bg-slate-900" :style="`height:${1080 * scale}px`" @click="go(i + 1)">
        <div class="pz-stage" :style="`transform: translate(${offsetX}px, ${offsetY}px) scale(${scale})`">
            @foreach($slides as $lang => $list)
            @php $dir = $lang === 'ar' ? 'rtl' : 'ltr'; @endphp
            @foreach($list as $idx => $s)
            <div x-show="lang === '{{ $lang }}' && i === {{ $idx }}" x-transition.opacity.duration.250ms dir="{{ $dir }}" style="text-align:start"
                 class="pz-slide {{ in_array($s['type'], ['cover', 'end']) || ($s['type'] === 'table') ? 'pz-dark' : 'pz-light' }}">
                @switch($s['type'])

                @case('cover')
                    <div style="display:flex;flex-direction:column;justify-content:center;gap:36px;height:100%">
                        <p class="pz-eyebrow" style="font-size:30px">{{ $s['eyebrow'] }}</p>
                        <p class="pz-head" style="font-size:160px;font-weight:700;line-height:1.05">{{ $s['title'] }}</p>
                        <div style="width:160px;height:6px;background:#5CC8E8"></div>
                        <p style="font-size:50px;line-height:1.4;color:#C7DAE2;max-width:1300px">{{ $s['subtitle'] }}</p>
                    </div>
                    <p style="position:absolute;bottom:64px;inset-inline-start:128px;font-size:26px;color:#8FB0BE">{{ $s['footer'] }}</p>
                    @break

                @case('cards')
                    <p class="pz-eyebrow">{{ $s['eyebrow'] }}</p>
                    <p class="pz-head" style="max-width:1550px">{{ $s['title'] }}</p>
                    <div style="display:flex;gap:32px">
                        @foreach($s['cards'] as $c)
                        <div class="pz-card" style="flex:1;{{ !empty($c['dark']) ? 'background:#0E2A3B;color:#F3F6F7;border:none' : '' }}">
                            <i class="fas {{ $c['icon'] }}" style="font-size:48px;color:{{ !empty($c['dark']) ? '#5CC8E8' : '#0F7EA3' }}"></i>
                            <p style="font-size:38px;font-weight:700">{{ $c['title'] }}</p>
                            <p style="font-size:30px;line-height:1.45;color:{{ !empty($c['dark']) ? '#C7DAE2' : '#4A5B66' }}">{{ $c['text'] }}</p>
                        </div>
                        @endforeach
                    </div>
                    @if(!empty($s['note']))<p class="pz-muted" style="font-size:30px;line-height:1.45">{{ $s['note'] }}</p>@endif
                    @break

                @case('steps')
                    <p class="pz-eyebrow">{{ $s['eyebrow'] }}</p>
                    <p class="pz-head">{{ $s['title'] }}</p>
                    <div style="display:flex;gap:20px">
                        @foreach($s['steps'] as $n => [$title, $text])
                        @php $last = $loop->last; @endphp
                        <div style="flex:1;display:flex;flex-direction:column;gap:12px;padding:32px 24px;border-radius:18px;background:{{ $last ? '#0E2A3B' : '#FFFFFF' }};border-top:8px solid {{ $last ? '#5CC8E8' : '#1C9BC2' }}">
                            <p style="font-family:'Rubik',Arial,sans-serif;font-size:56px;font-weight:700;color:{{ $last ? '#5CC8E8' : '#1C9BC2' }}">{{ $n + 1 }}</p>
                            <p style="font-size:31px;font-weight:700;color:{{ $last ? '#F3F6F7' : '#13232E' }}">{{ $title }}</p>
                            <p style="font-size:25px;line-height:1.4;color:{{ $last ? '#C7DAE2' : '#4A5B66' }}">{{ $text }}</p>
                        </div>
                        @endforeach
                    </div>
                    <p class="pz-muted" style="font-size:30px;line-height:1.45">{{ $s['note'] }}</p>
                    @break

                @case('split')
                    <p class="pz-eyebrow">{{ $s['eyebrow'] }}</p>
                    <p class="pz-head">{{ $s['title'] }}</p>
                    <div style="display:flex;gap:56px;align-items:{{ $s['panel']['kind'] === 'big' ? 'center' : 'flex-start' }}">
                        <div style="flex:1;display:flex;flex-direction:column;gap:26px">
                            @foreach($s['points'] as [$label, $text])
                            <p style="font-size:31px;line-height:1.45"><b>{{ $label }}{{ $dir === 'rtl' ? ':' : ' :' }}</b> {{ $text }}</p>
                            @endforeach
                        </div>
                        @php $p = $s['panel']; $tone = ['blue' => ['#0F7EA3', '#1C9BC2'], 'green' => ['#1F7A50', '#2E9E6B'], 'red' => ['#B4302C', '#D9534F'], '' => ['#13232E', '#DCE5E9']]; @endphp
                        @if($p['kind'] === 'rows')
                        <div class="pz-card" style="width:680px;gap:20px">
                            <p class="pz-muted" style="font-size:26px;font-weight:700">{{ $p['title'] }}</p>
                            @foreach($p['rows'] as $row)
                                @if(count($row) === 1)
                                <div style="height:2px;background:#DCE5E9"></div>
                                @else
                                <div style="display:flex;justify-content:space-between;gap:16px"><p style="font-size:30px">{{ $row[0] }}</p><p style="font-size:30px;font-weight:800;color:{{ $tone[$row[2]][0] }}">{{ $row[1] }}</p></div>
                                @endif
                            @endforeach
                            <p class="pz-muted" style="font-size:26px;line-height:1.45;background:#E8F4F8;padding:20px;border-radius:12px">{{ $p['note'] }}</p>
                        </div>
                        @elseif($p['kind'] === 'states')
                        <div style="width:660px;display:flex;flex-direction:column;gap:18px">
                            <p class="pz-muted" style="font-size:26px;font-weight:700">{{ $p['title'] }}</p>
                            @foreach($p['rows'] as [$range, $label, $color])
                            <div style="display:flex;align-items:center;gap:24px;background:#FFFFFF;padding:26px 28px;border-radius:18px;border-inline-start:12px solid {{ $tone[$color][1] }}">
                                <p style="font-family:'Rubik',Arial,sans-serif;font-size:34px;font-weight:700;width:250px;color:{{ $tone[$color][0] }}">{{ $range }}</p>
                                <p style="font-size:30px">{{ $label }}</p>
                            </div>
                            @endforeach
                            <p class="pz-muted" style="font-size:25px">{{ $p['note'] }}</p>
                        </div>
                        @else
                        <div style="width:620px;display:flex;flex-direction:column;gap:12px;background:#0E2A3B;color:#F3F6F7;padding:48px;border-radius:24px">
                            <p style="font-size:30px;color:#C7DAE2">{{ $p['top'] }}</p>
                            <p style="font-family:'Rubik',Arial,sans-serif;font-size:160px;font-weight:700;line-height:1;color:#5CC8E8">{{ $p['number'] }}</p>
                            <p style="font-size:34px;font-weight:700">{{ $p['sub'] }}</p>
                            <p style="font-size:25px;color:#8FB0BE">{{ $p['note'] }}</p>
                        </div>
                        @endif
                    </div>
                    @break

                @case('table')
                    <p class="pz-eyebrow">{{ $s['eyebrow'] }}</p>
                    <p class="pz-head">{{ $s['title'] }}</p>
                    <p style="font-size:32px;line-height:1.45;color:#C7DAE2;max-width:1500px">{{ $s['intro'] }}</p>
                    <table style="width:100%;font-size:30px;border-collapse:collapse">
                        <tr style="border-bottom:2px solid #5CC8E8">
                            @foreach($s['headers'] as $h => $head)
                            <th style="padding:14px 18px;font-weight:700;color:#5CC8E8;text-align:{{ $h === 0 ? 'start' : 'end' }};width:{{ $h === 0 ? '34%' : '22%' }}">{{ $head }}</th>
                            @endforeach
                        </tr>
                        @foreach($s['rows'] as $row)
                        <tr style="border-bottom:1px solid rgba(199,218,226,.25)">
                            @foreach($row as $c => $cell)
                            <td style="padding:16px 18px;text-align:{{ $c === 0 ? 'start' : 'end' }};{{ $c === 3 ? 'font-weight:700' : '' }}">{{ $cell }}</td>
                            @endforeach
                        </tr>
                        @endforeach
                    </table>
                    <p style="font-size:27px;color:#8FB0BE">{{ $s['note'] }}</p>
                    @break

                @case('grid')
                    <p class="pz-eyebrow">{{ $s['eyebrow'] }}</p>
                    <p class="pz-head">{{ $s['title'] }}</p>
                    <div style="display:grid;grid-template-columns:repeat({{ $s['columns'] }}, 1fr);gap:22px">
                        @foreach($s['items'] as [$title, $text])
                        <div class="pz-card" style="padding:30px;gap:8px">
                            <p style="font-size:33px;font-weight:700">{{ $title }}</p>
                            <p class="pz-muted" style="font-size:26px;line-height:1.4">{{ $text }}</p>
                        </div>
                        @endforeach
                    </div>
                    @if(!empty($s['note']))<p class="pz-muted" style="font-size:30px;line-height:1.45">{!! $s['note'] !!}</p>@endif
                    @break

                @case('end')
                    <div style="display:flex;flex-direction:column;justify-content:center;gap:40px;height:100%">
                        <p class="pz-head" style="font-size:96px;font-weight:700;line-height:1.2;max-width:1500px">{{ $s['title'] }}</p>
                        <div style="width:160px;height:6px;background:#5CC8E8"></div>
                        <p style="font-size:46px;color:#C7DAE2">{{ $s['subtitle'] }}</p>
                    </div>
                    @break
                @endswitch

                <p style="position:absolute;bottom:40px;inset-inline-end:64px;font-size:24px;color:{{ in_array($s['type'], ['cover', 'end', 'table']) ? '#8FB0BE' : '#4A5B66' }}"><span dir="ltr">{{ $idx + 1 }} / {{ $count }}</span></p>
            </div>
            @endforeach
            @endforeach
        </div>
    </div>

    {{-- Navigation --}}
    <div class="mt-4 flex items-center justify-between gap-3">
        <button type="button" @click="go(i - 1)" :disabled="i === 0" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 disabled:opacity-40"><i class="fas fa-arrow-left mr-1"></i> Précédent</button>
        <div class="flex gap-1.5">
            <template x-for="n in total" :key="n">
                <button type="button" @click="go(n - 1)" class="h-2.5 rounded-full transition-all" :class="i === n - 1 ? 'w-8 bg-primary-600' : 'w-2.5 bg-slate-300 hover:bg-slate-400'" :aria-label="`Diapositive ${n}`"></button>
            </template>
        </div>
        <button type="button" @click="go(i + 1)" :disabled="i === total - 1" class="px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold disabled:opacity-40">Suivant <i class="fas fa-arrow-right ml-1"></i></button>
    </div>
</div>
</body>
</html>
