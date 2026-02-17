<?php

class DonationRepository
{
    private PDO $pdo;
    private ?bool $villeImageColumnExists = null;
    private ?bool $sousTypeImageColumnExists = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getVilles(): array
    {
        $imageSelect = $this->hasVilleImageColumn()
            ? 'image_ville'
            : "CONCAT(LOWER(nom_ville), '.jpg') AS image_ville";
        $st = $this->pdo->query("SELECT id_ville, nom_ville, $imageSelect FROM ville ORDER BY nom_ville");
        return $st->fetchAll();
    }

    private function hasVilleImageColumn(): bool
    {
        if ($this->villeImageColumnExists !== null) {
            return $this->villeImageColumnExists;
        }

        $st = $this->pdo->query("SHOW COLUMNS FROM ville LIKE 'image_ville'");
        $this->villeImageColumnExists = (bool) $st->fetch();
        return $this->villeImageColumnExists;
    }

    public function getTypes(): array
    {
        $st = $this->pdo->query("SELECT id_type, nom_type FROM type_don ORDER BY nom_type");
        return $st->fetchAll();
    }

    public function getPurchaseTypes(): array
    {
        $st = $this->pdo->query("SELECT id_type, nom_type FROM type_don WHERE nom_type IN ('nature', 'materiaux') ORDER BY nom_type");
        return $st->fetchAll();
    }

    public function getSousTypes(): array
    {
        $imageSelect = $this->hasSousTypeImageColumn()
            ? 'image_sous_type'
            : "CASE
                    WHEN nom_sous_type = 'clou' THEN 'cloues.png'
                    WHEN nom_sous_type = 'tole' THEN 'toles.png'
                    ELSE CONCAT(LOWER(nom_sous_type), '.png')
               END AS image_sous_type";
        $sql = "SELECT id_sous_type, id_type, nom_sous_type, unite_defaut, $imageSelect
                FROM sous_type_don
                ORDER BY nom_sous_type";
        $st = $this->pdo->query($sql);
        return $st->fetchAll();
    }

    public function getOpenNeedsSummary(): array
    {
        $sql = "SELECT
                    b.id_besoin,
                    v.nom_ville,
                    td.nom_type,
                    std.nom_sous_type,
                    b.quantite_restante,
                    b.unite,
                    COALESCE(s.quantite_disponible, 0) AS stock_disponible
                FROM besoin b
                JOIN ville v ON v.id_ville = b.id_ville
                JOIN sous_type_don std ON std.id_sous_type = b.id_sous_type
                JOIN type_don td ON td.id_type = std.id_type
                LEFT JOIN stock s ON s.id_sous_type = b.id_sous_type
                WHERE b.statut IN ('ouvert', 'partiel')
                  AND b.quantite_restante > 0
                ORDER BY v.nom_ville, td.nom_type, std.nom_sous_type";
        $st = $this->pdo->query($sql);
        return $st->fetchAll();
    }

    public function getPurchasePriceList(): array
    {
        $imageSelect = $this->hasSousTypeImageColumn()
            ? 'std.image_sous_type'
            : "CASE
                    WHEN std.nom_sous_type = 'clou' THEN 'cloues.png'
                    WHEN std.nom_sous_type = 'tole' THEN 'toles.png'
                    ELSE CONCAT(LOWER(std.nom_sous_type), '.png')
               END AS image_sous_type";
        $sql = "SELECT
                    std.id_sous_type,
                    std.id_type,
                    td.nom_type,
                    std.nom_sous_type,
                    std.unite_defaut,
                    $imageSelect,
                    p.prix_unitaire
                FROM sous_type_don std
                JOIN type_don td ON td.id_type = std.id_type
                JOIN prix p ON p.id_sous_type = std.id_sous_type
                JOIN (
                    SELECT id_sous_type, MAX(date_prix) AS max_date
                    FROM prix
                    GROUP BY id_sous_type
                ) lp ON lp.id_sous_type = p.id_sous_type AND lp.max_date = p.date_prix
                WHERE td.nom_type IN ('nature', 'materiaux')
                ORDER BY td.nom_type, std.nom_sous_type";
        $st = $this->pdo->query($sql);
        return $st->fetchAll();
    }

    public function getSalePriceList(): array
    {
        $imageSelect = $this->hasSousTypeImageColumn()
            ? 'std.image_sous_type'
            : "CASE
                    WHEN std.nom_sous_type = 'clou' THEN 'cloues.png'
                    WHEN std.nom_sous_type = 'tole' THEN 'toles.png'
                    ELSE CONCAT(LOWER(std.nom_sous_type), '.png')
               END AS image_sous_type";
        $sql = "SELECT
                    std.id_sous_type,
                    std.id_type,
                    td.nom_type,
                    std.nom_sous_type,
                    std.unite_defaut,
                    $imageSelect,
                    p.prix_unitaire
                FROM sous_type_don std
                JOIN type_don td ON td.id_type = std.id_type
                JOIN prix p ON p.id_sous_type = std.id_sous_type
                JOIN (
                    SELECT id_sous_type, MAX(date_prix) AS max_date
                    FROM prix
                    GROUP BY id_sous_type
                ) lp ON lp.id_sous_type = p.id_sous_type AND lp.max_date = p.date_prix
                WHERE td.nom_type IN ('nature', 'materiaux')
                ORDER BY td.nom_type, std.nom_sous_type";
        $st = $this->pdo->query($sql);
        return $st->fetchAll();
    }

    private function hasSousTypeImageColumn(): bool
    {
        if ($this->sousTypeImageColumnExists !== null) {
            return $this->sousTypeImageColumnExists;
        }

        $st = $this->pdo->query("SHOW COLUMNS FROM sous_type_don LIKE 'image_sous_type'");
        $this->sousTypeImageColumnExists = (bool) $st->fetch();
        return $this->sousTypeImageColumnExists;
    }

    public function getArgentStock(): float
    {
        $sql = "SELECT COALESCE(s.quantite_disponible, 0) AS argent_stock
                FROM stock s
                JOIN sous_type_don std ON std.id_sous_type = s.id_sous_type
                WHERE std.nom_sous_type = 'argent'
                LIMIT 1";
        $st = $this->pdo->query($sql);
        $row = $st->fetch();
        return (float) ($row['argent_stock'] ?? 0);
    }

    public function distribute(
        int $villeId,
        int $typeId,
        int $sousTypeId,
        float $quantite
    ): int {
        if ($villeId <= 0 || $typeId <= 0 || $sousTypeId <= 0) {
            throw new InvalidArgumentException('Selection invalide.');
        }
        if ($quantite <= 0) {
            throw new InvalidArgumentException('La quantite doit etre superieure a 0.');
        }

        $this->pdo->beginTransaction();

        try {
            $st = $this->pdo->prepare(
                "SELECT id_sous_type, unite_defaut
                 FROM sous_type_don
                 WHERE id_sous_type = :id_sous_type AND id_type = :id_type
                 LIMIT 1"
            );
            $st->execute([
                ':id_sous_type' => $sousTypeId,
                ':id_type' => $typeId,
            ]);
            $sousType = $st->fetch();
            if (!$sousType) {
                throw new RuntimeException('Le sous-type ne correspond pas au type choisi.');
            }

            $stBesoin = $this->pdo->prepare(
                "SELECT id_besoin, quantite_restante
                 FROM besoin
                 WHERE id_ville = :id_ville
                   AND id_sous_type = :id_sous_type
                   AND statut IN ('ouvert', 'partiel')
                   AND quantite_restante > 0
                 ORDER BY date_besoin ASC, id_besoin ASC
                 LIMIT 1
                 FOR UPDATE"
            );
            $stBesoin->execute([
                ':id_ville' => $villeId,
                ':id_sous_type' => $sousTypeId,
            ]);
            $besoin = $stBesoin->fetch();
            if (!$besoin) {
                $available = $this->getOpenNeedsSummary();
                if (empty($available)) {
                    throw new RuntimeException('Aucun besoin ouvert actuellement dans la base.');
                }

                $hints = array_map(
                    static fn(array $row) => $row['nom_ville'] . ' - ' . $row['nom_sous_type'],
                    array_slice($available, 0, 5)
                );
                throw new RuntimeException(
                    'Aucun besoin ouvert pour cette ville et ce sous-type. Exemples disponibles: ' . implode(', ', $hints)
                );
            }

            $stStock = $this->pdo->prepare(
                "SELECT id_stock, quantite_disponible
                 FROM stock
                 WHERE id_sous_type = :id_sous_type
                 LIMIT 1
                 FOR UPDATE"
            );
            $stStock->execute([':id_sous_type' => $sousTypeId]);
            $stock = $stStock->fetch();
            if (!$stock) {
                throw new RuntimeException('Stock introuvable pour ce sous-type.');
            }

            $qteStock = (float) $stock['quantite_disponible'];
            $qteRestante = (float) $besoin['quantite_restante'];

            if ($quantite > $qteStock) {
                throw new RuntimeException('Stock insuffisant pour cette distribution.');
            }
            if ($quantite > $qteRestante) {
                throw new RuntimeException('La quantite depasse le besoin restant.');
            }

            $stInsertDistribution = $this->pdo->prepare(
                "INSERT INTO distribution (id_besoin, id_sous_type, quantite_distribuee, unite, date_distribution)
                 VALUES (:id_besoin, :id_sous_type, :quantite, :unite, NOW())"
            );
            $stInsertDistribution->execute([
                ':id_besoin' => (int) $besoin['id_besoin'],
                ':id_sous_type' => $sousTypeId,
                ':quantite' => $quantite,
                ':unite' => $sousType['unite_defaut'],
            ]);
            $distributionId = (int) $this->pdo->lastInsertId();

            $stUpdateBesoin = $this->pdo->prepare(
                "UPDATE besoin
                 SET quantite_restante = quantite_restante - :quantite,
                     statut = CASE
                         WHEN (quantite_restante - :quantite) <= 0 THEN 'satisfait'
                         WHEN (quantite_restante - :quantite) < quantite_requise THEN 'partiel'
                         ELSE 'ouvert'
                     END
                 WHERE id_besoin = :id_besoin"
            );
            $stUpdateBesoin->execute([
                ':quantite' => $quantite,
                ':id_besoin' => (int) $besoin['id_besoin'],
            ]);

            $stUpdateStock = $this->pdo->prepare(
                "UPDATE stock
                 SET quantite_disponible = quantite_disponible - :quantite
                 WHERE id_stock = :id_stock"
            );
            $stUpdateStock->execute([
                ':quantite' => $quantite,
                ':id_stock' => (int) $stock['id_stock'],
            ]);

            $stMouvement = $this->pdo->prepare(
                "INSERT INTO mouvement_stock (
                    type_mouvement, id_sous_type, quantite, unite, date_mouvement, reference_table, reference_id
                 ) VALUES (
                    'SORTIE_DISTRIBUTION', :id_sous_type, :quantite, :unite, NOW(), 'distribution', :reference_id
                 )"
            );
            $stMouvement->execute([
                ':id_sous_type' => $sousTypeId,
                ':quantite' => $quantite,
                ':unite' => $sousType['unite_defaut'],
                ':reference_id' => $distributionId,
            ]);

            $this->pdo->commit();
            return $distributionId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function acheter(
        int $villeId,
        int $typeId,
        int $sousTypeId,
        float $quantite
    ): int {
        if ($villeId <= 0 || $typeId <= 0 || $sousTypeId <= 0) {
            throw new InvalidArgumentException('Selection invalide.');
        }
        if ($quantite <= 0) {
            throw new InvalidArgumentException('La quantite doit etre superieure a 0.');
        }

        $this->pdo->beginTransaction();
        try {
            $stSousType = $this->pdo->prepare(
                "SELECT std.id_sous_type, std.unite_defaut, std.nom_sous_type, td.nom_type
                 FROM sous_type_don std
                 JOIN type_don td ON td.id_type = std.id_type
                 WHERE std.id_sous_type = :id_sous_type AND std.id_type = :id_type
                 LIMIT 1"
            );
            $stSousType->execute([
                ':id_sous_type' => $sousTypeId,
                ':id_type' => $typeId,
            ]);
            $sousType = $stSousType->fetch();
            if (!$sousType) {
                throw new RuntimeException('Le sous-type ne correspond pas au type choisi.');
            }
            if (!in_array($sousType['nom_type'], ['nature', 'materiaux'], true)) {
                throw new RuntimeException('Achat autorise seulement pour nature et materiaux.');
            }

            $stPrix = $this->pdo->prepare(
                "SELECT prix_unitaire
                 FROM prix
                 WHERE id_sous_type = :id_sous_type
                 ORDER BY date_prix DESC, id_prix DESC
                 LIMIT 1"
            );
            $stPrix->execute([':id_sous_type' => $sousTypeId]);
            $prix = $stPrix->fetch();
            if (!$prix) {
                throw new RuntimeException('Prix unitaire introuvable pour ce sous-type.');
            }
            $prixUnitaire = (float) $prix['prix_unitaire'];
            $montantTotal = $quantite * $prixUnitaire;

            $stArgentType = $this->pdo->query(
                "SELECT id_sous_type, unite_defaut
                 FROM sous_type_don
                 WHERE nom_sous_type = 'argent'
                 LIMIT 1"
            );
            $argentType = $stArgentType->fetch();
            if (!$argentType) {
                throw new RuntimeException('Sous-type argent introuvable.');
            }
            $argentSousTypeId = (int) $argentType['id_sous_type'];

            $stArgentStock = $this->pdo->prepare(
                "SELECT id_stock, quantite_disponible
                 FROM stock
                 WHERE id_sous_type = :id_sous_type
                 LIMIT 1
                 FOR UPDATE"
            );
            $stArgentStock->execute([':id_sous_type' => $argentSousTypeId]);
            $argentStock = $stArgentStock->fetch();
            if (!$argentStock) {
                throw new RuntimeException('Stock argent introuvable.');
            }
            if ((float) $argentStock['quantite_disponible'] < $montantTotal) {
                throw new RuntimeException('Solde argent insuffisant pour cet achat.');
            }

            $stInsertAchat = $this->pdo->prepare(
                "INSERT INTO achat (id_ville, id_sous_type, quantite_achetee, prix_unitaire, montant_total, date_achat)
                 VALUES (:id_ville, :id_sous_type, :quantite_achetee, :prix_unitaire, :montant_total, NOW())"
            );
            $stInsertAchat->execute([
                ':id_ville' => $villeId,
                ':id_sous_type' => $sousTypeId,
                ':quantite_achetee' => $quantite,
                ':prix_unitaire' => $prixUnitaire,
                ':montant_total' => $montantTotal,
            ]);
            $achatId = (int) $this->pdo->lastInsertId();

            $stStockAchat = $this->pdo->prepare(
                "SELECT id_stock
                 FROM stock
                 WHERE id_sous_type = :id_sous_type
                 LIMIT 1
                 FOR UPDATE"
            );
            $stStockAchat->execute([':id_sous_type' => $sousTypeId]);
            $stockAchat = $stStockAchat->fetch();

            if ($stockAchat) {
                $stUpdateStock = $this->pdo->prepare(
                    "UPDATE stock
                     SET quantite_disponible = quantite_disponible + :quantite
                     WHERE id_stock = :id_stock"
                );
                $stUpdateStock->execute([
                    ':quantite' => $quantite,
                    ':id_stock' => (int) $stockAchat['id_stock'],
                ]);
            } else {
                $stInsertStock = $this->pdo->prepare(
                    "INSERT INTO stock (id_sous_type, quantite_disponible, unite)
                     VALUES (:id_sous_type, :quantite_disponible, :unite)"
                );
                $stInsertStock->execute([
                    ':id_sous_type' => $sousTypeId,
                    ':quantite_disponible' => $quantite,
                    ':unite' => $sousType['unite_defaut'],
                ]);
            }

            $stUpdateArgent = $this->pdo->prepare(
                "UPDATE stock
                 SET quantite_disponible = quantite_disponible - :montant
                 WHERE id_stock = :id_stock"
            );
            $stUpdateArgent->execute([
                ':montant' => $montantTotal,
                ':id_stock' => (int) $argentStock['id_stock'],
            ]);

            $stMvtEntree = $this->pdo->prepare(
                "INSERT INTO mouvement_stock (
                    type_mouvement, id_sous_type, quantite, unite, date_mouvement, reference_table, reference_id
                ) VALUES (
                    'ENTREE_ACHAT', :id_sous_type, :quantite, :unite, NOW(), 'achat', :reference_id
                )"
            );
            $stMvtEntree->execute([
                ':id_sous_type' => $sousTypeId,
                ':quantite' => $quantite,
                ':unite' => $sousType['unite_defaut'],
                ':reference_id' => $achatId,
            ]);

            $stMvtSortieArgent = $this->pdo->prepare(
                "INSERT INTO mouvement_stock (
                    type_mouvement, id_sous_type, quantite, unite, date_mouvement, reference_table, reference_id
                ) VALUES (
                    'SORTIE_ARGENT_ACHAT', :id_sous_type, :quantite, :unite, NOW(), 'achat', :reference_id
                )"
            );
            $stMvtSortieArgent->execute([
                ':id_sous_type' => $argentSousTypeId,
                ':quantite' => $montantTotal,
                ':unite' => 'arriary',
                ':reference_id' => $achatId,
            ]);

            $this->pdo->commit();
            return $achatId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function vendreProduit(
        int $villeId,
        int $typeId,
        int $sousTypeId,
        float $quantite,
        float $tauxMaxPercent = 10.0
    ): int {
        if ($villeId <= 0 || $typeId <= 0 || $sousTypeId <= 0) {
            throw new InvalidArgumentException('Selection invalide.');
        }
        if ($quantite <= 0) {
            throw new InvalidArgumentException('La quantite doit etre > 0.');
        }
        if ($tauxMaxPercent <= 0) {
            throw new InvalidArgumentException('Le taux max doit etre > 0.');
        }

        $this->pdo->beginTransaction();
        try {
            $stSousType = $this->pdo->prepare(
                "SELECT std.id_sous_type, std.nom_sous_type, std.unite_defaut, td.nom_type
                 FROM sous_type_don std
                 JOIN type_don td ON td.id_type = std.id_type
                 WHERE std.id_sous_type = :id_sous_type AND std.id_type = :id_type
                 LIMIT 1"
            );
            $stSousType->execute([
                ':id_sous_type' => $sousTypeId,
                ':id_type' => $typeId,
            ]);
            $sousType = $stSousType->fetch();
            if (!$sousType) {
                throw new RuntimeException('Le sous-type ne correspond pas au type choisi.');
            }
            if (!in_array($sousType['nom_type'], ['nature', 'materiaux'], true)) {
                throw new RuntimeException('Vente autorisee uniquement pour nature et materiaux.');
            }

            $stNeeds = $this->pdo->prepare(
                "SELECT COUNT(*) AS total
                 FROM besoin
                 WHERE id_sous_type = :id_sous_type
                   AND statut IN ('ouvert', 'partiel')
                   AND quantite_restante > 0"
            );
            $stNeeds->execute([':id_sous_type' => $sousTypeId]);
            $openNeedCount = (int) ($stNeeds->fetch()['total'] ?? 0);
            if ($openNeedCount > 0) {
                throw new RuntimeException('Vente non permise: ce sous-type existe dans des besoins ouverts.');
            }

            $stPrix = $this->pdo->prepare(
                "SELECT prix_unitaire
                 FROM prix
                 WHERE id_sous_type = :id_sous_type
                 ORDER BY date_prix DESC, id_prix DESC
                 LIMIT 1"
            );
            $stPrix->execute([':id_sous_type' => $sousTypeId]);
            $prix = $stPrix->fetch();
            if (!$prix) {
                throw new RuntimeException('Prix d achat de reference introuvable.');
            }
            $prixAchat = (float) $prix['prix_unitaire'];
            // V3: prix de vente = prix d achat - taux%
            $prixVenteUnitaire = $prixAchat * (1 - ($tauxMaxPercent / 100.0));
            if ($prixVenteUnitaire < 0) {
                throw new RuntimeException('Taux invalide pour le calcul du prix de vente.');
            }

            $stStockProduit = $this->pdo->prepare(
                "SELECT id_stock, quantite_disponible
                 FROM stock
                 WHERE id_sous_type = :id_sous_type
                 LIMIT 1
                 FOR UPDATE"
            );
            $stStockProduit->execute([':id_sous_type' => $sousTypeId]);
            $stockProduit = $stStockProduit->fetch();
            if (!$stockProduit) {
                throw new RuntimeException('Stock introuvable pour ce sous-type.');
            }
            if ((float) $stockProduit['quantite_disponible'] < $quantite) {
                throw new RuntimeException('Stock insuffisant pour la vente.');
            }

            $montantTotal = $quantite * $prixVenteUnitaire;

            $stArgentType = $this->pdo->query(
                "SELECT id_sous_type
                 FROM sous_type_don
                 WHERE nom_sous_type = 'argent'
                 LIMIT 1"
            );
            $argentType = $stArgentType->fetch();
            if (!$argentType) {
                throw new RuntimeException('Sous-type argent introuvable.');
            }
            $argentSousTypeId = (int) $argentType['id_sous_type'];

            $stArgentStock = $this->pdo->prepare(
                "SELECT id_stock, quantite_disponible
                 FROM stock
                 WHERE id_sous_type = :id_sous_type
                 LIMIT 1
                 FOR UPDATE"
            );
            $stArgentStock->execute([':id_sous_type' => $argentSousTypeId]);
            $argentStock = $stArgentStock->fetch();
            if (!$argentStock) {
                $stCreateArgentStock = $this->pdo->prepare(
                    "INSERT INTO stock (id_sous_type, quantite_disponible, unite)
                     VALUES (:id_sous_type, 0, 'arriary')"
                );
                $stCreateArgentStock->execute([':id_sous_type' => $argentSousTypeId]);
                $argentStock = ['id_stock' => (int) $this->pdo->lastInsertId(), 'quantite_disponible' => 0];
            }

            $montantAffecteBesoinArgent = 0.0;
            $stBesoinArgent = $this->pdo->prepare(
                "SELECT id_besoin, quantite_restante, quantite_requise
                 FROM besoin
                 WHERE id_ville = :id_ville
                   AND id_sous_type = :id_sous_type
                   AND statut IN ('ouvert', 'partiel')
                   AND quantite_restante > 0
                 ORDER BY date_besoin ASC, id_besoin ASC
                 LIMIT 1
                 FOR UPDATE"
            );
            $stBesoinArgent->execute([
                ':id_ville' => $villeId,
                ':id_sous_type' => $argentSousTypeId,
            ]);
            $besoinArgent = $stBesoinArgent->fetch();

            if ($besoinArgent) {
                $restante = (float) $besoinArgent['quantite_restante'];
                $montantAffecteBesoinArgent = min($montantTotal, $restante);

                $stUpdateBesoinArgent = $this->pdo->prepare(
                    "UPDATE besoin
                     SET quantite_restante = quantite_restante - :montant,
                         statut = CASE
                             WHEN (quantite_restante - :montant) <= 0 THEN 'satisfait'
                             WHEN (quantite_restante - :montant) < quantite_requise THEN 'partiel'
                             ELSE 'ouvert'
                         END
                     WHERE id_besoin = :id_besoin"
                );
                $stUpdateBesoinArgent->execute([
                    ':montant' => $montantAffecteBesoinArgent,
                    ':id_besoin' => (int) $besoinArgent['id_besoin'],
                ]);
            }

            $montantAjouteStockArgent = $montantTotal - $montantAffecteBesoinArgent;

            $stUpdateStockProduit = $this->pdo->prepare(
                "UPDATE stock
                 SET quantite_disponible = quantite_disponible - :quantite
                 WHERE id_stock = :id_stock"
            );
            $stUpdateStockProduit->execute([
                ':quantite' => $quantite,
                ':id_stock' => (int) $stockProduit['id_stock'],
            ]);

            if ($montantAjouteStockArgent > 0) {
                $stUpdateStockArgent = $this->pdo->prepare(
                    "UPDATE stock
                     SET quantite_disponible = quantite_disponible + :montant
                     WHERE id_stock = :id_stock"
                );
                $stUpdateStockArgent->execute([
                    ':montant' => $montantAjouteStockArgent,
                    ':id_stock' => (int) $argentStock['id_stock'],
                ]);
            }

            $stInsertVente = $this->pdo->prepare(
                "INSERT INTO vente (
                    id_ville,
                    id_sous_type,
                    quantite_vendue,
                    prix_achat_reference,
                    prix_vente_unitaire,
                    taux_max_percent,
                    montant_total,
                    montant_affecte_besoin_argent,
                    montant_ajoute_stock_argent,
                    date_vente
                ) VALUES (
                    :id_ville,
                    :id_sous_type,
                    :quantite_vendue,
                    :prix_achat_reference,
                    :prix_vente_unitaire,
                    :taux_max_percent,
                    :montant_total,
                    :montant_affecte_besoin_argent,
                    :montant_ajoute_stock_argent,
                    NOW()
                )"
            );
            $stInsertVente->execute([
                ':id_ville' => $villeId,
                ':id_sous_type' => $sousTypeId,
                ':quantite_vendue' => $quantite,
                ':prix_achat_reference' => $prixAchat,
                ':prix_vente_unitaire' => $prixVenteUnitaire,
                ':taux_max_percent' => $tauxMaxPercent,
                ':montant_total' => $montantTotal,
                ':montant_affecte_besoin_argent' => $montantAffecteBesoinArgent,
                ':montant_ajoute_stock_argent' => $montantAjouteStockArgent,
            ]);
            $venteId = (int) $this->pdo->lastInsertId();

            $stMvtSortieProduit = $this->pdo->prepare(
                "INSERT INTO mouvement_stock (
                    type_mouvement, id_sous_type, quantite, unite, date_mouvement, reference_table, reference_id
                 ) VALUES (
                    'SORTIE_VENTE_PRODUIT', :id_sous_type, :quantite, :unite, NOW(), 'vente', :reference_id
                 )"
            );
            $stMvtSortieProduit->execute([
                ':id_sous_type' => $sousTypeId,
                ':quantite' => $quantite,
                ':unite' => $sousType['unite_defaut'],
                ':reference_id' => $venteId,
            ]);

            $stMvtEntreeArgent = $this->pdo->prepare(
                "INSERT INTO mouvement_stock (
                    type_mouvement, id_sous_type, quantite, unite, date_mouvement, reference_table, reference_id
                 ) VALUES (
                    'ENTREE_VENTE_ARGENT', :id_sous_type, :quantite, 'arriary', NOW(), 'vente', :reference_id
                 )"
            );
            $stMvtEntreeArgent->execute([
                ':id_sous_type' => $argentSousTypeId,
                ':quantite' => $montantTotal,
                ':reference_id' => $venteId,
            ]);

            $this->pdo->commit();
            return $venteId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function getVentes(array $filters = []): array
    {
        $whereParts = [];
        $params = [];

        if (!empty($filters['id_ville'])) {
            $whereParts[] = 'v.id_ville = :id_ville';
            $params[':id_ville'] = (int) $filters['id_ville'];
        }
        if (!empty($filters['id_sous_type'])) {
            $whereParts[] = 'std.id_sous_type = :id_sous_type';
            $params[':id_sous_type'] = (int) $filters['id_sous_type'];
        }
        if (!empty($filters['date_from'])) {
            $whereParts[] = 'DATE(vt.date_vente) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $whereParts[] = 'DATE(vt.date_vente) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        $where = '';
        if (!empty($whereParts)) {
            $where = 'WHERE ' . implode(' AND ', $whereParts);
        }

        $sql = "SELECT
                    vt.id_vente,
                    v.nom_ville,
                    td.nom_type,
                    std.nom_sous_type,
                    vt.quantite_vendue,
                    std.unite_defaut AS unite,
                    vt.prix_achat_reference,
                    vt.prix_vente_unitaire,
                    vt.taux_max_percent,
                    vt.montant_total,
                    vt.montant_affecte_besoin_argent,
                    vt.montant_ajoute_stock_argent,
                    vt.date_vente
                FROM vente vt
                JOIN ville v ON v.id_ville = vt.id_ville
                JOIN sous_type_don std ON std.id_sous_type = vt.id_sous_type
                JOIN type_don td ON td.id_type = std.id_type
                $where
                ORDER BY vt.date_vente DESC";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function getDashboardSummary(?int $villeId = null): array
    {
        $params = [];
        $where = '';
        if ($villeId !== null && $villeId > 0) {
            $where = 'WHERE v.id_ville = :id_ville';
            $params[':id_ville'] = $villeId;
        }

        $sql = "SELECT
                    v.id_ville,
                    v.nom_ville,
                    SUM(CASE WHEN b.statut = 'ouvert' THEN 1 ELSE 0 END) AS besoin_ouvert,
                    SUM(CASE WHEN b.statut = 'partiel' THEN 1 ELSE 0 END) AS besoin_partiel,
                    SUM(CASE WHEN b.statut = 'satisfait' THEN 1 ELSE 0 END) AS besoin_satisfait,
                    SUM(CASE WHEN td.nom_type = 'nature' THEN 1 ELSE 0 END) AS besoin_nature,
                    SUM(CASE WHEN td.nom_type = 'materiaux' THEN 1 ELSE 0 END) AS besoin_materiaux,
                    SUM(CASE WHEN td.nom_type = 'argent' THEN 1 ELSE 0 END) AS besoin_argent,
                    COALESCE(av.montant_achat, 0) AS montant_achat
                FROM ville v
                LEFT JOIN besoin b ON b.id_ville = v.id_ville
                LEFT JOIN sous_type_don std ON std.id_sous_type = b.id_sous_type
                LEFT JOIN type_don td ON td.id_type = std.id_type
                LEFT JOIN (
                    SELECT id_ville, SUM(montant_total) AS montant_achat
                    FROM achat
                    GROUP BY id_ville
                ) av ON av.id_ville = v.id_ville
                $where
                GROUP BY v.id_ville, v.nom_ville, av.montant_achat
                ORDER BY v.nom_ville";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function getBesoinsByVille(?int $villeId = null): array
    {
        $params = [];
        $where = '';
        if ($villeId !== null && $villeId > 0) {
            $where = 'WHERE v.id_ville = :id_ville';
            $params[':id_ville'] = $villeId;
        }

        $sql = "SELECT
                    b.id_besoin,
                    v.nom_ville,
                    td.nom_type,
                    std.nom_sous_type,
                    b.quantite_requise,
                    b.quantite_restante,
                    b.unite,
                    b.statut,
                    b.date_besoin
                FROM besoin b
                JOIN ville v ON v.id_ville = b.id_ville
                JOIN sous_type_don std ON std.id_sous_type = b.id_sous_type
                JOIN type_don td ON td.id_type = std.id_type
                $where
                ORDER BY v.nom_ville, b.date_besoin DESC";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function getDistributionHistory(?int $villeId = null): array
    {
        $params = [];
        $where = '';
        if ($villeId !== null && $villeId > 0) {
            $where = 'WHERE v.id_ville = :id_ville';
            $params[':id_ville'] = $villeId;
        }

        $sql = "SELECT
                    d.id_distribution,
                    v.nom_ville,
                    td.nom_type,
                    std.nom_sous_type,
                    d.quantite_distribuee,
                    d.unite,
                    d.date_distribution
                FROM distribution d
                JOIN besoin b ON b.id_besoin = d.id_besoin
                JOIN ville v ON v.id_ville = b.id_ville
                JOIN sous_type_don std ON std.id_sous_type = d.id_sous_type
                JOIN type_don td ON td.id_type = std.id_type
                $where
                ORDER BY d.date_distribution DESC";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function getAchats(?int $villeId = null): array
    {
        $params = [];
        $where = '';
        if ($villeId !== null && $villeId > 0) {
            $where = 'WHERE v.id_ville = :id_ville';
            $params[':id_ville'] = $villeId;
        }

        $sql = "SELECT
                    a.id_achat,
                    v.nom_ville,
                    td.nom_type,
                    std.nom_sous_type,
                    a.quantite_achetee,
                    std.unite_defaut AS unite,
                    a.prix_unitaire,
                    a.montant_total,
                    a.date_achat
                FROM achat a
                JOIN ville v ON v.id_ville = a.id_ville
                JOIN sous_type_don std ON std.id_sous_type = a.id_sous_type
                JOIN type_don td ON td.id_type = std.id_type
                $where
                ORDER BY a.date_achat DESC";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function getRecapMontants(): array
    {
        $besoinsTotal = $this->pdo->query(
            "SELECT COALESCE(SUM(
                b.quantite_requise * COALESCE(
                    (SELECT p.prix_unitaire
                     FROM prix p
                     WHERE p.id_sous_type = b.id_sous_type
                     ORDER BY p.date_prix DESC, p.id_prix DESC
                     LIMIT 1
                    ), 0
                )
            ), 0) AS montant
            FROM besoin b"
        )->fetch();

        $besoinsSatisfaits = $this->pdo->query(
            "SELECT COALESCE(SUM(
                (b.quantite_requise - b.quantite_restante) * COALESCE(
                    (SELECT p.prix_unitaire
                     FROM prix p
                     WHERE p.id_sous_type = b.id_sous_type
                     ORDER BY p.date_prix DESC, p.id_prix DESC
                     LIMIT 1
                    ), 0
                )
            ), 0) AS montant
            FROM besoin b"
        )->fetch();

        $donsRecus = $this->pdo->query(
            "SELECT COALESCE(SUM(
                CASE
                    WHEN std.nom_sous_type = 'argent' THEN d.quantite
                    ELSE d.quantite * COALESCE(
                        (SELECT p.prix_unitaire
                         FROM prix p
                         WHERE p.id_sous_type = d.id_sous_type
                         ORDER BY p.date_prix DESC, p.id_prix DESC
                         LIMIT 1
                        ), 0
                    )
                END
            ), 0) AS montant
            FROM don d
            JOIN sous_type_don std ON std.id_sous_type = d.id_sous_type"
        )->fetch();

        $donsDispatches = $this->pdo->query(
            "SELECT COALESCE(SUM(
                CASE
                    WHEN std.nom_sous_type = 'argent' THEN d.quantite_distribuee
                    ELSE d.quantite_distribuee * COALESCE(
                        (SELECT p.prix_unitaire
                         FROM prix p
                         WHERE p.id_sous_type = d.id_sous_type
                         ORDER BY p.date_prix DESC, p.id_prix DESC
                         LIMIT 1
                        ), 0
                    )
                END
            ), 0) AS montant
            FROM distribution d
            JOIN sous_type_don std ON std.id_sous_type = d.id_sous_type"
        )->fetch();

        $achatsEffectues = $this->pdo->query(
            "SELECT COALESCE(SUM(montant_total), 0) AS montant
             FROM achat"
        )->fetch();

        $ventesEffectuees = $this->pdo->query(
            "SELECT COALESCE(SUM(montant_total), 0) AS montant
             FROM vente"
        )->fetch();

        return [
            'besoins_totaux_montant' => (float) ($besoinsTotal['montant'] ?? 0),
            'besoins_satisfaits_montant' => (float) ($besoinsSatisfaits['montant'] ?? 0),
            'dons_recus_montant' => (float) ($donsRecus['montant'] ?? 0),
            'dons_dispatches_montant' => (float) ($donsDispatches['montant'] ?? 0),
            'achats_effectues_montant' => (float) ($achatsEffectues['montant'] ?? 0),
            'ventes_effectuees_montant' => (float) ($ventesEffectuees['montant'] ?? 0),
            'solde_argent_restant' => $this->getArgentStock(),
        ];
    }
}
