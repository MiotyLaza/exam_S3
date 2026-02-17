<?php

class DashboardController
{
    public static function showDashboard(): void
    {
        $villeId = (int) (Flight::request()->query['ville'] ?? 0);
        $selectedVille = $villeId > 0 ? $villeId : null;

        try {
            $repo = new DonationRepository(Flight::db());
            $data = [
                'villes' => $repo->getVilles(),
                'selectedVille' => $selectedVille,
                'summary' => $repo->getDashboardSummary($selectedVille),
                'besoins' => $repo->getBesoinsByVille($selectedVille),
                'historique' => $repo->getDistributionHistory($selectedVille),
                'achats' => $repo->getAchats($selectedVille),
                'error' => '',
            ];
        } catch (Throwable $e) {
            $data = [
                'villes' => [],
                'selectedVille' => $selectedVille,
                'summary' => [],
                'besoins' => [],
                'historique' => [],
                'achats' => [],
                'error' => 'Erreur DB: verifiez app/config.php et importez database/schema.sql',
            ];
        }

        Flight::render('dashboard', $data);
    }
}
