<?php

class DistributionController
{
    private static function redirectToSaleOffer(
        int $villeId,
        int $typeId,
        int $sousTypeId,
        float $quantite
    ): void {
        $query = http_build_query([
            'offer_sale' => 1,
            'id_ville' => $villeId,
            'id_type' => $typeId,
            'id_sous_type' => $sousTypeId,
            'quantite' => $quantite,
            'sale_notice' => 'La ville n a pas ce besoin ouvert. Vous pouvez effectuer une vente locale.',
        ]);
        self::redirectToDistribution('?' . $query);
    }

    private static function redirectToDistribution(string $query): void
    {
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $basePath = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
        $location = $scheme . '://' . $host . $basePath . '/distribution' . $query;

        Flight::response()
            ->status(303)
            ->header('Location', $location)
            ->send();
    }

    public static function showForm(): void
    {
        $success = Flight::request()->query['success'] ?? '';
        $error = Flight::request()->query['error'] ?? '';
        $saleNotice = Flight::request()->query['sale_notice'] ?? '';
        $offerSale = (int) (Flight::request()->query['offer_sale'] ?? 0) === 1;
        $saleDefaults = [
            'id_ville' => (int) (Flight::request()->query['id_ville'] ?? 0),
            'id_type' => (int) (Flight::request()->query['id_type'] ?? 0),
            'id_sous_type' => (int) (Flight::request()->query['id_sous_type'] ?? 0),
            'quantite' => (float) (Flight::request()->query['quantite'] ?? 0),
            'taux_max_percent' => 10.0,
        ];

        try {
            $repo = new DonationRepository(Flight::db());
            $villes = $repo->getVilles();
            $types = $repo->getTypes();
            $sousTypes = $repo->getSousTypes();
            $salePriceList = $repo->getSalePriceList();
            $openNeeds = $repo->getOpenNeedsSummary();
        } catch (Throwable $e) {
            $villes = [];
            $types = [];
            $sousTypes = [];
            $salePriceList = [];
            $openNeeds = [];
            $error = 'Erreur DB: verifiez app/config.php et importez database/schema.sql';
        }

        Flight::render('distribution', [
            'villes' => $villes,
            'types' => $types,
            'sousTypes' => $sousTypes,
            'salePriceList' => $salePriceList,
            'openNeeds' => $openNeeds,
            'offerSale' => $offerSale,
            'saleDefaults' => $saleDefaults,
            'saleNotice' => $saleNotice,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public static function postDistribution(): void
    {
        $repo = new DonationRepository(Flight::db());
        $req = Flight::request();

        $villeId = (int) ($req->data->id_ville ?? 0);
        $typeId = (int) ($req->data->id_type ?? 0);
        $sousTypeId = (int) ($req->data->id_sous_type ?? 0);
        $quantite = (float) ($req->data->quantite ?? 0);

        try {
            $distributionId = $repo->distribute($villeId, $typeId, $sousTypeId, $quantite);
            $message = rawurlencode("Distribution #$distributionId enregistree.");
            self::redirectToDistribution("?success=$message");
        } catch (Throwable $e) {
            $message = (string) $e->getMessage();
            if (strpos($message, 'Aucun besoin ouvert') !== false) {
                self::redirectToSaleOffer($villeId, $typeId, $sousTypeId, $quantite);
                return;
            }

            $encoded = rawurlencode($message);
            self::redirectToDistribution("?error=$encoded");
        }
    }

    public static function postVente(): void
    {
        $repo = new DonationRepository(Flight::db());
        $req = Flight::request();

        $villeId = (int) ($req->data->id_ville ?? 0);
        $typeId = (int) ($req->data->id_type ?? 0);
        $sousTypeId = (int) ($req->data->id_sous_type ?? 0);
        $quantite = (float) ($req->data->quantite ?? 0);
        $tauxMaxPercent = (float) ($req->data->taux_max_percent ?? 10);

        try {
            $venteId = $repo->vendreProduit($villeId, $typeId, $sousTypeId, $quantite, $tauxMaxPercent);
            $message = rawurlencode("Vente #$venteId enregistree.");
            self::redirectToDistribution("?success=$message");
        } catch (Throwable $e) {
            $message = rawurlencode($e->getMessage());
            self::redirectToDistribution("?error=$message");
        }
    }
}
