<?php
/*
 * Plugin GLPI : stockalert
 * Surveillance du stock matériel avec alertes configurables.
 * Jonathan Roza (Celeste IT) — avec l'aide de Claude AI — v1.0
 */

define('PLUGIN_STOCKALERT_VERSION',  '1.0.0');
define('PLUGIN_STOCKALERT_MIN_GLPI', '10.0.0');
define('PLUGIN_STOCKALERT_MAX_GLPI', '11.9.99');

function plugin_version_stockalert(): array {
    return [
        'name'         => 'Stock Alert',
        'version'      => PLUGIN_STOCKALERT_VERSION,
        'author'       => 'Jonathan Roza (Celeste IT) — avec l\'aide de Claude AI',
        'license'      => 'GPLv2+',
        'homepage'     => 'https://github.com/J-Roza/GLPI_Import',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_STOCKALERT_MIN_GLPI,
                'max' => PLUGIN_STOCKALERT_MAX_GLPI,
            ],
        ],
    ];
}

function plugin_stockalert_check_prerequisites(): bool {
    return true;
}

function plugin_stockalert_check_config(): bool {
    return true;
}

function plugin_init_stockalert(): void {
    global $PLUGIN_HOOKS;

    Plugin::registerClass('PluginStockalertStock');
    $PLUGIN_HOOKS['menu_toadd']['stockalert']       = ['tools' => 'PluginStockalertStock'];
    $PLUGIN_HOOKS['dashboard_cards']['stockalert']  = ['PluginStockalertStock', 'getDashboardCards'];
    $PLUGIN_HOOKS['add_javascript']['stockalert']   = ['front/dashboard_tiles.js', 'front/menu_tweaks.js'];
}
