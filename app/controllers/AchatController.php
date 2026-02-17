<?php

class AchatController
{
    private static function redirectToAchat(string $query): void
    {
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $basePath = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
        $location = $scheme . '://' . $host . $basePath . '/achat' . $query;

        Flight::response()
            ->status(303)
            ->header('Location', $location)
            ->send();
    }

    public static function showForm(): void
    {
        $success = Flight::request()->query['success'] ?? '';
        $error = Flight::request()->query['error'] ?? '';
        $villeId = (int) (Flight::request()->query['ville'] ?? 0);
        $selectedVille = $villeId > 0 ? $villeId : null;

        try {
            $repo = new DonationRepository(Flight::db());
            $villes = $repo->getVilles();
            $types = $repo->getPurchaseTypes();
            $priceList = $repo->getPurchasePriceList();
            $achats = $repo->getAchats($selectedVille);
            $argentStock = $repo->getArgentStock();
        } catch (Throwable $e) {
            $villes = [];
            $types = [];
            $priceList = [];
            $achats = [];
            $argentStock = 0;
            $error = 'Erreur DB: verifiez app/config.php et importez database/schema.sql';
        }

        Flight::render('achat', [
            'villes' => $villes,
            'types' => $types,
            'priceList' => $priceList,
            'achats' => $achats,
            'argentStock' => $argentStock,
            'selectedVille' => $selectedVille,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public static function postAchat(): void
    {
        $repo = new DonationRepository(Flight::db());
        $req = Flight::request();

        $villeId = (int) ($req->data->id_ville ?? 0);
        $typeId = (int) ($req->data->id_type ?? 0);
        $sousTypeId = (int) ($req->data->id_sous_type ?? 0);
        $quantite = (float) ($req->data->quantite ?? 0);

        try {
            $achatId = $repo->acheter($villeId, $typeId, $sousTypeId, $quantite);
            $message = rawurlencode("Achat #$achatId enregistre.");
            self::redirectToAchat("?success=$message");
        } catch (Throwable $e) {
            $message = rawurlencode($e->getMessage());
            self::redirectToAchat("?error=$message");
        }
    }
}
