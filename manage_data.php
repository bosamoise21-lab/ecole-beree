<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/payroll_helpers.php';
requireAdmin();

$pdo = getDb();

$entities = [
    'responsables' => [
        'label' => 'Responsables',
        'fields' => [
            'nom' => ['label' => 'Nom', 'type' => 'text', 'required' => true],
            'prenom' => ['label' => 'Prénom', 'type' => 'text', 'required' => true],
            'telephone' => ['label' => 'Téléphone', 'type' => 'text', 'nullable' => true],
            'email' => ['label' => 'Email', 'type' => 'email', 'nullable' => true],
            'adresse' => ['label' => 'Adresse', 'type' => 'text', 'nullable' => true],
        ],
    ],
    'enseignants' => [
        'label' => 'Enseignants',
        'fields' => [
            'matricule' => ['label' => 'Matricule', 'type' => 'text', 'required' => true],
            'nom' => ['label' => 'Nom', 'type' => 'text', 'required' => true],
            'prenom' => ['label' => 'Prénom', 'type' => 'text', 'required' => true],
            'email' => ['label' => 'Email', 'type' => 'email', 'required' => true],
            'telephone' => ['label' => 'Téléphone', 'type' => 'text', 'nullable' => true],
            'date_embauche' => ['label' => 'Date d’embauche', 'type' => 'date', 'nullable' => true],
            'salaire_mensuel' => ['label' => 'Salaire mensuel (CDF)', 'type' => 'number', 'step' => '0.01', 'nullable' => true],
        ],
    ],
    'employes' => [
        'label' => 'Employés',
        'fields' => [
            'matricule' => ['label' => 'Matricule', 'type' => 'text', 'required' => true],
            'nom' => ['label' => 'Nom', 'type' => 'text', 'required' => true],
            'prenom' => ['label' => 'Prénom', 'type' => 'text', 'required' => true],
            'poste' => ['label' => 'Poste', 'type' => 'text', 'required' => true],
            'email' => ['label' => 'Email', 'type' => 'email', 'nullable' => true],
            'telephone' => ['label' => 'Téléphone', 'type' => 'text', 'nullable' => true],
            'date_embauche' => ['label' => 'Date d’embauche', 'type' => 'date', 'nullable' => true],
            'salaire_mensuel' => ['label' => 'Salaire mensuel (CDF)', 'type' => 'number', 'step' => '0.01', 'nullable' => true],
            'actif' => ['label' => 'Employé actif', 'type' => 'checkbox'],
        ],
    ],
    'classes' => [
        'label' => 'Classes',
        'fields' => [
            'nom' => ['label' => 'Nom de la classe', 'type' => 'text', 'required' => true],
            'niveau' => ['label' => 'Niveau', 'type' => 'text', 'required' => true],
            'annee_scolaire' => ['label' => 'Année scolaire', 'type' => 'text', 'required' => true],
            'enseignant_principal_id' => ['label' => 'Enseignant principal', 'type' => 'foreign', 'source' => 'enseignants', 'nullable' => true],
            'capacite' => ['label' => 'Capacité', 'type' => 'number', 'step' => '1', 'required' => true],
        ],
    ],
    'eleves' => [
        'label' => 'Élèves',
        'fields' => [
            'matricule' => ['label' => 'Matricule', 'type' => 'text', 'required' => true],
            'nom' => ['label' => 'Nom', 'type' => 'text', 'required' => true],
            'prenom' => ['label' => 'Prénom', 'type' => 'text', 'required' => true],
            'date_naissance' => ['label' => 'Date de naissance', 'type' => 'date', 'required' => true],
            'sexe' => ['label' => 'Sexe', 'type' => 'select', 'options' => ['F' => 'Féminin', 'M' => 'Masculin', 'autre' => 'Autre'], 'required' => true],
            'adresse' => ['label' => 'Adresse', 'type' => 'text', 'nullable' => true],
            'telephone' => ['label' => 'Téléphone', 'type' => 'text', 'nullable' => true],
            'email' => ['label' => 'Email', 'type' => 'email', 'nullable' => true],
            'responsable_id' => ['label' => 'Responsable', 'type' => 'foreign', 'source' => 'responsables', 'nullable' => true],
            'actif' => ['label' => 'Élève actif', 'type' => 'checkbox'],
        ],
    ],
    'matieres' => [
        'label' => 'Matières',
        'fields' => [
            'code' => ['label' => 'Code', 'type' => 'text', 'required' => true],
            'nom' => ['label' => 'Nom', 'type' => 'text', 'required' => true],
            'coefficient' => ['label' => 'Coefficient', 'type' => 'number', 'step' => '0.01', 'required' => true],
            'enseignant_id' => ['label' => 'Enseignant', 'type' => 'foreign', 'source' => 'enseignants', 'nullable' => true],
        ],
    ],
    'inscriptions' => [
        'label' => 'Inscriptions',
        'fields' => [
            'eleve_id' => ['label' => 'Élève', 'type' => 'foreign', 'source' => 'eleves', 'required' => true],
            'classe_id' => ['label' => 'Classe', 'type' => 'foreign', 'source' => 'classes', 'required' => true],
            'date_inscription' => ['label' => 'Date d’inscription', 'type' => 'date', 'required' => true],
            'statut' => ['label' => 'Statut', 'type' => 'select', 'options' => ['inscrit' => 'Inscrit', 'transfere' => 'Transféré', 'diplome' => 'Diplômé', 'abandonne' => 'Abandonné'], 'required' => true],
        ],
    ],
    'notes' => [
        'label' => 'Notes',
        'fields' => [
            'inscription_id' => ['label' => 'Inscription', 'type' => 'foreign', 'source' => 'inscriptions', 'required' => true],
            'matiere_id' => ['label' => 'Matière', 'type' => 'foreign', 'source' => 'matieres', 'required' => true],
            'type_evaluation' => ['label' => 'Type d’évaluation', 'type' => 'select', 'options' => ['devoir' => 'Devoir', 'composition' => 'Composition', 'examen' => 'Examen', 'oral' => 'Oral'], 'required' => true],
            'note' => ['label' => 'Note', 'type' => 'number', 'step' => '0.01', 'required' => true],
            'note_sur' => ['label' => 'Note sur', 'type' => 'number', 'step' => '0.01', 'required' => true],
            'date_evaluation' => ['label' => 'Date d’évaluation', 'type' => 'date', 'required' => true],
            'commentaire' => ['label' => 'Commentaire', 'type' => 'text', 'nullable' => true],
        ],
    ],
    'presences' => [
        'label' => 'Présences',
        'fields' => [
            'eleve_id' => ['label' => 'Élève', 'type' => 'foreign', 'source' => 'eleves', 'required' => true],
            'classe_id' => ['label' => 'Classe', 'type' => 'foreign', 'source' => 'classes', 'required' => true],
            'date_presence' => ['label' => 'Date', 'type' => 'date', 'required' => true],
            'statut' => ['label' => 'Statut', 'type' => 'select', 'options' => ['present' => 'Présent', 'absent' => 'Absent', 'retard' => 'Retard', 'justifie' => 'Justifié'], 'required' => true],
            'motif' => ['label' => 'Motif', 'type' => 'text', 'nullable' => true],
        ],
    ],
    'paiements' => [
        'label' => 'Paiements',
        'fields' => [
            'eleve_id' => ['label' => 'Élève', 'type' => 'foreign', 'source' => 'eleves', 'required' => true],
            'montant' => ['label' => 'Montant', 'type' => 'number', 'step' => '0.01', 'required' => true],
            'type_paiement' => ['label' => 'Type de paiement', 'type' => 'select', 'options' => ['inscription' => 'Inscription', 'trimestre' => 'Trimestre', 'frais' => 'Frais', 'autre' => 'Autre'], 'required' => true],
            'date_paiement' => ['label' => 'Date de paiement', 'type' => 'date', 'required' => true],
            'mode_paiement' => ['label' => 'Mode de paiement', 'type' => 'select', 'options' => ['cash' => 'Espèces', 'banque' => 'Banque', 'mobile_money' => 'Mobile money', 'cheque' => 'Chèque'], 'required' => true],
            'commentaire' => ['label' => 'Commentaire', 'type' => 'text', 'nullable' => true],
        ],
    ],
    'emplois_du_temps' => [
        'label' => 'Emplois du temps',
        'fields' => [
            'classe_id' => ['label' => 'Classe', 'type' => 'foreign', 'source' => 'classes', 'required' => true],
            'matiere_id' => ['label' => 'Matière', 'type' => 'foreign', 'source' => 'matieres', 'required' => true],
            'enseignant_id' => ['label' => 'Enseignant', 'type' => 'foreign', 'source' => 'enseignants', 'required' => true],
            'jour' => ['label' => 'Jour', 'type' => 'select', 'options' => ['Lundi' => 'Lundi', 'Mardi' => 'Mardi', 'Mercredi' => 'Mercredi', 'Jeudi' => 'Jeudi', 'Vendredi' => 'Vendredi', 'Samedi' => 'Samedi'], 'required' => true],
            'heure_debut' => ['label' => 'Heure de début', 'type' => 'time', 'required' => true],
            'heure_fin' => ['label' => 'Heure de fin', 'type' => 'time', 'required' => true],
            'salle' => ['label' => 'Salle', 'type' => 'text', 'nullable' => true],
        ],
    ],
];

$foreignSources = [
    'enseignants' => 'SELECT id, CONCAT(prenom, " ", nom, " (", matricule, ")") AS label FROM enseignants ORDER BY nom, prenom',
    'responsables' => 'SELECT id, CONCAT(prenom, " ", nom) AS label FROM responsables ORDER BY nom, prenom',
    'eleves' => 'SELECT id, CONCAT(prenom, " ", nom, " (", matricule, ")") AS label FROM eleves ORDER BY nom, prenom',
    'classes' => 'SELECT id, CONCAT(nom, " - ", niveau, " (", annee_scolaire, ")") AS label FROM classes ORDER BY annee_scolaire DESC, nom',
    'matieres' => 'SELECT id, CONCAT(nom, " (", code, ")") AS label FROM matieres ORDER BY nom',
    'inscriptions' => 'SELECT i.id, CONCAT(e.prenom, " ", e.nom, " - ", c.nom, " (", i.date_inscription, ")") AS label FROM inscriptions i INNER JOIN eleves e ON e.id = i.eleve_id INNER JOIN classes c ON c.id = i.classe_id ORDER BY i.date_inscription DESC',
];

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$entity = (string) ($_GET['entity'] ?? $_POST['entity'] ?? 'eleves');
if (!isset($entities[$entity])) {
    http_response_code(400);
    exit('Type de données invalide.');
}

$definition = $entities[$entity];
$fields = $definition['fields'];
$recordId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$message = (string) ($_SESSION['manage_data_message'] ?? '');
$messageType = (string) ($_SESSION['manage_data_message_type'] ?? 'success');
unset($_SESSION['manage_data_message'], $_SESSION['manage_data_message_type']);
$values = [];
$error = '';

if (empty($_SESSION['manage_data_csrf'])) {
    $_SESSION['manage_data_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) $_SESSION['manage_data_csrf'], $token)) {
        http_response_code(403);
        exit('La session du formulaire a expiré. Rechargez la page et réessayez.');
    }

    $recordId = (int) ($_POST['id'] ?? 0);
    $values = [];
    foreach ($fields as $column => $field) {
        if ($field['type'] === 'checkbox') {
            $values[$column] = isset($_POST[$column]) ? 1 : 0;
            continue;
        }

        $value = trim((string) ($_POST[$column] ?? ''));
        if ($value === '') {
            if (!empty($field['required'])) {
                $error = 'Le champ « ' . $field['label'] . ' » est obligatoire.';
                break;
            }
            $values[$column] = !empty($field['nullable']) ? null : '';
            continue;
        }

        if ($field['type'] === 'number' && !is_numeric($value)) {
            $error = 'Le champ « ' . $field['label'] . ' » doit contenir un nombre valide.';
            break;
        }
        if ($column === 'salaire_mensuel' && $value !== '' && !isValidPayrollAmount($value)) {
            $error = 'Le salaire mensuel doit être positif et contenir deux décimales au maximum.';
            break;
        }
        if ($field['type'] === 'select' && !array_key_exists($value, $field['options'])) {
            $error = 'La valeur choisie pour « ' . $field['label'] . ' » est invalide.';
            break;
        }
        $values[$column] = $value;
    }

    if ($error === '') {
        try {
            $columns = array_keys($fields);
            if ($recordId > 0) {
                $set = implode(', ', array_map(static fn (string $column): string => "`$column` = :$column", $columns));
                $statement = $pdo->prepare("UPDATE `$entity` SET $set WHERE id = :record_id");
                $values['record_id'] = $recordId;
                $statement->execute($values);
                if ($statement->rowCount() === 0) {
                    $exists = $pdo->prepare("SELECT 1 FROM `$entity` WHERE id = :id");
                    $exists->execute([':id' => $recordId]);
                    if (!$exists->fetchColumn()) {
                        throw new RuntimeException('L’enregistrement demandé n’existe plus.');
                    }
                }
                $message = 'Les modifications ont été enregistrées.';
            } else {
                $columnSql = implode(', ', array_map(static fn (string $column): string => "`$column`", $columns));
                $parameterSql = implode(', ', array_map(static fn (string $column): string => ":$column", $columns));
                $statement = $pdo->prepare("INSERT INTO `$entity` ($columnSql) VALUES ($parameterSql)");
                $statement->execute($values);
                $message = 'La nouvelle donnée a été ajoutée.';
            }
            $_SESSION['manage_data_message'] = $message;
            $_SESSION['manage_data_message_type'] = 'success';
            header('Location: manage_data.php?entity=' . rawurlencode($entity));
            exit;
        } catch (PDOException $exception) {
            error_log('Échec de gestion des données scolaires (' . $entity . '): ' . $exception->getMessage());
            $error = 'Enregistrement impossible. Vérifiez les doublons, les valeurs saisies et les éléments liés.';
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
    }
} elseif ($recordId > 0) {
    $statement = $pdo->prepare("SELECT * FROM `$entity` WHERE id = :id");
    $statement->execute([':id' => $recordId]);
    $values = $statement->fetch() ?: [];
    if ($values === []) {
        $recordId = 0;
        $error = 'L’enregistrement demandé est introuvable.';
    }
}

$rows = $pdo->query("SELECT * FROM `$entity` ORDER BY id DESC LIMIT 100")->fetchAll();
$foreignOptions = [];
foreach ($foreignSources as $source => $query) {
    $foreignOptions[$source] = $pdo->query($query)->fetchAll();
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestion des données - École Beree</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f7fb; color: #172033; }
        .header { background: #172033; color: white; padding: 18px 32px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: #fff; text-decoration: none; }
        .container { max-width: 1200px; margin: 36px auto; padding: 0 20px; }
        .panel { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 6px 20px rgba(15,23,42,0.08); margin-bottom: 26px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; }
        .field { margin-bottom: 12px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; }
        input, select { box-sizing: border-box; width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; }
        input[type="checkbox"] { width: auto; }
        button, .button { display: inline-block; background: #2563eb; color: white; border: 0; padding: 11px 16px; border-radius: 8px; cursor: pointer; font-weight: bold; text-decoration: none; }
        .button-secondary { background: #64748b; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 11px 13px; border-bottom: 1px solid #e2e8f0; text-align: left; white-space: nowrap; }
        th { background: #2563eb; color: white; }
        .message { padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
        .actions { display: flex; gap: 8px; }
    </style>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <strong>Gestion des données scolaires</strong>
        <div style="display:flex; gap:14px;">
            <a href="dashboard.php">Tableau de bord</a>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>
    <main class="container">
        <?php if ($message !== ''): ?>
            <div class="message <?= $messageType === 'success' ? 'success' : 'error' ?>"><?= $escape($message) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="message error"><?= $escape($error) ?></div>
        <?php endif; ?>

        <section class="panel">
            <h1>Ajouter ou modifier des données</h1>
            <div class="field">
                <label for="entity">Données à gérer</label>
                <select id="entity" onchange="window.location.href='manage_data.php?entity='+encodeURIComponent(this.value)">
                    <?php foreach ($entities as $key => $item): ?>
                        <option value="<?= $escape($key) ?>" <?= $entity === $key ? 'selected' : '' ?>><?= $escape($item['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <h2><?= $recordId > 0 ? 'Modifier' : 'Ajouter' ?> : <?= $escape($definition['label']) ?></h2>
            <form method="post" action="manage_data.php?entity=<?= rawurlencode($entity) ?>">
                <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['manage_data_csrf']) ?>">
                <input type="hidden" name="entity" value="<?= $escape($entity) ?>">
                <input type="hidden" name="id" value="<?= $recordId ?>">
                <div class="grid">
                    <?php foreach ($fields as $column => $field): ?>
                        <?php $value = $values[$column] ?? ($column === 'date_inscription' ? date('Y-m-d') : ($column === 'actif' ? 1 : '')); ?>
                        <div class="field">
                            <?php if ($field['type'] === 'checkbox'): ?>
                                <label for="<?= $escape($column) ?>">
                                    <input type="checkbox" id="<?= $escape($column) ?>" name="<?= $escape($column) ?>" value="1" <?= (string) $value === '1' || $value === true ? 'checked' : '' ?>>
                                    <?= $escape($field['label']) ?>
                                </label>
                            <?php else: ?>
                                <label for="<?= $escape($column) ?>"><?= $escape($field['label']) ?><?= !empty($field['required']) ? ' *' : '' ?></label>
                                <?php if ($field['type'] === 'select' || $field['type'] === 'foreign'): ?>
                                    <select id="<?= $escape($column) ?>" name="<?= $escape($column) ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                                        <?php if (!empty($field['required'])): ?>
                                            <option value="" disabled <?= (string) $value === '' ? 'selected' : '' ?>>— Choisir —</option>
                                        <?php else: ?>
                                            <option value="">— Aucun —</option>
                                        <?php endif; ?>
                                        <?php $options = $field['type'] === 'select' ? array_map(static fn ($label, $option): array => ['id' => $option, 'label' => $label], $field['options'], array_keys($field['options'])) : $foreignOptions[$field['source']]; ?>
                                        <?php foreach ($options as $option): ?>
                                            <option value="<?= $escape($option['id']) ?>" <?= (string) $value === (string) $option['id'] ? 'selected' : '' ?>><?= $escape($option['label']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input
                                        type="<?= $escape($field['type']) ?>"
                                        id="<?= $escape($column) ?>"
                                        name="<?= $escape($column) ?>"
                                        value="<?= $escape($value) ?>"
                                        <?= isset($field['step']) ? 'step="' . $escape($field['step']) . '"' : '' ?>
                                        <?= !empty($field['required']) ? 'required' : '' ?>
                                    >
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit"><?= $recordId > 0 ? 'Enregistrer les modifications' : 'Ajouter' ?></button>
                <?php if ($recordId > 0): ?>
                    <a class="button button-secondary" href="manage_data.php?entity=<?= rawurlencode($entity) ?>">Annuler</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="panel">
            <h2>Enregistrements récents — <?= $escape($definition['label']) ?></h2>
            <p>Les 100 enregistrements les plus récents sont affichés.</p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php foreach ($fields as $field): ?><th><?= $escape($field['label']) ?></th><?php endforeach; ?>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= (int) $row['id'] ?></td>
                                <?php foreach ($fields as $column => $field): ?>
                                    <td><?= $escape($field['type'] === 'checkbox' ? ((int) $row[$column] === 1 ? 'Oui' : 'Non') : ($row[$column] ?? '—')) ?></td>
                                <?php endforeach; ?>
                                <td class="actions">
                                    <a class="button" href="manage_data.php?entity=<?= rawurlencode($entity) ?>&amp;id=<?= (int) $row['id'] ?>">Modifier</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($rows === []): ?>
                            <tr><td colspan="<?= count($fields) + 2 ?>">Aucune donnée enregistrée pour le moment.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
