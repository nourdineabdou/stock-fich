<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\CompanySetting::get('company_name', 'FreezePro') }} — Connexion</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: { 400:'#38bdf8', 500:'#0ea5e9', 600:'#0284c7', 700:'#0369a1', 800:'#075985', 900:'#0c4a6e' },
                        ice:     { 50:'#f0f9ff', 100:'#e0f2fe', 200:'#bae6fd', 300:'#7dd3fc', 400:'#38bdf8' }
                    },
                    backgroundImage: {
                        'cold-gradient': 'linear-gradient(135deg, #0c4a6e 0%, #0369a1 40%, #0ea5e9 80%, #38bdf8 100%)',
                        'glass': 'linear-gradient(135deg, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0.04) 100%)',
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'float-delayed': 'float 6s ease-in-out 2s infinite',
                        'float-slow': 'float 8s ease-in-out 1s infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4,0,0.6,1) infinite',
                        'shimmer': 'shimmer 2.5s linear infinite',
                    },
                    keyframes: {
                        float: { '0%,100%': {transform:'translateY(0px)'}, '50%': {transform:'translateY(-20px)'} },
                        shimmer: { '0%': {backgroundPosition:'-200% 0'}, '100%': {backgroundPosition:'200% 0'} },
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .cold-bg {
            background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 35%, #0284c7 65%, #0ea5e9 100%);
            min-height: 100vh;
        }
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%,100% { transform: translateY(0px) rotate(0deg); opacity: 0.6; }
            50% { transform: translateY(-25px) rotate(180deg); opacity: 1; }
        }
        @keyframes snowfall {
            0% { transform: translateY(-10px) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 0.8; }
            100% { transform: translateY(100vh) rotate(360deg); opacity: 0; }
        }
        .snowflake {
            position: absolute;
            top: -10px;
            color: rgba(255,255,255,0.5);
            font-size: 14px;
            animation: snowfall linear infinite;
            user-select: none;
            pointer-events: none;
        }
        .glass-card {
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .input-field {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.9rem;
            color: #1e293b;
            background: #f8fafc;
            transition: all 0.2s ease;
            outline: none;
        }
        .input-field:focus {
            border-color: #0ea5e9;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(14,165,233,0.12);
        }
        .btn-login {
            width: 100%;
            padding: 0.85rem 1.5rem;
            background: linear-gradient(135deg, #0369a1, #0ea5e9);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.025em;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-login::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s ease;
        }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(14,165,233,0.4); }
        .btn-login:hover::after { left: 100%; }
        .btn-login:active { transform: translateY(0); }
        .logo-ring {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, #0c4a6e, #0ea5e9);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 24px rgba(14,165,233,0.4), inset 0 1px 0 rgba(255,255,255,0.2);
        }
        .feature-badge {
            display: flex; align-items: center; gap: 0.5rem;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            backdrop-filter: blur(4px);
        }
    </style>
</head>
<body>
<div class="cold-bg relative overflow-hidden" style="min-height:100vh;">

    <!-- Background particles -->
    <div class="particle" style="width:300px;height:300px;top:-100px;left:-80px;animation-duration:8s;"></div>
    <div class="particle" style="width:200px;height:200px;bottom:100px;right:-50px;animation-duration:6s;animation-delay:2s;"></div>
    <div class="particle" style="width:150px;height:150px;top:50%;left:55%;animation-duration:7s;animation-delay:1s;"></div>
    <div class="particle" style="width:80px;height:80px;top:20%;right:30%;animation-duration:5s;animation-delay:3s;"></div>
    <div class="particle" style="width:120px;height:120px;bottom:20%;left:15%;animation-duration:9s;animation-delay:0.5s;"></div>

    <!-- Snowflakes -->
    @for($i = 0; $i < 18; $i++)
    <div class="snowflake" style="left:{{ rand(0,100) }}%;animation-duration:{{ rand(6,14) }}s;animation-delay:{{ rand(0,8) }}s;font-size:{{ rand(10,18) }}px;">❄</div>
    @endfor

    <!-- Main layout: Left panel + Right form -->
    <div class="flex min-h-screen">

        {{-- ── LEFT PANEL ──────────────────────────────────────────────────── --}}
        <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 flex-col justify-between p-12 relative">

            <!-- Top: Logo + Name -->
            <div class="flex items-center gap-4">
                @php $logo = \App\Models\CompanySetting::get('company_logo'); @endphp
                @if($logo)
                    <img src="{{ asset('storage/'.$logo) }}" alt="Logo" class="h-12 w-12 rounded-xl object-cover shadow-lg">
                @else
                    <div class="logo-ring">
                        <i class="fas fa-snowflake text-white text-2xl"></i>
                    </div>
                @endif
                <div>
                    <h1 class="text-white font-bold text-2xl tracking-tight">{{ \App\Models\CompanySetting::get('company_name', 'FreezePro') }}</h1>
                    <p class="text-sky-200 text-sm font-medium">Système de gestion intégré</p>
                </div>
            </div>

            <!-- Center: Hero text -->
            <div class="space-y-6">
                <div>
                    <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 rounded-full px-4 py-1.5 text-sky-200 text-xs font-semibold uppercase tracking-widest mb-4">
                        <i class="fas fa-thermometer-half text-sky-300"></i>
                        Gestion Frigorifique Professionnelle
                    </div>
                    <h2 class="text-white text-4xl xl:text-5xl font-extrabold leading-tight">
                        Contrôlez chaque<br>
                        <span class="text-transparent bg-clip-text" style="background: linear-gradient(90deg, #7dd3fc, #bae6fd);">
                            étape de la chaîne<br>du froid
                        </span>
                    </h2>
                    <p class="text-sky-200 text-lg mt-4 leading-relaxed opacity-80">
                        De l'arrivage à la livraison — traçabilité totale,<br>gestion des stocks et facturation en un seul outil.
                    </p>
                </div>

                <!-- Feature badges -->
                <div class="grid grid-cols-2 gap-3 max-w-sm">
                    <div class="feature-badge text-white text-sm">
                        <i class="fas fa-fish text-sky-300 text-xs"></i>
                        <span>Arrivages & Traitement</span>
                    </div>
                    <div class="feature-badge text-white text-sm">
                        <i class="fas fa-boxes text-sky-300 text-xs"></i>
                        <span>Gestion des stocks</span>
                    </div>
                    <div class="feature-badge text-white text-sm">
                        <i class="fas fa-truck text-sky-300 text-xs"></i>
                        <span>Livraisons & BL</span>
                    </div>
                    <div class="feature-badge text-white text-sm">
                        <i class="fas fa-chart-bar text-sky-300 text-xs"></i>
                        <span>Rapports & Analyses</span>
                    </div>
                </div>
            </div>

            <!-- Bottom: Stats -->
            <div class="flex items-center gap-8">
                <div class="text-center">
                    <div class="text-white font-bold text-2xl">−18°C</div>
                    <div class="text-sky-300 text-xs mt-1">Temp. cible</div>
                </div>
                <div class="w-px h-10 bg-white/20"></div>
                <div class="text-center">
                    <div class="text-white font-bold text-2xl">100%</div>
                    <div class="text-sky-300 text-xs mt-1">Traçabilité</div>
                </div>
                <div class="w-px h-10 bg-white/20"></div>
                <div class="text-center">
                    <div class="text-white font-bold text-2xl">24/7</div>
                    <div class="text-sky-300 text-xs mt-1">Surveillance</div>
                </div>
            </div>
        </div>

        {{-- ── RIGHT PANEL: FORM ────────────────────────────────────────────── --}}
        <div class="w-full lg:w-1/2 xl:w-2/5 flex items-center justify-center p-6 sm:p-10">
            <div class="glass-card w-full max-w-md rounded-3xl shadow-2xl p-8 sm:p-10">

                <!-- Mobile: Logo + Name (shown only on small screens) -->
                <div class="flex lg:hidden items-center gap-3 mb-8">
                    @if($logo ?? false)
                        <img src="{{ asset('storage/'.$logo) }}" alt="Logo" class="h-10 w-10 rounded-xl object-cover">
                    @else
                        <div style="width:44px;height:44px;background:linear-gradient(135deg,#0c4a6e,#0ea5e9);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-snowflake text-white text-lg"></i>
                        </div>
                    @endif
                    <div>
                        <h1 class="font-bold text-slate-800 text-lg">{{ \App\Models\CompanySetting::get('company_name', 'FreezePro') }}</h1>
                        <p class="text-slate-400 text-xs">Gestion frigorifique</p>
                    </div>
                </div>

                <!-- Form header -->
                <div class="mb-8">
                    <h2 class="text-2xl font-extrabold text-slate-800">Connexion</h2>
                    <p class="text-slate-400 text-sm mt-1">Accédez à votre espace de gestion</p>
                </div>

                {{ $slot }}

                <!-- Footer -->
                <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                    <p class="text-slate-400 text-xs">
                        &copy; {{ date('Y') }} {{ \App\Models\CompanySetting::get('company_name', 'FreezePro') }} —
                        Tous droits réservés
                    </p>
                    <p class="text-slate-300 text-xs mt-1">
                        <i class="fas fa-shield-alt mr-1"></i>Connexion sécurisée SSL
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
