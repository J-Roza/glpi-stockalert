<?php
/*
 * Classe PluginStockalertStock
 * Gestion des alertes de stock GLPI — stockage en base de données.
 * Jonathan Roza (Celeste IT) — avec l'aide de Claude AI
 */

class PluginStockalertStock extends CommonGLPI {

    public static function getTypeName($nb = 0): string {
        return 'Stock Alert';
    }

    // Menu GLPI — appelé par le hook menu_toadd (inliné car hook.php pas encore chargé)
    public static function getMenuContent(): array {
        return [
            'title'   => 'Stock Alert',
            'page'    => Plugin::getWebDir('stockalert') . '/front/stock.php',
            'icon'    => 'ti ti-alert-triangle',
            'options' => [
                'stock' => [
                    'title' => 'Surveillance',
                    'page'  => Plugin::getWebDir('stockalert') . '/front/stock.php',
                    'icon'  => 'ti ti-alert-triangle',
                ],
                'config' => [
                    'title' => 'Configuration',
                    'page'  => Plugin::getWebDir('stockalert') . '/front/config.php',
                    'icon'  => 'ti ti-settings',
                ],
            ],
        ];
    }

    // Mapping item_type → tables GLPI
    private static function getTableMapping(): array {
        return [
            'Computer' => [
                'table'       => 'glpi_computers',
                'table_type'  => 'glpi_computertypes',
                'table_model' => 'glpi_computermodels',
                'fk_type'     => 'computertypes_id',
                'fk_model'    => 'computermodels_id',
            ],
            'Monitor' => [
                'table'       => 'glpi_monitors',
                'table_type'  => 'glpi_monitortypes',
                'table_model' => 'glpi_monitormodels',
                'fk_type'     => 'monitortypes_id',
                'fk_model'    => 'monitormodels_id',
            ],
            'Peripheral' => [
                'table'       => 'glpi_peripherals',
                'table_type'  => 'glpi_peripheraltypes',
                'table_model' => 'glpi_peripheralmodels',
                'fk_type'     => 'peripheraltypes_id',
                'fk_model'    => 'peripheralmodels_id',
            ],
            'NetworkEquipment' => [
                'table'       => 'glpi_networkequipments',
                'table_type'  => 'glpi_networkequipmenttypes',
                'table_model' => 'glpi_networkequipmentmodels',
                'fk_type'     => 'networkequipmenttypes_id',
                'fk_model'    => 'networkequipmentmodels_id',
            ],
            'Phone' => [
                'table'       => 'glpi_phones',
                'table_type'  => 'glpi_phonetypes',
                'table_model' => 'glpi_phonemodels',
                'fk_type'     => 'phonetypes_id',
                'fk_model'    => 'phonemodels_id',
            ],
        ];
    }

    // Compte le matériel en stock pour une ligne d'alerte (depuis DB)
    public static function compterStock(array $alerte): int {
        global $DB;

        $itemType = $alerte['item_type'] ?? 'Computer';
        $mapping  = self::getTableMapping();

        if (!isset($mapping[$itemType])) {
            return -1;
        }

        $m          = $mapping[$itemType];
        $tableMain  = $m['table'];
        $tableType  = $m['table_type'];
        $tableModel = $m['table_model'];
        $fkType     = $m['fk_type'];
        $fkModel    = $m['fk_model'];

        $conditions = [
            "{$tableMain}.is_deleted"  => 0,
            "{$tableMain}.is_template" => 0,
            'glpi_states.name'         => $alerte['statut_stock'] ?? 'En stock',
        ];

        // Les filtres sont stockés en CSV — on utilise IN si plusieurs valeurs.
        // QueryExpression pour les noms de champs avec notation pointée (table.colonne),
        // car GLPI quoteName() traite le tout comme un seul identifiant sinon.
        foreach ([
            'filtre_fabricant' => 'glpi_manufacturers.name',
            'filtre_type'      => "{$tableType}.name",
            'filtre_lieu'      => 'glpi_locations.name',
            'filtre_modele'    => "{$tableModel}.name",
        ] as $col => $field) {
            if (!empty($alerte[$col])) {
                $vals = array_values(array_filter(array_map('trim', explode(',', $alerte[$col]))));
                if (count($vals) === 0) {
                    continue;
                }
                if (count($vals) === 1) {
                    $conditions[$field] = $vals[0];
                } else {
                    // Quoter chaque partie du champ séparément pour éviter `table.colonne` → une seule colonne
                    $parts       = explode('.', $field);
                    $quotedField = implode('.', array_map(fn($p) => $DB->quoteName($p), $parts));
                    $quotedVals  = implode(',', array_map(fn($v) => $DB->quoteValue($v), $vals));
                    $conditions[] = new QueryExpression("{$quotedField} IN ({$quotedVals})");
                }
            }
        }

        try {
            $iterator = $DB->request([
                'COUNT'    => 'id',
                'FROM'     => $tableMain,
                'LEFT JOIN' => [
                    'glpi_states' => [
                        'ON' => [$tableMain => 'states_id', 'glpi_states' => 'id'],
                    ],
                    'glpi_locations' => [
                        'ON' => [$tableMain => 'locations_id', 'glpi_locations' => 'id'],
                    ],
                    'glpi_manufacturers' => [
                        'ON' => [$tableMain => 'manufacturers_id', 'glpi_manufacturers' => 'id'],
                    ],
                    $tableType => [
                        'ON' => [$tableMain => $fkType, $tableType => 'id'],
                    ],
                    $tableModel => [
                        'ON' => [$tableMain => $fkModel, $tableModel => 'id'],
                    ],
                ],
                'WHERE' => $conditions,
            ]);

            $ligne = $iterator->current();
            $count = (int) (current($ligne) ?: 0);
            return $count;

        } catch (Exception $e) {
            return -1;
        }
    }

    // Retourne toutes les alertes actives avec stock actuel et flag en_alerte
    public static function getAlertes(): array {
        global $DB;

        $resultats = [];
        foreach ($DB->request(['FROM' => 'glpi_plugin_stockalert_alerts', 'WHERE' => ['is_active' => 1], 'ORDER' => 'ordre ASC, nom ASC']) as $alerte) {
            $quantite    = self::compterStock($alerte);
            $resultats[] = [
                'id'        => $alerte['id'],
                'nom'       => $alerte['nom'],
                'item_type' => $alerte['item_type'],
                'seuil'     => (int) $alerte['seuil'],
                'couleur'   => $alerte['couleur'] ?? '#3498db',
                'filtres'   => [
                    'lieu'      => $alerte['filtre_lieu'],
                    'fabricant' => $alerte['filtre_fabricant'],
                    'type'      => $alerte['filtre_type'],
                    'modele'    => $alerte['filtre_modele'],
                ],
                'statut_stock' => $alerte['statut_stock'],
                'quantite'     => $quantite,
                'en_alerte'    => ($quantite >= 0 && $quantite < (int) $alerte['seuil']),
            ];
        }

        return $resultats;
    }

    // Retourne toutes les alertes (actives et inactives) pour la page de config
    public static function getToutesAlertes(): array {
        global $DB;
        $alertes = [];
        foreach ($DB->request(['FROM' => 'glpi_plugin_stockalert_alerts', 'ORDER' => 'ordre ASC, nom ASC']) as $row) {
            $alertes[] = $row;
        }
        return $alertes;
    }

    // Monte une alerte dans l'ordre d'affichage
    public static function monterAlerte(int $id): void {
        global $DB;
        $current = self::getAlerte($id);
        if (!$current) return;
        $prev = null;
        foreach ($DB->request(['FROM' => 'glpi_plugin_stockalert_alerts', 'WHERE' => ['ordre' => ['<', (int) $current['ordre']]], 'ORDER' => 'ordre DESC', 'LIMIT' => 1]) as $row) {
            $prev = $row;
        }
        if ($prev) {
            $DB->update('glpi_plugin_stockalert_alerts', ['ordre' => (int) $prev['ordre']], ['id' => $id]);
            $DB->update('glpi_plugin_stockalert_alerts', ['ordre' => (int) $current['ordre']], ['id' => $prev['id']]);
        }
    }

    // Descend une alerte dans l'ordre d'affichage
    public static function descendreAlerte(int $id): void {
        global $DB;
        $current = self::getAlerte($id);
        if (!$current) return;
        $next = null;
        foreach ($DB->request(['FROM' => 'glpi_plugin_stockalert_alerts', 'WHERE' => ['ordre' => ['>', (int) $current['ordre']]], 'ORDER' => 'ordre ASC', 'LIMIT' => 1]) as $row) {
            $next = $row;
        }
        if ($next) {
            $DB->update('glpi_plugin_stockalert_alerts', ['ordre' => (int) $next['ordre']], ['id' => $id]);
            $DB->update('glpi_plugin_stockalert_alerts', ['ordre' => (int) $current['ordre']], ['id' => $next['id']]);
        }
    }

    // Retourne une alerte par ID
    public static function getAlerte(int $id): ?array {
        global $DB;
        foreach ($DB->request(['FROM' => 'glpi_plugin_stockalert_alerts', 'WHERE' => ['id' => $id]]) as $row) {
            return $row;
        }
        return null;
    }

    // Sérialise un filtre POST (string ou array) en CSV pour la DB
    private static function serialiserFiltre(mixed $val): ?string {
        if (is_array($val)) {
            $val = implode(',', array_filter(array_map('trim', $val)));
        }
        return $val !== '' ? $val : null;
    }

    // Ajoute une alerte
    public static function ajouterAlerte(array $input): bool {
        global $DB;
        // Ordre = max existant + 1
        $maxOrdre = 0;
        foreach ($DB->request(['SELECT' => ['MAX' => 'ordre as max_ordre'], 'FROM' => 'glpi_plugin_stockalert_alerts']) as $row) {
            $maxOrdre = (int) ($row['max_ordre'] ?? 0);
        }
        return $DB->insert('glpi_plugin_stockalert_alerts', [
            'nom'              => trim($input['nom'] ?? ''),
            'item_type'        => $input['item_type']  ?? 'Computer',
            'seuil'            => (int) ($input['seuil'] ?? 5),
            'couleur'          => $input['couleur']    ?? '#3498db',
            'filtre_lieu'      => self::serialiserFiltre($input['filtre_lieu']      ?? ''),
            'filtre_fabricant' => self::serialiserFiltre($input['filtre_fabricant'] ?? ''),
            'filtre_type'      => self::serialiserFiltre($input['filtre_type']      ?? ''),
            'filtre_modele'    => self::serialiserFiltre($input['filtre_modele']    ?? ''),
            'statut_stock'     => $input['statut_stock'] ?? 'En stock',
            'is_active'        => isset($input['is_active']) ? 1 : 0,
            'ordre'            => $maxOrdre + 1,
        ]);
    }

    // Modifie une alerte
    public static function modifierAlerte(int $id, array $input): bool {
        global $DB;
        return $DB->update('glpi_plugin_stockalert_alerts', [
            'nom'              => trim($input['nom'] ?? ''),
            'item_type'        => $input['item_type']  ?? 'Computer',
            'seuil'            => (int) ($input['seuil'] ?? 5),
            'couleur'          => $input['couleur']    ?? '#3498db',
            'filtre_lieu'      => self::serialiserFiltre($input['filtre_lieu']      ?? ''),
            'filtre_fabricant' => self::serialiserFiltre($input['filtre_fabricant'] ?? ''),
            'filtre_type'      => self::serialiserFiltre($input['filtre_type']      ?? ''),
            'filtre_modele'    => self::serialiserFiltre($input['filtre_modele']    ?? ''),
            'statut_stock'     => $input['statut_stock'] ?? 'En stock',
            'is_active'        => isset($input['is_active']) ? 1 : 0,
        ], ['id' => $id]);
    }

    // Supprime une alerte
    public static function supprimerAlerte(int $id): bool {
        global $DB;
        return $DB->delete('glpi_plugin_stockalert_alerts', ['id' => $id]);
    }

    // Provider pour le hook dashboard_cards GLPI 11
    public static function getDashboardCards(): array {
        return [
            'plugin_stockalert_summary' => [
                'widgettype' => ['summaryNumbers'],
                'label'      => 'Stock Alert — niveaux de stock',
                'provider'   => 'PluginStockalertStock::getDashboardSummaryProvider',
                'icon'       => 'ti ti-package',
                'filters'    => [],
            ],
            'plugin_stockalert_big' => [
                'widgettype' => ['bigNumber'],
                'label'      => 'Stock Alert — alerte critique',
                'provider'   => 'PluginStockalertStock::getDashboardBigProvider',
                'icon'       => 'ti ti-alert-triangle',
                'filters'    => [],
            ],
        ];
    }

    // Construit l'URL de recherche GLPI pré-filtrée pour une alerte donnée
    private static function buildSearchUrl(array $alerte): string {
        $itemType = $alerte['item_type'] ?? 'Computer';
        $filtres  = $alerte['filtres']   ?? [];

        try {
            $obj      = new $itemType();
            $basePage = $obj->getSearchURL(false);
        } catch (\Throwable $e) {
            return Plugin::getWebDir('stockalert') . '/front/stock.php';
        }

        // IDs de champs de recherche GLPI (communs aux types d'assets standard)
        $fieldState = 31;
        $fieldLoc   = 3;
        $fieldFab   = 23;
        $fieldType  = 4;
        $fieldModel = 40;

        $p = [];
        $i = 0;

        if (!empty($alerte['statut_stock'])) {
            $p["criteria[$i][field]"]      = $fieldState;
            $p["criteria[$i][searchtype]"] = 'contains';
            $p["criteria[$i][value]"]      = $alerte['statut_stock'];
            $i++;
        }

        foreach ([
            'lieu' => $fieldLoc,
            'type' => $fieldType,
        ] as $key => $fieldId) {
            if (!empty($filtres[$key])) {
                $vals = array_values(array_filter(array_map('trim', explode(',', $filtres[$key]))));
                if (!empty($vals)) {
                    $p["criteria[$i][field]"]      = $fieldId;
                    $p["criteria[$i][searchtype]"] = 'contains';
                    $p["criteria[$i][value]"]      = $vals[0];
                    $i++;
                }
            }
        }

        return $basePage . '?' . http_build_query($p);
    }

    // Données pour summaryNumbers — format attendu : ['data' => [items...], 'label' => '...']
    public static function getDashboardSummaryProvider(array $params = []): array {
        $alertes = self::getAlertes();

        $data = [];
        foreach ($alertes as $alerte) {
            $quantite = $alerte['quantite'];
            $data[] = [
                'number' => $quantite >= 0 ? $quantite : 0,
                'label'  => $alerte['nom'] . ' (seuil : ' . $alerte['seuil'] . ')',
                'url'    => self::buildSearchUrl($alerte),
                'icon'   => $alerte['en_alerte'] ? 'ti ti-alert-triangle' : 'ti ti-check',
                'color'  => $alerte['en_alerte'] ? '#e74c3c' : ($alerte['couleur'] ?? '#3498db'),
            ];
        }

        return [
            'data'  => $data,
            'label' => 'Stock Alert',
        ];
    }

    // Données pour bigNumber — retourne l'alerte la plus critique
    public static function getDashboardBigProvider(array $params = []): array {
        $alertes = self::getAlertes();

        foreach ($alertes as $alerte) {
            if ($alerte['en_alerte']) {
                return [
                    'number' => $alerte['quantite'] >= 0 ? $alerte['quantite'] : 0,
                    'label'  => $alerte['nom'] . ' (seuil : ' . $alerte['seuil'] . ')',
                    'url'    => self::buildSearchUrl($alerte),
                    'color'  => '#e74c3c',
                ];
            }
        }

        $first = $alertes[0] ?? null;
        return [
            'number' => $first ? ($first['quantite'] >= 0 ? $first['quantite'] : 0) : 0,
            'label'  => $first ? $first['nom'] : 'Aucune alerte',
            'url'    => $first ? self::buildSearchUrl($first) : Plugin::getWebDir('stockalert') . '/front/stock.php',
            'color'  => '#27ae60',
        ];
    }
}
