<?php

namespace App\Support;

use App\Http\Middleware\CheckRoutePermission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Une permission par action du systeme, deduite des routes protegees.
 * Nom de la permission = nom de la route ; « store » est fusionne avec « create »
 * et « update » avec « edit » (le formulaire et son enregistrement = une seule action).
 */
class PermissionCatalog
{
    /** Routes ouvertes a tout utilisateur connecte. */
    public const OPEN = ['dashboard', 'profile.edit', 'profile.update', 'profile.destroy'];

    /** Routes techniques rattachees a l'action qui les utilise. */
    public const ALIASES = [
        'deliveries.client-stock' => 'deliveries.create',
        'invoices.preview' => 'invoices.create',
        'processing.distribution.suggest' => 'processing.distribution',
    ];

    public const MODULES = [
        'clients' => 'Clients',
        'arrivals' => 'Arrivages',
        'processing' => 'Traitement',
        'packaging' => 'Cartonnage',
        'tunnels' => 'Tunnels',
        'carts' => 'Chariots',
        'cold-rooms' => 'Chambres froides',
        'stock' => 'Stock client',
        'deliveries' => 'Livraisons',
        'invoices' => 'Factures',
        'storage-billing' => 'Facturation congélation',
        'payments' => 'Encaissements',
        'fish-types' => 'Types de poissons',
        'carton-types' => 'Types de cartons',
        'reports' => 'Rapports',
        'settings' => 'Paramètres',
        'users' => 'Utilisateurs',
        'roles' => 'Rôles et permissions',
    ];

    private const ACTION_LABELS = [
        'index' => 'Voir la liste',
        'show' => 'Voir le détail',
        'create' => 'Créer',
        'edit' => 'Modifier',
        'destroy' => 'Supprimer',
        'pdf' => 'Imprimer le PDF',
        'print' => 'Imprimer',
    ];

    private const LABELS = [
        'processing.prices' => 'Saisir les frais de traitement par kg',
        'processing.assign-carts' => 'Affecter les chariots',
        'processing.cancel-carts' => "Annuler l'affectation des chariots",
        'processing.distribution' => 'Répartir les poissons dans les chariots',
        'processing.place-tunnels' => 'Placer les chariots dans les tunnels',
        'processing.release-tunnels' => 'Sortir les chariots du tunnel (vers le cartonnage)',
        'processing.client-pdf' => 'Imprimer la fiche de traitement client',
        'processing.worker-sheet-pdf' => 'Imprimer le bon de chargement',
        'tunnels.release-assignment' => 'Dissocier un chariot du tunnel',
        'tunnels.release-multiple-assignments' => 'Dissocier plusieurs chariots',
        'packaging.print' => 'Imprimer la fiche de cartonnage',
        'packaging.finalize' => 'Finaliser le cartonnage (stocker / récupéré)',
        'stock.receipt' => "Imprimer le reçu d'entrée",
        'stock.transfer' => 'Transférer un lot de chambre',
        'stock.entry.show' => "Voir une entrée de stock",
        'stock.transactions' => 'Voir le suivi des transactions',
        'stock.transactions.excel' => 'Exporter les transactions (Excel)',
        'stock.export.pdf' => 'Exporter la liste du stock (PDF)',
        'stock.export.excel' => 'Exporter la liste du stock (Excel)',
        'stock.client-statement' => "Voir le relevé de stock d'un client",
        'stock.client-statement.pdf' => 'Imprimer le relevé de stock (PDF)',
        'stock.client-statement.excel' => 'Exporter le relevé de stock (Excel)',
        'stock.create' => 'Entrée directe de stock',
        'deliveries.validate' => 'Valider une livraison (sortie de stock)',
        'deliveries.pdf' => 'Imprimer le bon de livraison',
        'invoices.storage-config' => "Paramétrer le tarif froid d'un client",
        'invoices.storage-generate' => 'Générer une facture froid (période)',
        'invoices.pdf' => 'Imprimer la facture',
        'invoices.mark-paid' => 'Solder une facture',
        'invoices.payments.store' => 'Encaisser un paiement sur une facture',
        'storage-billing.index' => 'Voir la facturation congélation',
        'storage-billing.generate' => 'Générer la période en cours',
        'storage-billing.generate-week' => 'Facturer une semaine',
        'storage-billing.generate-unbilled' => 'Facturer les semaines non facturées',
        'storage-billing.pay' => 'Solder une facture froid',
        'storage-billing.collect' => 'Encaisser sur le compte froid du client',
        'payments.receipt' => 'Imprimer un reçu de paiement',
        'payments.destroy' => 'Annuler un encaissement',
        'reports.index' => 'Voir les rapports',
        'settings.index' => 'Voir les paramètres',
        'settings.edit' => 'Modifier les paramètres',
    ];

    /** Nom de la permission requise pour une route (null = route libre). */
    public static function permissionFor(?string $routeName): ?string
    {
        if (! $routeName || in_array($routeName, self::OPEN, true)) {
            return null;
        }

        return self::normalize(self::ALIASES[$routeName] ?? $routeName);
    }

    public static function normalize(string $routeName): string
    {
        // Les actions nommees explicitement (ex. invoices.payments.store = Encaisser) gardent leur nom
        if (isset(self::LABELS[$routeName])) {
            return $routeName;
        }

        return preg_replace(['/\.store$/', '/\.update$/'], ['.create', '.edit'], $routeName);
    }

    /**
     * Toutes les permissions du systeme : [name => ['module', 'module_label', 'label', 'method']].
     */
    public static function all(): Collection
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array(CheckRoutePermission::class, $route->gatherMiddleware(), true))
            ->map(fn ($route) => ['name' => self::permissionFor($route->getName()), 'method' => $route->methods()[0]])
            ->filter(fn ($r) => $r['name'])
            ->groupBy('name')
            ->map(function ($rows, $name) {
                $module = Str::before($name, '.');
                return [
                    'name' => $name,
                    'module' => $module,
                    'module_label' => self::MODULES[$module] ?? Str::headline($module),
                    'label' => self::label($name),
                    // Lecture seule si toutes les routes de l'action sont des GET
                    'read_only' => $rows->every(fn ($r) => in_array($r['method'], ['GET', 'HEAD'], true)),
                ];
            })
            ->sortBy(fn ($p) => [self::moduleOrder($p['module']), self::order($p['name']), $p['name']])
            ->values();
    }

    public static function grouped(): Collection
    {
        return self::all()->groupBy('module_label');
    }

    public static function label(string $name): string
    {
        if (isset(self::LABELS[$name])) {
            return self::LABELS[$name];
        }

        $action = Str::afterLast($name, '.');
        return self::ACTION_LABELS[$action] ?? Str::ucfirst(str_replace(['-', '.'], ' ', Str::after($name, '.')));
    }

    private static function moduleOrder(string $module): int
    {
        $pos = array_search($module, array_keys(self::MODULES), true);
        return $pos === false ? 99 : $pos;
    }

    private static function order(string $name): int
    {
        $pos = array_search(Str::afterLast($name, '.'), ['index', 'show', 'create', 'edit', 'destroy'], true);
        return $pos === false ? 10 : $pos;
    }

    /**
     * Convertit les anciennes permissions par module (clients.view, invoices.collect...)
     * en permissions par action. Sert a la migration et au seeder.
     */
    public static function fromLegacy(array $legacy): array
    {
        $legacyModule = ['storage-billing' => 'invoices', 'payments' => 'invoices'];
        $collect = ['invoices.mark-paid', 'invoices.payments.store', 'storage-billing.pay', 'storage-billing.collect', 'payments.destroy'];
        $createLike = ['invoices.storage-generate', 'storage-billing.generate', 'storage-billing.generate-week', 'storage-billing.generate-unbilled'];

        return self::all()
            ->filter(function ($p) use ($legacy, $legacyModule, $collect, $createLike) {
                $module = $legacyModule[$p['module']] ?? $p['module'];
                $action = Str::afterLast($p['name'], '.');

                $needed = match (true) {
                    in_array($p['name'], $collect, true) => 'invoices.collect',
                    in_array($p['name'], $createLike, true) => 'invoices.create',
                    $action === 'create' => "{$module}.create",
                    $action === 'edit' => "{$module}.edit",
                    $action === 'destroy' => "{$module}.delete",
                    $p['read_only'] => "{$module}.view",
                    default => "{$module}.edit",
                };

                return in_array($needed, $legacy, true);
            })
            ->pluck('name')
            ->values()
            ->all();
    }
}
