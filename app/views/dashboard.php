<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tableau de bord - Dons</title>
  <link rel="stylesheet" href="../bootstrap-5.3.5-dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light page-dashboard">
  <?php
    $totalOpen = 0;
    $totalPartiel = 0;
    $totalSatisfait = 0;
    $totalNature = 0;
    $totalMateriaux = 0;
    $totalArgent = 0;
    $montantAchatGlobal = 0.0;

    foreach ($summary as $row) {
      $totalOpen += (int) $row['besoin_ouvert'];
      $totalPartiel += (int) $row['besoin_partiel'];
      $totalSatisfait += (int) $row['besoin_satisfait'];
      $totalNature += (int) $row['besoin_nature'];
      $totalMateriaux += (int) $row['besoin_materiaux'];
      $totalArgent += (int) $row['besoin_argent'];
      $montantAchatGlobal += (float) $row['montant_achat'];
    }

    $countBesoins = count($besoins);
    $countDistributions = count($historique);
    $countAchats = count($achats);

    $statusesTotal = max(1, $totalOpen + $totalPartiel + $totalSatisfait);
    $pOpen = ($totalOpen / $statusesTotal) * 100;
    $pPartiel = ($totalPartiel / $statusesTotal) * 100;
    $pSatisfait = ($totalSatisfait / $statusesTotal) * 100;

    $seriesAchat = [];
    foreach ($achats as $a) {
      $key = substr((string) $a['date_achat'], 0, 10);
      if (!isset($seriesAchat[$key])) {
        $seriesAchat[$key] = 0.0;
      }
      $seriesAchat[$key] += (float) $a['montant_total'];
    }

    $seriesDist = [];
    foreach ($historique as $d) {
      $key = substr((string) $d['date_distribution'], 0, 10);
      if (!isset($seriesDist[$key])) {
        $seriesDist[$key] = 0.0;
      }
      $seriesDist[$key] += (float) $d['quantite_distribuee'];
    }

    $allDates = array_values(array_unique(array_merge(array_keys($seriesAchat), array_keys($seriesDist))));
    sort($allDates);
    if (count($allDates) > 8) {
      $allDates = array_slice($allDates, -8);
    }

    $chartAchat = [];
    $chartDist = [];
    foreach ($allDates as $dt) {
      $chartAchat[] = (float) ($seriesAchat[$dt] ?? 0);
      $chartDist[] = (float) ($seriesDist[$dt] ?? 0);
    }

    $allValues = array_merge($chartAchat, $chartDist);
    $maxVal = !empty($allValues) ? max($allValues) : 1;
    $maxVal = $maxVal <= 0 ? 1 : $maxVal;

    $w = 820;
    $h = 260;
    $pad = 26;
    $countPts = max(2, count($allDates));
    $stepX = ($w - 2 * $pad) / ($countPts - 1);

    $toPoints = static function (array $vals) use ($w, $h, $pad, $stepX, $maxVal): string {
      if (empty($vals)) {
        return '';
      }
      $pts = [];
      foreach ($vals as $i => $v) {
        $x = $pad + ($i * $stepX);
        $y = $h - $pad - (($v / $maxVal) * ($h - 2 * $pad));
        $pts[] = number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
      }
      return implode(' ', $pts);
    };

    $ptsAchat = $toPoints($chartAchat);
    $ptsDist = $toPoints($chartDist);
    $recentFeed = array_slice(array_merge($achats, $historique), 0, 6);
  ?>

  <div class="container py-4 page-shell">
    <div class="brand-header">
      <img class="brand-logo" src="image/bngrclogo.png" alt="Logo BNGRC">
      <p class="brand-title">BNGRC</p>
    </div>

    <h1 class="h3 m-0 page-title">Tableau de bord</h1>
    <div class="d-flex gap-2 justify-content-center mb-4 page-nav">
      <a class="btn btn-outline-dark" href="distribution">Distribution</a>
      <a class="btn btn-outline-dark" href="achat">Achat</a>
      <a class="btn btn-outline-dark" href="recapitulatif">Recapitulatif</a>
    </div>

    <?php if (!empty($error)) { ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php } ?>

    <form class="card card-body mb-4" method="get" action="dashboard">
      <div class="form-stack">
        <div class="form-block">
          <label class="form-label d-block">Filtrer par ville</label>
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
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Appliquer</button>
          <a href="dashboard" class="btn btn-secondary">Reinitialiser</a>
        </div>
      </div>
    </form>

    <div class="dashboard-grid">
      <div class="dash-panel">
        <div class="dash-panel-title">Vue Globale</div>
        <div class="kpi-grid">
          <div class="kpi-box">
            <div class="kpi-label">Besoins</div>
            <div class="kpi-value"><?= $countBesoins ?></div>
          </div>
          <div class="kpi-box">
            <div class="kpi-label">Distributions</div>
            <div class="kpi-value"><?= $countDistributions ?></div>
          </div>
          <div class="kpi-box">
            <div class="kpi-label">Achats</div>
            <div class="kpi-value"><?= $countAchats ?></div>
          </div>
          <div class="kpi-box">
            <div class="kpi-label">Montant achats</div>
            <div class="kpi-value"><?= number_format($montantAchatGlobal, 0, '.', ' ') ?> Ar</div>
          </div>
        </div>
      </div>

      <div class="dash-panel">
        <div class="dash-panel-title">Courbe Activite</div>
        <svg class="dash-chart" viewBox="0 0 820 260" preserveAspectRatio="none" aria-label="Courbe d activite">
          <g class="dash-grid-lines">
            <line x1="26" y1="26" x2="794" y2="26"></line>
            <line x1="26" y1="87" x2="794" y2="87"></line>
            <line x1="26" y1="148" x2="794" y2="148"></line>
            <line x1="26" y1="209" x2="794" y2="209"></line>
          </g>
          <?php if ($ptsAchat !== '') { ?>
            <polyline class="line-achat" points="<?= htmlspecialchars($ptsAchat, ENT_QUOTES, 'UTF-8') ?>"></polyline>
          <?php } ?>
          <?php if ($ptsDist !== '') { ?>
            <polyline class="line-dist" points="<?= htmlspecialchars($ptsDist, ENT_QUOTES, 'UTF-8') ?>"></polyline>
          <?php } ?>
        </svg>
        <div class="dash-legend">
          <span><i class="dot dot-achat"></i> Montant achats</span>
          <span><i class="dot dot-dist"></i> Quantite distribuee</span>
        </div>
      </div>

      <div class="dash-panel">
        <div class="dash-panel-title">Repartition Des Statuts</div>
        <div
          class="status-donut"
          style="--p-open: <?= number_format($pOpen, 2, '.', '') ?>; --p-partiel: <?= number_format($pPartiel, 2, '.', '') ?>; --p-satisfait: <?= number_format($pSatisfait, 2, '.', '') ?>;"
        >
          <div class="status-donut-center"><?= $statusesTotal ?></div>
        </div>
        <div class="status-list">
          <div><span class="status-dot status-rien"></span> Ouvert: <?= $totalOpen ?></div>
          <div><span class="status-dot status-moyen"></span> Partiel: <?= $totalPartiel ?></div>
          <div><span class="status-dot status-satisfait"></span> Satisfait: <?= $totalSatisfait ?></div>
        </div>
      </div>

      <div class="dash-panel">
        <div class="dash-panel-title">Types De Besoin</div>
        <div class="type-bars">
          <div class="type-row">
            <span>Nature</span>
            <strong><?= $totalNature ?></strong>
          </div>
          <div class="type-row">
            <span>Materiaux</span>
            <strong><?= $totalMateriaux ?></strong>
          </div>
          <div class="type-row">
            <span>Argent</span>
            <strong><?= $totalArgent ?></strong>
          </div>
        </div>
      </div>

      <div class="dash-panel dash-panel-wide">
        <div class="dash-panel-title">Activite Recente</div>
        <div class="feed-list">
          <?php if (empty($recentFeed)) { ?>
            <div class="feed-item">Aucune activite recente.</div>
          <?php } else { ?>
            <?php foreach ($recentFeed as $item) { ?>
              <?php
                $isAchat = isset($item['id_achat']);
                $title = $isAchat ? 'Achat' : 'Distribution';
                $ville = htmlspecialchars($item['nom_ville'], ENT_QUOTES, 'UTF-8');
                $sousType = htmlspecialchars($item['nom_sous_type'], ENT_QUOTES, 'UTF-8');
              ?>
              <div class="feed-item">
                <span class="feed-tag <?= $isAchat ? 'feed-achat' : 'feed-dist' ?>"><?= $title ?></span>
                <span><?= $ville ?> - <?= $sousType ?></span>
              </div>
            <?php } ?>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>
  <script src="js/page-transition.js"></script>
</body>
</html>
