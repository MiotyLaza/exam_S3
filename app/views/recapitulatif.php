<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recapitulatif</title>
  <link rel="stylesheet" href="../bootstrap-5.3.5-dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
  <style>
    body.page-recap,
    body.page-recap * {
      color: #fff !important;
    }
    body.page-recap ::placeholder {
      color: #fff !important;
      opacity: 1 !important;
    }
  </style>
</head>
<body class="bg-light page-recap">
  <?php
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $basePath = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
  ?>
  <div class="container py-4 page-shell">
    <div class="brand-header">
      <img class="brand-logo" src="image/bngrclogo.png" alt="Logo BNGRC">
      <p class="brand-title">BNGRC</p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 m-0 page-title">Recapitulatif global</h1>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-dark" href="distribution">Distribution</a>
        <a class="btn btn-outline-dark" href="achat">Achat</a>
        <a class="btn btn-outline-dark" href="dashboard">Tableau de bord</a>
      </div>
    </div>

    <?php if (!empty($error)) { ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php } ?>

    <div class="mb-3">
      <button id="refreshBtn" class="btn btn-primary">Actualiser</button>
    </div>

    <div class="row g-3" id="recap-grid">
      <div class="col-md-4">
        <div class="card"><div class="card-body"><div class="text-muted">Besoins totaux</div><div id="besoins_totaux_montant" class="h5 mb-0"></div></div></div>
      </div>
      <div class="col-md-4">
        <div class="card"><div class="card-body"><div class="text-muted">Besoins satisfaits</div><div id="besoins_satisfaits_montant" class="h5 mb-0"></div></div></div>
      </div>
      <div class="col-md-4">
        <div class="card"><div class="card-body"><div class="text-muted">Dons recus</div><div id="dons_recus_montant" class="h5 mb-0"></div></div></div>
      </div>
      <div class="col-md-4">
        <div class="card"><div class="card-body"><div class="text-muted">Dons dispatches</div><div id="dons_dispatches_montant" class="h5 mb-0"></div></div></div>
      </div>
      <div class="col-md-4">
        <div class="card"><div class="card-body"><div class="text-muted">Achats effectues</div><div id="achats_effectues_montant" class="h5 mb-0"></div></div></div>
      </div>
      <div class="col-md-4">
        <div class="card"><div class="card-body"><div class="text-muted">Ventes effectuees</div><div id="ventes_effectuees_montant" class="h5 mb-0"></div></div></div>
      </div>
      <div class="col-md-4">
        <div class="card"><div class="card-body"><div class="text-muted">Solde argent restant</div><div id="solde_argent_restant" class="h5 mb-0"></div></div></div>
      </div>
    </div>
  </div>

  <script>
    const initialRecap = <?= json_encode($recap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const recapApiUrl = <?= json_encode($basePath . '/api/recapitulatif', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function fmt(value) {
      return Number(value || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' Ar';
    }

    function renderRecap(data) {
      document.getElementById('besoins_totaux_montant').textContent = fmt(data.besoins_totaux_montant);
      document.getElementById('besoins_satisfaits_montant').textContent = fmt(data.besoins_satisfaits_montant);
      document.getElementById('dons_recus_montant').textContent = fmt(data.dons_recus_montant);
      document.getElementById('dons_dispatches_montant').textContent = fmt(data.dons_dispatches_montant);
      document.getElementById('achats_effectues_montant').textContent = fmt(data.achats_effectues_montant);
      document.getElementById('ventes_effectuees_montant').textContent = fmt(data.ventes_effectuees_montant);
      document.getElementById('solde_argent_restant').textContent = fmt(data.solde_argent_restant);
    }

    async function refreshRecap() {
      const response = await fetch(recapApiUrl, { headers: { 'Accept': 'application/json' } });
      const raw = await response.text();
      let payload;
      try {
        payload = JSON.parse(raw);
      } catch (e) {
        throw new Error('Reponse API invalide (non JSON)');
      }
      if (!payload.ok) {
        throw new Error(payload.error || ('HTTP ' + response.status));
      }
      renderRecap(payload.data);
    }

    document.getElementById('refreshBtn').addEventListener('click', async () => {
      const btn = document.getElementById('refreshBtn');
      btn.disabled = true;
      try {
        await refreshRecap();
      } catch (e) {
        alert('Erreur lors de l actualisation: ' + (e.message || 'inconnue'));
      } finally {
        btn.disabled = false;
      }
    });

    renderRecap(initialRecap);
  </script>
  <script src="js/page-transition.js"></script>
</body>
</html>
