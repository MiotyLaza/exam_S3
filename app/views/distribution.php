<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Distribution des dons</title>
  <link rel="stylesheet" href="../bootstrap-5.3.5-dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light page-distribution">
  <div class="container py-4 page-shell">
    <div class="brand-header">
      <img class="brand-logo" src="image/bngrclogo.png" alt="Logo BNGRC">
      <p class="brand-title">BNGRC</p>
    </div>

    <h1 class="h3 m-0 page-title">Page distribution</h1>
    <div class="d-flex gap-2 justify-content-center mb-4 page-nav">
      <a class="btn btn-outline-dark" href="achat">Achat</a>
      <a class="btn btn-outline-dark" href="dashboard">Tableau de bord</a>
      <a class="btn btn-outline-dark" href="recapitulatif">Recapitulatif</a>
    </div>

    <?php if (!empty($success)) { ?>
      <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php } ?>
    <?php if (!empty($saleNotice)) { ?>
      <div class="alert alert-warning"><?= htmlspecialchars($saleNotice, ENT_QUOTES, 'UTF-8') ?></div>
    <?php } ?>
    <?php if (!empty($error)) { ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php } ?>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5 mb-3">Besoins ouverts actuellement</h2>
        <?php if (empty($openNeeds)) { ?>
          <div class="alert alert-warning mb-0">Aucun besoin ouvert disponible.</div>
        <?php } else { ?>
          <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
              <thead>
                <tr>
                  <th>Ville</th>
                  <th>Type</th>
                  <th>Sous-type</th>
                  <th>Besoin restant</th>
                  <th>Stock disponible</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($openNeeds as $need) { ?>
                  <tr>
                    <td><?= htmlspecialchars($need['nom_ville'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($need['nom_type'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($need['nom_sous_type'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (float) $need['quantite_restante'] . ' ' . htmlspecialchars($need['unite'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (float) $need['stock_disponible'] . ' ' . htmlspecialchars($need['unite'], ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        <?php } ?>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <form method="post" action="distribution">
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
              <label class="form-label d-block">Sous-type de don</label>
              <div class="subtype-picks" id="id_sous_type_list"></div>
            </div>

            <div class="form-block">
              <label class="form-label" for="quantite">Quantite a distribuer</label>
              <input class="form-control" type="number" step="0.01" min="0.01" id="quantite" name="quantite" required>
            </div>

            <div class="form-block">
              <label class="form-label" for="unite_label">Unite</label>
              <input class="form-control" type="text" id="unite_label" readonly placeholder="auto">
            </div>

          </div>

          <div class="mt-4 form-actions">
            <button type="submit" class="btn btn-primary">Valider la distribution</button>
          </div>
        </form>
      </div>
    </div>

    <div id="saleCard" class="card shadow-sm d-none">
      <div class="card-body">
        <h2 class="h5 mb-3">Vente locale (si aucun besoin ouvert)</h2>
        <div class="alert alert-warning">
          Vente autorisee uniquement si le sous-type n'existe dans aucun besoin ouvert.
        </div>

        <form id="saleForm" method="post" action="distribution/vente">
          <input type="hidden" name="id_ville" id="sale_id_ville">
          <input type="hidden" name="id_type" id="sale_id_type">
          <input type="hidden" name="id_sous_type" id="sale_id_sous_type">

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="sale_quantite">Quantite vendue (auto)</label>
              <input class="form-control" type="number" step="0.01" min="0.01" id="sale_quantite" name="quantite" readonly required>
            </div>

            <div class="col-md-4">
              <label class="form-label" for="sale_unite">Unite</label>
              <input class="form-control" type="text" id="sale_unite" readonly>
            </div>

            <div class="col-md-4">
              <label class="form-label" for="sale_prix_achat_reference">Prix achat reference</label>
              <input class="form-control" type="text" id="sale_prix_achat_reference" readonly>
            </div>

            <div class="col-md-4">
              <label class="form-label" for="sale_taux_max_percent">Plafond (%)</label>
              <input class="form-control" type="number" step="0.01" min="0.01" max="100" id="sale_taux_max_percent" name="taux_max_percent" value="10" required>
            </div>

            <div class="col-md-4">
              <label class="form-label" for="sale_prix_vente_unitaire">Prix vente unitaire (auto)</label>
              <input class="form-control" type="text" id="sale_prix_vente_unitaire" readonly>
            </div>

            <div class="col-md-4">
              <label class="form-label" for="sale_montant_total">Montant total</label>
              <input class="form-control" type="text" id="sale_montant_total" readonly>
            </div>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn btn-primary">Valider la vente</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    const sousTypes = <?= json_encode($sousTypes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const salePriceList = <?= json_encode($salePriceList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const openNeeds = <?= json_encode($openNeeds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const offerSale = <?= !empty($offerSale) ? 'true' : 'false' ?>;
    const saleDefaults = <?= json_encode($saleDefaults ?? ['id_ville' => 0, 'id_type' => 0, 'id_sous_type' => 0, 'quantite' => 0, 'taux_max_percent' => 10], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const typeSelect = document.getElementById('id_type');
    const sousTypeList = document.getElementById('id_sous_type_list');
    const uniteLabel = document.getElementById('unite_label');
    const saleCard = document.getElementById('saleCard');
    const saleForm = document.getElementById('saleForm');
    const saleIdVille = document.getElementById('sale_id_ville');
    const saleIdType = document.getElementById('sale_id_type');
    const saleIdSousType = document.getElementById('sale_id_sous_type');
    const saleQuantite = document.getElementById('sale_quantite');
    const saleUnite = document.getElementById('sale_unite');
    const salePrixAchat = document.getElementById('sale_prix_achat_reference');
    const saleTauxMax = document.getElementById('sale_taux_max_percent');
    const salePrixVente = document.getElementById('sale_prix_vente_unitaire');
    const saleMontantTotal = document.getElementById('sale_montant_total');
    const openNeedSousTypeSet = new Set(openNeeds.map((n) => parseInt(n.id_besoin ? n.id_sous_type : n.id_sous_type, 10)).filter((v) => !Number.isNaN(v)));

    function getCurrentSousType() {
      const selected = sousTypeList.querySelector('input[name="id_sous_type"]:checked');
      if (!selected) return null;
      const id = parseInt(selected.value, 10);
      return sousTypes.find((item) => parseInt(item.id_sous_type, 10) === id) || null;
    }

    function getSalePriceItem() {
      const selected = sousTypeList.querySelector('input[name="id_sous_type"]:checked');
      if (!selected) return null;
      const id = parseInt(selected.value, 10);
      return salePriceList.find((item) => parseInt(item.id_sous_type, 10) === id) || null;
    }

    function displaySaleCardFromCurrentSelection() {
      const selected = sousTypeList.querySelector('input[name="id_sous_type"]:checked');
      saleCard.classList.add('d-none');
      if (!selected) return;

      const selectedSousTypeId = parseInt(selected.value, 10);
      const saleItem = getSalePriceItem();
      if (!openNeedSousTypeSet.has(selectedSousTypeId) && saleItem) {
        const selectedVille = document.querySelector('input[name="id_ville"]:checked');
        saleIdVille.value = selectedVille ? selectedVille.value : '';
        saleIdType.value = typeSelect.value || '';
        saleIdSousType.value = selectedSousTypeId;
        saleUnite.value = selected.dataset.unite || '';

        const prixAchat = parseFloat(saleItem.prix_unitaire || '0');
        const taux = parseFloat(saleTauxMax.value || '0');
        const prixVente = prixAchat * (1 - (taux / 100));
        salePrixAchat.value = prixAchat ? prixAchat.toFixed(2) : '';
        salePrixVente.value = prixVente ? prixVente.toFixed(2) : '';
        saleMontantTotal.value = '';
        syncSaleQuantite();
        saleCard.classList.remove('d-none');
      }
    }

    function refreshSousTypes() {
      const selectedType = parseInt(typeSelect.value, 10);
      sousTypeList.innerHTML = '';
      uniteLabel.value = '';
      saleCard.classList.add('d-none');

      if (!selectedType) return;

      const filtered = sousTypes
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

    typeSelect.addEventListener('change', refreshSousTypes);
    sousTypeList.addEventListener('change', function () {
      const selected = sousTypeList.querySelector('input[name="id_sous_type"]:checked');
      uniteLabel.value = selected ? (selected.dataset.unite || '') : '';
      displaySaleCardFromCurrentSelection();
    });

    document.querySelectorAll('input[name="id_ville"]').forEach((input) => {
      input.addEventListener('change', function () {
        saleIdVille.value = this.value || '';
      });
    });

    function syncSaleQuantite() {
      const quantite = parseFloat(document.getElementById('quantite').value || '0');
      saleQuantite.value = quantite ? quantite.toFixed(2) : '';
      const prix = parseFloat(salePrixVente.value || '0');
      saleMontantTotal.value = (quantite && prix) ? (quantite * prix).toFixed(2) : '';
    }

    function refreshSalePrix() {
      const saleItem = getSalePriceItem();
      if (!saleItem) return;
      const prixAchat = parseFloat(saleItem.prix_unitaire || '0');
      const taux = parseFloat(saleTauxMax.value || '0');
      const prixVente = prixAchat * (1 - (taux / 100));
      salePrixAchat.value = prixAchat ? prixAchat.toFixed(2) : '';
      salePrixVente.value = prixVente ? prixVente.toFixed(2) : '';
      syncSaleQuantite();
    }

    document.getElementById('quantite').addEventListener('input', syncSaleQuantite);
    saleTauxMax.addEventListener('input', refreshSalePrix);

    saleForm.addEventListener('submit', function (event) {
      if (!saleIdVille.value || !saleIdType.value || !saleIdSousType.value) {
        event.preventDefault();
        alert('Selection invalide pour la vente. Choisissez une ville, un type et un sous-type.');
        return;
      }
      syncSaleQuantite();
    });

    if (offerSale) {
      const defaultVille = parseInt(saleDefaults.id_ville || '0', 10);
      const defaultType = parseInt(saleDefaults.id_type || '0', 10);
      const defaultSousType = parseInt(saleDefaults.id_sous_type || '0', 10);
      const defaultQuantite = parseFloat(saleDefaults.quantite || '0');
      const defaultTaux = parseFloat(saleDefaults.taux_max_percent || '10');

      if (defaultTaux > 0) {
        saleTauxMax.value = defaultTaux.toFixed(2);
      }
      if (defaultQuantite > 0) {
        document.getElementById('quantite').value = defaultQuantite.toFixed(2);
      }

      if (defaultVille > 0) {
        const villeInput = document.querySelector(`input[name="id_ville"][value="${defaultVille}"]`);
        if (villeInput) {
          villeInput.checked = true;
          saleIdVille.value = String(defaultVille);
        }
      }

      if (defaultType > 0) {
        typeSelect.value = String(defaultType);
        refreshSousTypes();
      }

      if (defaultSousType > 0) {
        const sousTypeInput = document.querySelector(`input[name="id_sous_type"][value="${defaultSousType}"]`);
        if (sousTypeInput) {
          sousTypeInput.checked = true;
          uniteLabel.value = sousTypeInput.dataset.unite || '';
        }
      }

      displaySaleCardFromCurrentSelection();
      refreshSalePrix();
      syncSaleQuantite();
    }
  </script>
  <script src="js/page-transition.js"></script>
</body>
</html>
