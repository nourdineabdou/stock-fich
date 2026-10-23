<?php

namespace App\Support;

/**
 * Contenu de la presentation de l'application (page « Presentation »), en francais et en arabe.
 * Chaque diapositive a un « type » qui choisit sa mise en page dans resources/views/presentation/index.blade.php.
 */
class PresentationSlides
{
    public static function all(): array
    {
        return ['fr' => self::fr(), 'ar' => self::ar()];
    }

    private static function fr(): array
    {
        return [
            ['type' => 'cover', 'eyebrow' => 'Gestion frigorifique', 'title' => 'FreezePro',
                'subtitle' => 'De l’arrivage du poisson jusqu’à la facture : une seule application pour tout suivre.',
                'footer' => 'Présentation de l’application · Arrivage · Traitement · Tunnels · Stock · Facturation'],

            ['type' => 'cards', 'eyebrow' => 'Pourquoi FreezePro', 'title' => 'Tout le travail du frigorifique, au même endroit, pour chaque client.',
                'cards' => [
                    ['icon' => 'fa-magnifying-glass', 'title' => 'Traçabilité', 'text' => 'Chaque kilo suivi de l’arrivage à la livraison.'],
                    ['icon' => 'fa-database', 'title' => 'Stock clair', 'text' => 'Le stock de chaque client par poisson et par carton.'],
                    ['icon' => 'fa-chart-column', 'title' => 'Facturation juste', 'text' => 'Traitement et froid calculés automatiquement.'],
                    ['icon' => 'fa-users', 'title' => 'Équipe organisée', 'text' => 'Chaque rôle voit seulement ce qui le concerne.'],
                ]],

            ['type' => 'steps', 'eyebrow' => 'Le parcours', 'title' => 'Sept étapes, de la balance au client',
                'steps' => [
                    ['Arrivage', 'Pesée du poisson reçu'], ['Traitement', 'Filtrage et tri par type'],
                    ['Chariots', 'Chargement du poisson'], ['Tunnel', 'Congélation 1 à 3 jours'],
                    ['Cartonnage', 'Cartons par poisson'], ['Stock', 'Chambre froide du client'],
                    ['Livraison', 'Retrait par le client'],
                ],
                'note' => 'À chaque étape, l’application enregistre les poids, imprime le document utile et prépare la suivante.'],

            ['type' => 'split', 'eyebrow' => 'Étape 2 · Traitement', 'title' => 'Filtrer, trier, puis remettre la fiche au client',
                'points' => [
                    ['Filtrage', 'on retire la partie non utilisable.'],
                    ['Tri', 'le reste est séparé par type de poisson, en kg.'],
                    ['Frais', 'un seul prix par kg pour toute la quantité triée.'],
                    ['Fiche A4', 'types, kg, frais et total, prête à imprimer.'],
                ],
                'panel' => ['kind' => 'rows', 'title' => 'Exemple', 'rows' => [
                    ['Poids reçu', '4 000 kg', ''], ['Non utilisable', '10 kg', 'red'], ['—'],
                    ['Poisson trié', '3 990 kg', 'blue'], ['Frais de traitement', '[__] MRU / kg', ''],
                ], 'note' => 'Total = 3 990 kg × prix au kg. Facture de traitement séparée du froid.']],

            ['type' => 'split', 'eyebrow' => 'Étapes 3 et 4 · Chariots et tunnel', 'title' => 'Charger, congeler, et savoir quand sortir',
                'points' => [
                    ['Choix des chariots', 'le système propose ceux qui couvrent le poids.'],
                    ['Bon de chargement', 'un tableau simple, chariot par chariot, pour les ouvriers.'],
                    ['Placement', 'on met les chariots dans le tunnel, sans dépasser sa capacité.'],
                    ['Sortie', 'un clic sort les chariots et débloque le cartonnage.'],
                ],
                'panel' => ['kind' => 'states', 'title' => 'Temps dans le tunnel, en direct', 'rows' => [
                    ['< 24 h', 'En congélation', 'blue'], ['24–72 h', 'Prêt à sortir', 'green'], ['> 72 h', 'Dépassé, à sortir', 'red'],
                ], 'note' => 'Durées réglables dans les paramètres.']],

            ['type' => 'split', 'eyebrow' => 'Étapes 5 à 7 · Cartonnage, stock, livraison', 'title' => 'Le stock de chaque client, au carton près',
                'points' => [
                    ['Cartonnage', 'le nombre de cartons se calcule à partir du poids trié.'],
                    ['Fiche client', 'stock par poisson et par type de carton.'],
                    ['Historique', 'entrées et retraits, filtrés par période, date, poisson ou carton.'],
                    ['Livraison', 'le client reprend ses cartons, le stock baisse, un bon est imprimé.'],
                ],
                'panel' => ['kind' => 'big', 'top' => 'Poulpe · Carton 16 kg', 'number' => '28', 'sub' => 'cartons · 448 kg', 'note' => 'Exemple de ligne sur la fiche client']],

            ['type' => 'table', 'eyebrow' => 'Frais de froid', 'title' => 'Chaque semaine, le froid suit le stock réel',
                'intro' => 'Quantité de la semaine = stock en début de semaine + entrées. Multipliée par le tarif du client, par exemple 40 ou 50 MRU.',
                'headers' => ['Semaine (exemple, 40 MRU / carton)', 'Cartons facturés', 'Montant', 'Cumul'],
                'rows' => [
                    ['Semaine 1 · entrée de 28 cartons', '28', '1 120 MRU', '1 120 MRU'],
                    ['Semaine 2 · retrait de 8 cartons', '28', '1 120 MRU', '2 240 MRU'],
                    ['Semaine 3', '20', '800 MRU', '3 040 MRU'],
                ],
                'note' => 'Un retrait compte à partir de la semaine suivante. Une facture froid = une ligne par semaine.'],

            ['type' => 'cards', 'eyebrow' => 'Factures et encaissements', 'title' => 'Deux factures séparées, payées en une ou plusieurs fois',
                'cards' => [
                    ['icon' => 'fa-file-invoice', 'title' => 'Facture de traitement', 'text' => 'Kg triés × frais par kg. Une facture par traitement, sans lien avec le froid.'],
                    ['icon' => 'fa-snowflake', 'title' => 'Facture de froid', 'text' => 'Variable : une ligne par semaine, selon le stock présent dans la chambre froide.'],
                    ['icon' => 'fa-hand-holding-dollar', 'title' => 'Encaissement', 'text' => 'Paiement partiel ou total, en espèces, virement, chèque ou mobile. Reçu PDF remis au client.', 'dark' => true],
                ],
                'note' => 'L’application montre toujours ce qui est facturé, encaissé et le reste à payer de chaque client.'],

            ['type' => 'grid', 'eyebrow' => 'Rôles et permissions', 'title' => 'Chacun voit et fait seulement son travail', 'columns' => 4,
                'items' => [
                    ['Super Admin', 'Tout le système'], ['Direction', 'Suivi et décisions'], ['Comptable', 'Factures et encaissements'], ['Facturation', 'Émettre les factures'],
                    ['Traitement', 'Tri, chariots, cartons'], ['Stock', 'Entrées et livraisons'], ['Opérateur tunnel', 'Placer et sortir'], ['Magasinier', 'Chambres froides'],
                ],
                'note' => '<b>Une permission par action</b> : l’administrateur crée ses rôles et coche exactement ce que chacun peut faire.'],

            ['type' => 'grid', 'eyebrow' => 'Documents imprimés', 'title' => 'Le bon papier, au bon moment', 'columns' => 3,
                'items' => [
                    ['Fiche de traitement', 'Pour le client : types, kg, frais'], ['Bon de chargement', 'Pour les ouvriers : chariot par chariot'],
                    ['Reçu d’entrée', 'Les cartons mis en chambre froide'], ['Bon de livraison', 'Les cartons repris par le client'],
                    ['Factures', 'Traitement et froid, avec le reste à payer'], ['Reçu de paiement', 'Remis à chaque encaissement'],
                ]],

            ['type' => 'end', 'title' => 'Chaque kilo suivi, chaque semaine facturée.', 'subtitle' => 'FreezePro · Merci — des questions ?'],
        ];
    }

    private static function ar(): array
    {
        return [
            ['type' => 'cover', 'eyebrow' => 'إدارة التبريد', 'title' => 'FreezePro',
                'subtitle' => 'من وصول السمك إلى الفاتورة: تطبيق واحد لمتابعة كل شيء.',
                'footer' => 'عرض التطبيق · الوصول · المعالجة · الأنفاق · المخزون · الفوترة'],

            ['type' => 'cards', 'eyebrow' => 'لماذا FreezePro', 'title' => 'كل عمل المبرّد في مكان واحد، لكل عميل.',
                'cards' => [
                    ['icon' => 'fa-magnifying-glass', 'title' => 'التتبّع', 'text' => 'كل كيلوغرام متابَع من الوصول إلى التسليم.'],
                    ['icon' => 'fa-database', 'title' => 'مخزون واضح', 'text' => 'مخزون كل عميل حسب نوع السمك ونوع الكرتون.'],
                    ['icon' => 'fa-chart-column', 'title' => 'فوترة دقيقة', 'text' => 'حساب تلقائي لرسوم المعالجة والتبريد.'],
                    ['icon' => 'fa-users', 'title' => 'فريق منظَّم', 'text' => 'كل دور يرى ما يخصّه فقط.'],
                ]],

            ['type' => 'steps', 'eyebrow' => 'المسار', 'title' => 'سبع مراحل، من الميزان إلى العميل',
                'steps' => [
                    ['الوصول', 'وزن السمك المستلَم'], ['المعالجة', 'الفرز والتصنيف حسب النوع'],
                    ['العربات', 'تحميل السمك'], ['النفق', 'تجميد من يوم إلى 3 أيام'],
                    ['التعبئة', 'كراتين لكل نوع'], ['المخزون', 'غرفة تبريد العميل'],
                    ['التسليم', 'استلام العميل'],
                ],
                'note' => 'في كل مرحلة، يسجّل التطبيق الأوزان، ويطبع الوثيقة اللازمة، ويهيّئ المرحلة التالية.'],

            ['type' => 'split', 'eyebrow' => 'المرحلة 2 · المعالجة', 'title' => 'الفرز والتصنيف، ثم تسليم البطاقة للعميل',
                'points' => [
                    ['الفرز', 'نستبعد الجزء غير الصالح.'],
                    ['التصنيف', 'يُقسَّم الباقي حسب نوع السمك بالكيلوغرام.'],
                    ['الرسوم', 'سعر واحد للكيلوغرام لكل الكمية المصنَّفة.'],
                    ['بطاقة A4', 'الأنواع والكميات والرسوم والمجموع، جاهزة للطباعة.'],
                ],
                'panel' => ['kind' => 'rows', 'title' => 'مثال', 'rows' => [
                    ['الوزن المستلَم', '4000 كغ', ''], ['غير صالح', '10 كغ', 'red'], ['—'],
                    ['السمك المصنَّف', '3990 كغ', 'blue'], ['رسوم المعالجة', '[__] أوقية / كغ', ''],
                ], 'note' => 'المجموع = 3990 كغ × السعر للكيلوغرام. فاتورة المعالجة منفصلة عن التبريد.']],

            ['type' => 'split', 'eyebrow' => 'المرحلتان 3 و4 · العربات والنفق', 'title' => 'التحميل، التجميد، ومعرفة وقت الإخراج',
                'points' => [
                    ['اختيار العربات', 'يقترح النظام العربات التي تكفي للوزن.'],
                    ['سند التحميل', 'جدول بسيط، عربة بعربة، للعمّال.'],
                    ['الإدخال', 'توضع العربات في النفق دون تجاوز سعته.'],
                    ['الإخراج', 'نقرة واحدة تُخرج العربات وتفتح التعبئة.'],
                ],
                'panel' => ['kind' => 'states', 'title' => 'الوقت في النفق، مباشرة', 'rows' => [
                    ['أقل من 24 س', 'قيد التجميد', 'blue'], ['من 24 إلى 72 س', 'جاهز للإخراج', 'green'], ['أكثر من 72 س', 'تجاوز المدة، يجب الإخراج', 'red'],
                ], 'note' => 'المدد قابلة للتعديل في الإعدادات.']],

            ['type' => 'split', 'eyebrow' => 'المراحل 5 إلى 7 · التعبئة، المخزون، التسليم', 'title' => 'مخزون كل عميل، بدقّة الكرتونة',
                'points' => [
                    ['التعبئة', 'يُحسب عدد الكراتين انطلاقاً من الوزن المصنَّف.'],
                    ['بطاقة العميل', 'المخزون حسب نوع السمك ونوع الكرتون.'],
                    ['السجلّ', 'الإدخالات والسحوبات، مع تصفية حسب الفترة أو التاريخ أو السمك أو الكرتون.'],
                    ['التسليم', 'يسترجع العميل كراتينه، ينقص المخزون، ويُطبع سند.'],
                ],
                'panel' => ['kind' => 'big', 'top' => 'أخطبوط · كرتون 16 كغ', 'number' => '28', 'sub' => 'كرتونة · 448 كغ', 'note' => 'مثال لسطر في بطاقة العميل']],

            ['type' => 'table', 'eyebrow' => 'رسوم التبريد', 'title' => 'كل أسبوع، يتبع التبريد المخزون الحقيقي',
                'intro' => 'كمية الأسبوع = المخزون في بداية الأسبوع + الإدخالات، مضروبة في تعريفة العميل، مثلاً 40 أو 50 أوقية.',
                'headers' => ['الأسبوع (مثال، 40 أوقية / كرتونة)', 'الكراتين المفوترة', 'المبلغ', 'المجموع التراكمي'],
                'rows' => [
                    ['الأسبوع 1 · إدخال 28 كرتونة', '28', '1120 أوقية', '1120 أوقية'],
                    ['الأسبوع 2 · سحب 8 كراتين', '28', '1120 أوقية', '2240 أوقية'],
                    ['الأسبوع 3', '20', '800 أوقية', '3040 أوقية'],
                ],
                'note' => 'يُحتسب السحب ابتداءً من الأسبوع التالي. فاتورة التبريد = سطر لكل أسبوع.'],

            ['type' => 'cards', 'eyebrow' => 'الفواتير والتحصيل', 'title' => 'فاتورتان منفصلتان، تُدفعان مرة واحدة أو على دفعات',
                'cards' => [
                    ['icon' => 'fa-file-invoice', 'title' => 'فاتورة المعالجة', 'text' => 'الكيلوغرامات المصنَّفة × الرسوم للكيلوغرام. فاتورة لكل معالجة، بلا علاقة بالتبريد.'],
                    ['icon' => 'fa-snowflake', 'title' => 'فاتورة التبريد', 'text' => 'متغيّرة: سطر لكل أسبوع حسب المخزون الموجود في غرفة التبريد.'],
                    ['icon' => 'fa-hand-holding-dollar', 'title' => 'التحصيل', 'text' => 'دفع جزئي أو كامل، نقداً أو بتحويل أو بشيك أو بالهاتف. يُسلَّم للعميل وصل PDF.', 'dark' => true],
                ],
                'note' => 'يعرض التطبيق دائماً ما تمت فوترته وما تم تحصيله والباقي على كل عميل.'],

            ['type' => 'grid', 'eyebrow' => 'الأدوار والصلاحيات', 'title' => 'كلٌّ يرى عمله ويقوم به فقط', 'columns' => 4,
                'items' => [
                    ['المدير العام', 'كل النظام'], ['الإدارة', 'المتابعة والقرارات'], ['المحاسب', 'الفواتير والتحصيل'], ['الفوترة', 'إصدار الفواتير'],
                    ['المعالجة', 'الفرز، العربات، الكراتين'], ['المخزون', 'الإدخالات والتسليمات'], ['مشغّل النفق', 'الإدخال والإخراج'], ['أمين المخزن', 'غرف التبريد'],
                ],
                'note' => '<b>صلاحية لكل عملية</b>: ينشئ المسؤول الأدوار ويحدّد بدقة ما يمكن لكل شخص القيام به.'],

            ['type' => 'grid', 'eyebrow' => 'الوثائق المطبوعة', 'title' => 'الورقة المناسبة في الوقت المناسب', 'columns' => 3,
                'items' => [
                    ['بطاقة المعالجة', 'للعميل: الأنواع، الكميات، الرسوم'], ['سند التحميل', 'للعمّال: عربة بعربة'],
                    ['وصل الإدخال', 'الكراتين الموضوعة في غرفة التبريد'], ['سند التسليم', 'الكراتين التي استرجعها العميل'],
                    ['الفواتير', 'المعالجة والتبريد، مع الباقي للدفع'], ['وصل الدفع', 'يُسلَّم عند كل تحصيل'],
                ]],

            ['type' => 'end', 'title' => 'كل كيلوغرام متابَع، وكل أسبوع مفوتَر.', 'subtitle' => 'FreezePro · شكراً — هل من أسئلة؟'],
        ];
    }
}
