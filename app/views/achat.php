<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Page achat</title>
  <link rel="stylesheet" href="../bootstrap-5.3.5-dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light page-achat">
  <div class="container py-4 page-shell">
    <div class="brand-header">
      <img class="brand-logo" src="image/bngrclogo.png" alt="Logo BNGRC">
      <p class="brand-title">BNGRC</p>
    </div>

    <h1 class="h3 m-0 page-title">Page achat</h1>
    <div class="d-flex gap-2 justify-content-center mb-4 page-nav">
      <a class="btn btn-outline-dark" href="distribution">Distribution</a>
      <a class="btn btn-outline-dark" href="dashboard">Tableau de bord</a>
      <a class="btn btn-outline-dark" href="recapitulatif">Recapitulatif</a>
    </div>

    <?php if (!empty($success)) { ?>
      <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php } ?>
    <?php if (!empty($error)) { ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php } ?>

    <div class="alert alert-info">
      Solde argent disponible: <strong id="argent-stock"><?= number_format((float) $argentStock, 2, '.', ' ') ?> arriary</strong>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <form method="post" action="achat">
          <div class="form-stack">
            <div class="form-block">
              <label class="form-label d-block">Ville sinistree</label>
              <div class="city-picks">
                <?php foreach ($villes as $index => $ville) { ?>
                  <label class="city-card">
                    <input type="radio" name="id_ville" value="<?= (int) $ville['id_ville'] ?>" <?= $index === 0 ? 'required' : '' ?>>
                    <img
                      src="image/<?= htmlspecialchars($ville['image_ville'], ENT_QUOTES, 'UTF-8') ?>"
                      alt="<?= htmlspecialchars($ville['nom_ville'], ENT_QUOTES, 'UTF-8') ?>"
                      onerror="this.style.display='none';"
                    >
                    <span><?= htmlspecialchars($ville['nom_ville'], ENT_QUOTES, 'UTF-8') ?></span>
                  </label>
                <?php } ?>
              </div>
            </div>

            <div class="form-block">
              <label class="form-label" for="id_type">Type de don</label>
              <select class="form-select" id="id_type" name="id_type" required>
                <option value="">Choisir un type</option>
                <?php foreach ($types as $type) { ?>
                  <option value="<?= (int) $type['id_type'] ?>"><?= htmlspecialchars($type['nom_type'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php } ?>
              </select>
            </div>

            <div class="form-block">
              <label class="form-label d-block">Sous-type</label>
              <div class="subtype-picks" id="id_sous_type_list"></div>
            </div>

            <div class="form-block">
              <label class="form-label" for="quantite">Quantite</label>
              <input class="form-control" type="number" step="0.01" min="0.01" id="quantite" name="quantite" required>
            </div>

            <div class="form-block">
              <label class="form-label" for="unite_label">Unite</label>
              <input class="form-control" type="text" id="unite_label" readonly>
            </div>

            <div class="form-block">
              <label class="form-label" for="prix_unitaire">Prix unitaire (arriary)</label>
              <input class="form-control" type="text" id="prix_unitaire" readonly>
            </div>

            <div class="form-block">
              <label class="form-label" for="montant_total">Montant total (arriary)</label>
              <input class="form-control" type="text" id="montant_total" readonly>
            </div>
          </div>

          <div class="mt-4 form-actions">
            <button type="submit" class="btn btn-primary">Valider l'achat</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <form class="row g-3 align-items-end mb-3" method="get" action="achat">
          <div class="col-12">
            <label class="form-label d-block">Filtrer liste des achats par ville</label>
            <div class="city-picks">
              <?php foreach ($villes as $ville) { ?>
                <label class="city-card">
                  <input
                    type="radio"
                    name="ville"
                    value="<?= (int) $ville['id_ville'] ?>"
                    <?= ((int) $selectedVille === (int) $ville['id_ville']) ? 'checked' : '' ?>
                  >
                  <img
                    src="image/<?= htmlspecialchars($ville['image_ville'], ENT_QUOTES, 'UTF-8') ?>"
                    alt="<?= htmlspecialchars($ville['nom_ville'], ENT_QUOTES, 'UTF-8') ?>"
                    onerror="this.style.display='none';"
                  >
                  <span><?= htmlspecialchars($ville['nom_ville'], ENT_QUOTES, 'UTF-8') ?></span>
                </label>
              <?php } ?>
            </div>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-secondary">Filtrer</button>
            <a href="achat" class="btn btn-outline-secondary">Reinitialiser</a>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-sm table-striped mb-0">
            <thead>
              <tr>
                <th>ID Achat</th>
                <th>Ville</th>
                <th>Type</th>
                <th>Sous-type</th>
                <th>Quantite</th>
                <th>Unite</th>
                <th>Prix unitaire</th>
                <th>Montant total</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($achats as $achat) { ?>
                <tr>
                  <td><?= (int) $achat['id_achat'] ?></td>
                  <td><?= htmlspecialchars($achat['nom_ville'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($achat['nom_type'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($achat['nom_sous_type'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= (float) $achat['quantite_achetee'] ?></td>
                  <td><?= htmlspecialchars($achat['unite'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= number_format((float) $achat['prix_unitaire'], 2, '.', ' ') ?></td>
                  <td><?= number_format((float) $achat['montant_total'], 2, '.', ' ') ?></td>
                  <td><?= htmlspecialchars($achat['date_achat'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    const priceList = <?= json_encode($priceList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const typeSelect = document.getElementById('id_type');
    const sousTypeList = document.getElementById('id_sous_type_list');
    const quantiteInput = document.getElementById('quantite');
    const uniteLabel = document.getElementById('unite_label');
    const prixUnitaire = document.getElementById('prix_unitaire');
    const montantTotal = document.getElementById('montant_total');

    function refreshSousTypes() {
      const selectedType = parseInt(typeSelect.value, 10);
      sousTypeList.innerHTML = '';
      uniteLabel.value = '';
      prixUnitaire.value = '';
      montantTotal.value = '';

      if (!selectedType) return;

      const filtered = priceList
        .filter((item) => parseInt(item.id_type, 10) === selectedType);

      if (!filtered.length) {
        sousTypeList.innerHTML = '<div class="text-muted small">Aucun sous-type disponible</div>';
        return;
      }

      filtered.forEach((item, index) => {
        const label = document.createElement('label');
        label.className = 'subtype-card';

        const input = document.createElement('input');
        input.type = 'radio';
        input.name = 'id_sous_type';
        input.value = item.id_sous_type;
        input.required = index === 0;
        input.dataset.unite = item.unite_defaut;
        input.dataset.prix = item.prix_unitaire;

        const img = document.createElement('img');
        img.src = `image/${item.image_sous_type}`;
        img.alt = item.nom_sous_type;
        img.onerror = function () { this.style.display = 'none'; };

        const text = document.createElement('span');
        text.textContent = item.nom_sous_type;

        label.appendChild(input);
        label.appendChild(img);
        label.appendChild(text);
        sousTypeList.appendChild(label);
      });
    }

    function getSelectedSousTypeInput() {
      return sousTypeList.querySelector('input[name="id_sous_type"]:checked');
    }

    function refreshSelectionData() {
      const selected = getSelectedSousTypeInput();
      uniteLabel.value = selected ? (selected.dataset.unite || '') : '';
      refreshMontant();
    }

    function refreshMontant() {
      const selected = getSelectedSousTypeInput();
      const prix = selected ? parseFloat(selected.dataset.prix || '0') : 0;
      const qte = parseFloat(quantiteInput.value || '0');
      prixUnitaire.value = prix ? prix.toFixed(2) : '';
      montantTotal.value = prix && qte ? (prix * qte).toFixed(2) : '';
    }

    typeSelect.addEventListener('change', refreshSousTypes);
    sousTypeList.addEventListener('change', refreshSelectionData);
    quantiteInput.addEventListener('input', refreshMontant);
  </script>
  <script src="js/page-transition.js"></script>
</body>
</html>
