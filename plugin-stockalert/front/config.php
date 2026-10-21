<?php
/*
 * Page GLPI — Configuration des alertes stock (plugin stockalert)
 * Formulaire d'ajout/modification/suppression des alertes en base de données.
 * Jonathan Roza (Celeste IT) — avec l'aide de Claude AI
 */

include('../../../inc/includes.php');
Session::checkLoginUser();

$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$alerteId = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$message  = '';
$messageType = 'success';

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'ajouter':
            if (empty(trim($_POST['nom'] ?? ''))) {
                $message = "Le nom de l'alerte est obligatoire.";
                $messageType = 'danger';
            } else {
                PluginStockalertStock::ajouterAlerte($_POST);
                $message = "Alerte « " . htmlspecialchars(trim($_POST['nom'])) . " » créée.";
            }
            break;

        case 'modifier':
            if ($alerteId > 0) {
                if (empty(trim($_POST['nom'] ?? ''))) {
                    $message = "Le nom de l'alerte est obligatoire.";
                    $messageType = 'danger';
                } else {
                    PluginStockalertStock::modifierAlerte($alerteId, $_POST);
                    $message = "Alerte mise à jour.";
                    $alerteId = 0;
                }
            }
            break;

        case 'supprimer':
            if ($alerteId > 0) {
                $alerte = PluginStockalertStock::getAlerte($alerteId);
                PluginStockalertStock::supprimerAlerte($alerteId);
                $message = "Alerte « " . htmlspecialchars($alerte['nom'] ?? '') . " » supprimée.";
                $alerteId = 0;
            }
            break;

        case 'monter':
            if ($alerteId > 0) {
                PluginStockalertStock::monterAlerte($alerteId);
                $alerteId = 0;
            }
            break;

        case 'descendre':
            if ($alerteId > 0) {
                PluginStockalertStock::descendreAlerte($alerteId);
                $alerteId = 0;
            }
            break;
    }
}

// Chargement de l'alerte à éditer
$alerteEdition = ($alerteId > 0) ? PluginStockalertStock::getAlerte($alerteId) : null;
$toutesAlertes = PluginStockalertStock::getToutesAlertes();

$itemTypes = [
    'Computer'         => 'Ordinateur',
    'Monitor'          => 'Écran',
    'Peripheral'       => 'Périphérique',
    'NetworkEquipment' => 'Équipement réseau',
    'Phone'            => 'Téléphone',
];

// Chargement des listes GLPI depuis la DB
global $DB;

$lieux = [];
foreach ($DB->request(['SELECT' => 'name', 'FROM' => 'glpi_locations', 'ORDER' => 'name ASC']) as $row) {
    if ($row['name'] !== '') $lieux[] = $row['name'];
}

$fabricants = [];
foreach ($DB->request(['SELECT' => 'name', 'FROM' => 'glpi_manufacturers', 'ORDER' => 'name ASC']) as $row) {
    if ($row['name'] !== '') $fabricants[] = $row['name'];
}

$statuts = [];
foreach ($DB->request(['SELECT' => 'name', 'FROM' => 'glpi_states', 'ORDER' => 'name ASC']) as $row) {
    if ($row['name'] !== '') $statuts[] = $row['name'];
}

$typesTables  = [
    'Computer'         => 'glpi_computertypes',
    'Monitor'          => 'glpi_monitortypes',
    'Peripheral'       => 'glpi_peripheraltypes',
    'NetworkEquipment' => 'glpi_networkequipmenttypes',
    'Phone'            => 'glpi_phonetypes',
];
$modelsTables = [
    'Computer'         => 'glpi_computermodels',
    'Monitor'          => 'glpi_monitormodels',
    'Peripheral'       => 'glpi_peripheralmodels',
    'NetworkEquipment' => 'glpi_networkequipmentmodels',
    'Phone'            => 'glpi_phonemodels',
];

$typesParItemType  = [];
$modelsParItemType = [];
foreach ($itemTypes as $itemType => $_) {
    $typesParItemType[$itemType] = [];
    foreach ($DB->request(['SELECT' => 'name', 'FROM' => $typesTables[$itemType], 'ORDER' => 'name ASC']) as $row) {
        if ($row['name'] !== '') $typesParItemType[$itemType][] = $row['name'];
    }
    $modelsParItemType[$itemType] = [];
    foreach ($DB->request(['SELECT' => 'name', 'FROM' => $modelsTables[$itemType], 'ORDER' => 'name ASC']) as $row) {
        if ($row['name'] !== '') $modelsParItemType[$itemType][] = $row['name'];
    }
}

Html::header('Stock Alert — Configuration', $_SERVER['PHP_SELF'], 'tools', 'PluginStockalertStock');
?>

<div class="container-fluid mt-3">

    <div class="d-flex align-items-center mb-4 gap-3">
        <i class="ti ti-settings fa-2x text-primary"></i>
        <div>
            <h2 class="mb-0">Configuration des alertes</h2>
            <small class="text-muted">Définissez les seuils de stock à surveiller</small>
        </div>
        <a href="<?= '/plugins/stockalert' ?>/front/stock.php"
           class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="ti ti-arrow-left me-1"></i>Retour surveillance
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <i class="ti ti-<?= $messageType === 'success' ? 'check' : 'alert-circle' ?> me-2"></i>
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Formulaire ajout / édition -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-<?= $alerteEdition ? 'edit' : 'plus' ?> me-2"></i>
                        <?= $alerteEdition ? 'Modifier l\'alerte' : 'Nouvelle alerte' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= '/plugins/stockalert' ?>/front/config.php">
                        <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
                        <input type="hidden" name="action" value="<?= $alerteEdition ? 'modifier' : 'ajouter' ?>">
                        <?php if ($alerteEdition): ?>
                            <input type="hidden" name="id" value="<?= $alerteEdition['id'] ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control"
                                   value="<?= htmlspecialchars($alerteEdition['nom'] ?? '') ?>"
                                   placeholder="Ex : Portables Lenovo — Champs" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Type de matériel</label>
                            <select name="item_type" id="item_type" class="form-select">
                                <?php foreach ($itemTypes as $val => $label): ?>
                                    <option value="<?= $val ?>"
                                        <?= ($alerteEdition['item_type'] ?? 'Computer') === $val ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Seuil d'alerte</label>
                            <input type="number" name="seuil" class="form-control" min="0"
                                   value="<?= (int) ($alerteEdition['seuil'] ?? 5) ?>">
                            <div class="form-text">Déclenche l'alerte quand le stock passe sous ce nombre.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Statut "en stock"</label>
                            <select name="statut_stock" class="form-select">
                                <option value="">— Tous les statuts —</option>
                                <?php foreach ($statuts as $s): ?>
                                    <option value="<?= htmlspecialchars($s) ?>"
                                        <?= ($alerteEdition['statut_stock'] ?? 'En stock') === $s ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <hr>
                        <p class="text-muted small mb-1">Filtres <span class="text-muted">(Ctrl+clic pour sélection multiple, vide = tous)</span></p>

                        <?php
                        // Valeurs actuelles des filtres (CSV → tableau)
                        $selLieux      = array_filter(explode(',', $alerteEdition['filtre_lieu']      ?? ''));
                        $selFabricants = array_filter(explode(',', $alerteEdition['filtre_fabricant'] ?? ''));
                        $selTypes      = array_filter(explode(',', $alerteEdition['filtre_type']      ?? ''));
                        $selModeles    = array_filter(explode(',', $alerteEdition['filtre_modele']    ?? ''));
                        ?>

                        <div class="mb-3">
                            <label class="form-label">Lieu</label>
                            <select name="filtre_lieu[]" multiple class="form-select" size="4">
                                <?php foreach ($lieux as $l): ?>
                                    <option value="<?= htmlspecialchars($l) ?>"
                                        <?= in_array($l, $selLieux, true) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($l) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fabricant</label>
                            <select name="filtre_fabricant[]" multiple class="form-select" size="4">
                                <?php foreach ($fabricants as $f): ?>
                                    <option value="<?= htmlspecialchars($f) ?>"
                                        <?= in_array($f, $selFabricants, true) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($f) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="filtre_type[]" id="filtre_type" multiple class="form-select" size="4">
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Modèle</label>
                            <select name="filtre_modele[]" id="filtre_modele" multiple class="form-select" size="5">
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Couleur <span class="text-muted small">(état normal)</span></label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="couleur" class="form-control form-control-color"
                                       value="<?= htmlspecialchars($alerteEdition['couleur'] ?? '#3498db') ?>"
                                       style="width:3rem;height:2rem;padding:2px;">
                                <span class="text-muted small">Rouge automatique si en alerte</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                       value="1" <?= ($alerteEdition['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Alerte active</label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-<?= $alerteEdition ? 'device-floppy' : 'plus' ?> me-1"></i>
                                <?= $alerteEdition ? 'Enregistrer' : 'Créer l\'alerte' ?>
                            </button>
                            <?php if ($alerteEdition): ?>
                                <a href="<?= '/plugins/stockalert' ?>/front/config.php"
                                   class="btn btn-outline-secondary">Annuler</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Liste des alertes existantes -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-list me-2"></i>Alertes configurées
                        <span class="badge bg-secondary ms-2"><?= count($toutesAlertes) ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($toutesAlertes)): ?>
                        <div class="p-4 text-muted text-center">
                            <i class="ti ti-inbox fa-2x d-block mb-2"></i>
                            Aucune alerte configurée.
                        </div>
                    <?php else: ?>
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nom</th>
                                    <th>Type</th>
                                    <th class="text-center">Seuil</th>
                                    <th class="text-center">Actif</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($toutesAlertes as $a): ?>
                                <tr class="<?= !$a['is_active'] ? 'text-muted' : '' ?>">
                                    <td>
                                        <span class="d-inline-block me-2" style="width:12px;height:12px;border-radius:50%;background:<?= htmlspecialchars($a['couleur'] ?? '#3498db') ?>"></span>
                                        <span class="fw-semibold"><?= htmlspecialchars($a['nom']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($itemTypes[$a['item_type']] ?? $a['item_type']) ?></td>
                                    <td class="text-center"><?= (int) $a['seuil'] ?></td>
                                    <td class="text-center">
                                        <?php if ($a['is_active']): ?>
                                            <span class="badge bg-success">Oui</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Non</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <!-- Boutons ordre -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
                                            <input type="hidden" name="action" value="monter">
                                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Monter">
                                                <i class="ti ti-arrow-up"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
                                            <input type="hidden" name="action" value="descendre">
                                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary me-1" title="Descendre">
                                                <i class="ti ti-arrow-down"></i>
                                            </button>
                                        </form>
                                        <a href="?id=<?= $a['id'] ?>"
                                           class="btn btn-sm btn-outline-primary me-1"
                                           title="Modifier">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <form method="POST" class="d-inline"
                                              onsubmit="return confirm('Supprimer cette alerte ?')">
                                            <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
                                            <input type="hidden" name="action" value="supprimer">
                                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    title="Supprimer">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Données types et modèles par item_type (injectées depuis PHP)
const typesParItemType   = <?= json_encode($typesParItemType) ?>;
const modelsParItemType  = <?= json_encode($modelsParItemType) ?>;
const valeursTypeActuel  = <?= json_encode($selTypes) ?>;
const valeursModeleActuel = <?= json_encode($selModeles) ?>;

function mettreAJourTypeModele(itemType) {
    const selectType   = document.getElementById('filtre_type');
    const selectModele = document.getElementById('filtre_modele');

    // Reconstruit les options Type (multi-select)
    selectType.innerHTML = '';
    (typesParItemType[itemType] || []).forEach(t => {
        const opt = document.createElement('option');
        opt.value = t;
        opt.textContent = t;
        if (valeursTypeActuel.includes(t)) opt.selected = true;
        selectType.appendChild(opt);
    });

    // Reconstruit les options Modèle (multi-select)
    selectModele.innerHTML = '';
    (modelsParItemType[itemType] || []).forEach(m => {
        const opt = document.createElement('option');
        opt.value = m;
        opt.textContent = m;
        if (valeursModeleActuel.includes(m)) opt.selected = true;
        selectModele.appendChild(opt);
    });
}

const selectItemType = document.getElementById('item_type');
selectItemType.addEventListener('change', () => mettreAJourTypeModele(selectItemType.value));

// Initialisation au chargement
mettreAJourTypeModele(selectItemType.value);
</script>

<?php Html::footer(); ?>
