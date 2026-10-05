<?php
/**
 * Endpoint JSON — retourne les couleurs des alertes actives pour le JS dashboard.
 * Nécessite une session GLPI valide (include.php vérifie l'authentification).
 */
include('../../../inc/includes.php');

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache');

global $DB;

$data = [];
foreach ($DB->request([
    'SELECT' => ['id', 'couleur'],
    'FROM'   => 'glpi_plugin_stockalert_alerts',
    'WHERE'  => ['is_active' => 1],
    'ORDER'  => 'ordre ASC, nom ASC',
]) as $row) {
    $data[] = [
        'id'      => (int) $row['id'],
        'couleur' => $row['couleur'] ?? '#3498db',
    ];
}

echo json_encode($data);
