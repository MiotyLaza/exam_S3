<?php

class RecapController
{
    public static function showRecap(): void
    {
        try {
            $repo = new DonationRepository(Flight::db());
            $recap = $repo->getRecapMontants();
            $error = '';
        } catch (Throwable $e) {
            $recap = [
                'besoins_totaux_montant' => 0,
                'besoins_satisfaits_montant' => 0,
                'dons_recus_montant' => 0,
                'dons_dispatches_montant' => 0,
                'achats_effectues_montant' => 0,
                'ventes_effectuees_montant' => 0,
                'solde_argent_restant' => 0,
            ];
            $error = 'Erreur DB: verifiez app/config.php et importez database/schema.sql';
        }

        Flight::render('recapitulatif', [
            'recap' => $recap,
            'error' => $error,
        ]);
    }

    public static function apiRecap(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        try {
            $repo = new DonationRepository(Flight::db());
            http_response_code(200);
            echo json_encode([
                'ok' => true,
                'data' => $repo->getRecapMontants(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }
}
