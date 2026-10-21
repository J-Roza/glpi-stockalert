<?php
/*
 * Hook GLPI — plugin stockalert
 */

function plugin_stockalert_install(): bool {
    global $DB;

    if (!$DB->tableExists('glpi_plugin_stockalert_alerts')) {
        // Crée la table avec toutes les colonnes (schéma complet, compatible GLPI 11)
        $query = "
            CREATE TABLE `glpi_plugin_stockalert_alerts` (
                `id`               int unsigned     NOT NULL AUTO_INCREMENT,
                `nom`              varchar(255)     NOT NULL DEFAULT '',
                `item_type`        varchar(100)     NOT NULL DEFAULT 'Computer',
                `seuil`            int              NOT NULL DEFAULT 5,
                `filtre_lieu`      varchar(255)              DEFAULT NULL,
                `filtre_fabricant` varchar(255)              DEFAULT NULL,
                `filtre_type`      varchar(255)              DEFAULT NULL,
                `filtre_modele`    varchar(255)              DEFAULT NULL,
                `statut_stock`     varchar(255)     NOT NULL DEFAULT 'En stock',
                `is_active`        tinyint(1)       NOT NULL DEFAULT 1,
                `couleur`          varchar(7)       NOT NULL DEFAULT '#3498db',
                `ordre`            int              NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        if (!$DB->doQuery($query)) {
            return false;
        }
    }

    return true;
}

function plugin_stockalert_uninstall(): bool {
    global $DB;
    return (bool) $DB->doQuery("DROP TABLE IF EXISTS `glpi_plugin_stockalert_alerts`");
}

function plugin_stockalert_getMenuContent(): array {
    return [
        'title'   => 'Stock Alert',
        'page'    => '/plugins/stockalert/front/stock.php',
        'icon'    => 'ti ti-alert-triangle',
        'options' => [
            'stock' => [
                'title' => 'Surveillance',
                'page'  => '/plugins/stockalert/front/stock.php',
                'icon'  => 'ti ti-alert-triangle',
            ],
            'config' => [
                'title' => 'Configuration',
                'page'  => '/plugins/stockalert/front/config.php',
                'icon'  => 'ti ti-settings',
            ],
        ],
    ];
}
