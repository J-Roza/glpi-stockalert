<?php
/*
 * Page GLPI — Surveillance du stock (plugin stockalert)
 * Affiche l'état du stock pour chaque alerte active configurée en DB.
 * Jonathan Roza (Celeste IT) — avec l'aide de Claude AI
 */

include('../../../inc/includes.php');
Session::checkLoginUser();

$alertes   = PluginStockalertStock::getAlertes();
$nbAlertes = count(array_filter($alertes, fn($a) => $a['en_alerte']));

Html::header('Stock Alert', $_SERVER['PHP_SELF'], 'tools', 'PluginStockalertStock');
?>

<div class="container-fluid mt-3">

    <!-- Titre -->
    <div class="d-flex align-items-center mb-4 gap-3">
        <i class="ti ti-alert-triangle fa-2x <?= $nbAlertes > 0 ? 'text-danger' : 'text-success' ?>"></i>
        <div>
            <h2 class="mb-0">Surveillance du stock</h2>
            <small class="text-muted">
                <?php if (empty($alertes)): ?>
                    Aucune règle configurée —
                    <a href="<?= Plugin::getWebDir('stockalert') ?>/front/config.php">Ajouter une alerte</a>
                <?php elseif ($nbAlertes > 0): ?>
                    <span class="text-danger fw-bold"><?= $nbAlertes ?> alerte(s) active(s)</span>
                    sur <?= count($alertes) ?> règle(s)
                <?php else: ?>
                    <span class="text-success fw-bold">Tous les stocks sont OK</span>
                    — <?= count($alertes) ?> règle(s)
                <?php endif; ?>
            </small>
        </div>
        <div class="ms-auto d-flex gap-2">
            <a href="<?= Plugin::getWebDir('stockalert') ?>/front/config.php"
               class="btn btn-sm btn-outline-primary">
                <i class="ti ti-settings me-1"></i>Configuration
            </a>
            <a href="<?= Plugin::getWebDir('stockalert') ?>/front/stock.php"
               class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-refresh me-1"></i>Actualiser
            </a>
        </div>
    </div>

    <?php if (empty($alertes)): ?>

        <div class="alert alert-info d-flex align-items-center gap-2">
            <i class="ti ti-info-circle"></i>
            <span>
                Aucune alerte configurée.
                <a href="<?= Plugin::getWebDir('stockalert') ?>/front/config.php">Créer la première alerte</a>.
            </span>
        </div>

    <?php else: ?>

        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th><i class="ti ti-tag me-1"></i>Nom</th>
                            <th><i class="ti ti-device-desktop me-1"></i>Type</th>
                            <th><i class="ti ti-map-pin me-1"></i>Lieu</th>
                            <th><i class="ti ti-building-factory me-1"></i>Fabricant</th>
                            <th><i class="ti ti-box me-1"></i>Modèle</th>
                            <th class="text-center"><i class="ti ti-package me-1"></i>En stock</th>
                            <th class="text-center"><i class="ti ti-alert-circle me-1"></i>Seuil</th>
                            <th class="text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alertes as $alerte):
                            $filtres   = $alerte['filtres'] ?? [];
                            $quantite  = $alerte['quantite'];
                            $enAlerte  = $alerte['en_alerte'];
                            $erreur    = ($quantite === -1);
                        ?>
                        <tr class="<?= $enAlerte ? 'table-danger' : '' ?>">
                            <td class="fw-semibold"><?= htmlspecialchars($alerte['nom']) ?></td>
                            <td><?= htmlspecialchars($alerte['item_type']) ?></td>
                            <td><?= htmlspecialchars($filtres['lieu']      ?? '—') ?></td>
                            <td><?= htmlspecialchars($filtres['fabricant'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($filtres['modele']    ?? '—') ?></td>
                            <td class="text-center fw-bold fs-5 <?= $erreur ? 'text-warning' : ($enAlerte ? 'text-danger' : 'text-success') ?>">
                                <?= $erreur ? '<i class="ti ti-alert-triangle" title="Erreur SQL"></i>' : $quantite ?>
                            </td>
                            <td class="text-center"><?= $alerte['seuil'] ?></td>
                            <td class="text-center">
                                <?php if ($erreur): ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="ti ti-exclamation-mark me-1"></i>ERREUR
                                    </span>
                                <?php elseif ($enAlerte): ?>
                                    <span class="badge bg-danger">
                                        <i class="ti ti-alert-triangle me-1"></i>ALERTE
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success">
                                        <i class="ti ti-check me-1"></i>OK
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php Html::footer(); ?>
