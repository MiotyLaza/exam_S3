DROP DATABASE IF EXISTS gestion_dons_sinistres;
CREATE DATABASE gestion_dons_sinistres;
USE gestion_dons_sinistres;

CREATE TABLE ville (
  id_ville INT AUTO_INCREMENT PRIMARY KEY,
  nom_ville VARCHAR(100) NOT NULL UNIQUE,
  image_ville VARCHAR(255) NOT NULL
);

CREATE TABLE type_don (
  id_type INT AUTO_INCREMENT PRIMARY KEY,
  nom_type VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE sous_type_don (
  id_sous_type INT AUTO_INCREMENT PRIMARY KEY,
  id_type INT NOT NULL,
  nom_sous_type VARCHAR(50) NOT NULL UNIQUE,
  unite_defaut VARCHAR(20) NOT NULL,
  image_sous_type VARCHAR(255) NOT NULL,
  CONSTRAINT fk_sous_type_type
    FOREIGN KEY (id_type) REFERENCES type_don(id_type)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE don (
  id_don INT AUTO_INCREMENT PRIMARY KEY,
  id_ville INT NOT NULL,
  id_sous_type INT NOT NULL,
  quantite DECIMAL(12,2) NOT NULL,
  unite VARCHAR(20) NOT NULL,
  date_don DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_don_ville
    FOREIGN KEY (id_ville) REFERENCES ville(id_ville)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_don_sous_type
    FOREIGN KEY (id_sous_type) REFERENCES sous_type_don(id_sous_type)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE besoin (
  id_besoin INT AUTO_INCREMENT PRIMARY KEY,
  id_ville INT NOT NULL,
  id_sous_type INT NOT NULL,
  quantite_requise DECIMAL(12,2) NOT NULL,
  quantite_restante DECIMAL(12,2) NOT NULL,
  unite VARCHAR(20) NOT NULL,
  date_besoin DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  statut ENUM('ouvert', 'partiel', 'satisfait') NOT NULL DEFAULT 'ouvert',
  CONSTRAINT fk_besoin_ville
    FOREIGN KEY (id_ville) REFERENCES ville(id_ville)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_besoin_sous_type
    FOREIGN KEY (id_sous_type) REFERENCES sous_type_don(id_sous_type)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE distribution (
  id_distribution INT AUTO_INCREMENT PRIMARY KEY,
  id_besoin INT NOT NULL,
  id_sous_type INT NOT NULL,
  quantite_distribuee DECIMAL(12,2) NOT NULL,
  unite VARCHAR(20) NOT NULL,
  date_distribution DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_distribution_besoin
    FOREIGN KEY (id_besoin) REFERENCES besoin(id_besoin)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_distribution_sous_type
    FOREIGN KEY (id_sous_type) REFERENCES sous_type_don(id_sous_type)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE stock (
  id_stock INT AUTO_INCREMENT PRIMARY KEY,
  id_sous_type INT NOT NULL UNIQUE,
  quantite_disponible DECIMAL(12,2) NOT NULL DEFAULT 0,
  unite VARCHAR(20) NOT NULL,
  CONSTRAINT fk_stock_sous_type
    FOREIGN KEY (id_sous_type) REFERENCES sous_type_don(id_sous_type)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE prix (
  id_prix INT AUTO_INCREMENT PRIMARY KEY,
  id_sous_type INT NOT NULL,
  prix_unitaire DECIMAL(12,2) NOT NULL,
  date_prix DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_prix_sous_type
    FOREIGN KEY (id_sous_type) REFERENCES sous_type_don(id_sous_type)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE achat (
  id_achat INT AUTO_INCREMENT PRIMARY KEY,
  id_ville INT NOT NULL,
  id_sous_type INT NOT NULL,
  quantite_achetee DECIMAL(12,2) NOT NULL,
  prix_unitaire DECIMAL(12,2) NOT NULL,
  montant_total DECIMAL(12,2) NOT NULL,
  date_achat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_achat_ville
    FOREIGN KEY (id_ville) REFERENCES ville(id_ville)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_achat_sous_type
    FOREIGN KEY (id_sous_type) REFERENCES sous_type_don(id_sous_type)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE vente (
  id_vente INT AUTO_INCREMENT PRIMARY KEY,
  id_ville INT NOT NULL,
  id_sous_type INT NOT NULL,
  quantite_vendue DECIMAL(12,2) NOT NULL,
  prix_achat_reference DECIMAL(12,2) NOT NULL,
  prix_vente_unitaire DECIMAL(12,2) NOT NULL,
  taux_max_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  montant_total DECIMAL(12,2) NOT NULL,
  montant_affecte_besoin_argent DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  montant_ajoute_stock_argent DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  date_vente DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_vente_ville
    FOREIGN KEY (id_ville) REFERENCES ville(id_ville)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_vente_sous_type
    FOREIGN KEY (id_sous_type) REFERENCES sous_type_don(id_sous_type)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE mouvement_stock (
  id_mouvement INT AUTO_INCREMENT PRIMARY KEY,
  type_mouvement ENUM(
    'ENTREE_DON',
    'SORTIE_DISTRIBUTION',
    'ENTREE_ACHAT',
    'SORTIE_ARGENT_ACHAT',
    'SORTIE_VENTE_PRODUIT',
    'ENTREE_VENTE_ARGENT'
  ) NOT NULL,
  id_sous_type INT NOT NULL,
  quantite DECIMAL(12,2) NOT NULL,
  unite VARCHAR(20) NOT NULL,
  date_mouvement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reference_table ENUM('don', 'distribution', 'achat', 'vente') NOT NULL,
  reference_id INT NOT NULL,
  CONSTRAINT fk_mouvement_sous_type
    FOREIGN KEY (id_sous_type) REFERENCES sous_type_don(id_sous_type)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE INDEX idx_don_date ON don(date_don);
CREATE INDEX idx_besoin_statut ON besoin(statut);
CREATE INDEX idx_distribution_date ON distribution(date_distribution);
CREATE INDEX idx_achat_date ON achat(date_achat);
CREATE INDEX idx_vente_date ON vente(date_vente);
CREATE INDEX idx_mouvement_date ON mouvement_stock(date_mouvement);

INSERT INTO ville (nom_ville, image_ville) VALUES
('Toamasina', 'toamasina.jpg'),
('Mananjary', 'manajary.jpg'),
('Farafangana', 'farafangana.jpg'),
('Nosy Be', 'nosybe.jpg'),
('Morondava', 'morondava.jpg');

INSERT INTO type_don (nom_type) VALUES
('nature'),
('materiaux'),
('argent');

INSERT INTO sous_type_don (id_type, nom_sous_type, unite_defaut, image_sous_type) VALUES
(1, 'riz', 'kg', 'riz.png'),
(1, 'eau', 'litre', 'eau.png'),
(1, 'huile', 'litre', 'huile.png'),
(1, 'haricots', 'kg', 'haricots.png'),
(2, 'clou', 'kg', 'cloues.png'),
(2, 'tole', 'metre', 'toles.png'),
(2, 'bois', 'metre', 'bois.png'),
(2, 'bache', 'unite', 'bache.png'),
(2, 'groupe', 'unite', 'groupe.png'),
(3, 'argent', 'arriary', 'argent.png');

INSERT INTO prix (id_sous_type, prix_unitaire, date_prix) VALUES
(1, 3000.00, '2026-02-15 00:00:00'),
(2, 1000.00, '2026-02-15 00:00:00'),
(3, 6000.00, '2026-02-15 00:00:00'),
(4, 4000.00, '2026-02-15 00:00:00'),
(5, 8000.00, '2026-02-15 00:00:00'),
(6, 25000.00, '2026-02-15 00:00:00'),
(7, 10000.00, '2026-02-15 00:00:00'),
(8, 15000.00, '2026-02-15 00:00:00'),
(9, 6750000.00, '2026-02-15 00:00:00'),
(10, 1.00, '2026-02-15 00:00:00');

INSERT INTO besoin (id_ville, id_sous_type, quantite_requise, quantite_restante, unite, date_besoin, statut) VALUES
(1, 1, 800.00, 800.00, 'kg', '2026-02-16 00:00:00', 'ouvert'),
(1, 2, 1500.00, 1500.00, 'litre', '2026-02-15 00:00:00', 'ouvert'),
(1, 6, 120.00, 120.00, 'metre', '2026-02-16 00:00:00', 'ouvert'),
(1, 8, 200.00, 200.00, 'unite', '2026-02-15 00:00:00', 'ouvert'),
(1, 10, 12000000.00, 12000000.00, 'arriary', '2026-02-16 00:00:00', 'ouvert'),
(2, 1, 500.00, 500.00, 'kg', '2026-02-15 00:00:00', 'ouvert'),
(2, 3, 120.00, 120.00, 'litre', '2026-02-16 00:00:00', 'ouvert'),
(2, 6, 80.00, 80.00, 'metre', '2026-02-15 00:00:00', 'ouvert'),
(2, 5, 60.00, 60.00, 'kg', '2026-02-16 00:00:00', 'ouvert'),
(2, 10, 6000000.00, 6000000.00, 'arriary', '2026-02-15 00:00:00', 'ouvert'),
(3, 1, 600.00, 600.00, 'kg', '2026-02-16 00:00:00', 'ouvert'),
(3, 2, 1000.00, 1000.00, 'litre', '2026-02-15 00:00:00', 'ouvert'),
(3, 8, 150.00, 150.00, 'unite', '2026-02-16 00:00:00', 'ouvert'),
(3, 7, 100.00, 100.00, 'metre', '2026-02-15 00:00:00', 'ouvert'),
(3, 10, 8000000.00, 8000000.00, 'arriary', '2026-02-16 00:00:00', 'ouvert'),
(4, 1, 300.00, 300.00, 'kg', '2026-02-15 00:00:00', 'ouvert'),
(4, 4, 200.00, 200.00, 'kg', '2026-02-16 00:00:00', 'ouvert'),
(4, 6, 40.00, 40.00, 'metre', '2026-02-15 00:00:00', 'ouvert'),
(4, 5, 30.00, 30.00, 'kg', '2026-02-16 00:00:00', 'ouvert'),
(4, 10, 4000000.00, 4000000.00, 'arriary', '2026-02-15 00:00:00', 'ouvert'),
(5, 1, 700.00, 700.00, 'kg', '2026-02-16 00:00:00', 'ouvert'),
(5, 2, 1200.00, 1200.00, 'litre', '2026-02-15 00:00:00', 'ouvert'),
(5, 8, 180.00, 180.00, 'unite', '2026-02-16 00:00:00', 'ouvert'),
(5, 7, 150.00, 150.00, 'metre', '2026-02-15 00:00:00', 'ouvert'),
(5, 10, 10000000.00, 10000000.00, 'arriary', '2026-02-16 00:00:00', 'ouvert'),
(1, 9, 3.00, 3.00, 'unite', '2026-02-15 00:00:00', 'ouvert');

INSERT INTO don (id_ville, id_sous_type, quantite, unite, date_don) VALUES
(1, 10, 5000000.00, 'arriary', '2026-02-16 00:00:00'),
(1, 10, 3000000.00, 'arriary', '2026-02-16 00:00:00'),
(1, 10, 4000000.00, 'arriary', '2026-02-17 00:00:00'),
(1, 10, 1500000.00, 'arriary', '2026-02-17 00:00:00'),
(1, 10, 6000000.00, 'arriary', '2026-02-17 00:00:00'),
(1, 1, 400.00, 'kg', '2026-02-16 00:00:00'),
(1, 2, 600.00, 'litre', '2026-02-16 00:00:00'),
(1, 6, 50.00, 'metre', '2026-02-17 00:00:00'),
(1, 8, 70.00, 'unite', '2026-02-17 00:00:00'),
(1, 4, 100.00, 'kg', '2026-02-17 00:00:00'),
(1, 1, 2000.00, 'kg', '2026-02-18 00:00:00'),
(1, 6, 300.00, 'metre', '2026-02-18 00:00:00'),
(1, 2, 5000.00, 'litre', '2026-02-18 00:00:00'),
(1, 10, 20000000.00, 'arriary', '2026-02-19 00:00:00'),
(1, 8, 500.00, 'unite', '2026-02-19 00:00:00'),
(1, 4, 88.00, 'kg', '2026-02-17 00:00:00');

INSERT INTO stock (id_sous_type, quantite_disponible, unite) VALUES
(1, 2400.00, 'kg'),
(2, 5600.00, 'litre'),
(3, 0.00, 'litre'),
(4, 188.00, 'kg'),
(5, 0.00, 'kg'),
(6, 350.00, 'metre'),
(7, 0.00, 'metre'),
(8, 570.00, 'unite'),
(9, 0.00, 'unite'),
(10, 39500000.00, 'arriary');

INSERT INTO mouvement_stock (type_mouvement, id_sous_type, quantite, unite, date_mouvement, reference_table, reference_id) VALUES
('ENTREE_DON', 10, 5000000.00, 'arriary', '2026-02-16 00:00:00', 'don', 1),
('ENTREE_DON', 10, 3000000.00, 'arriary', '2026-02-16 00:00:00', 'don', 2),
('ENTREE_DON', 10, 4000000.00, 'arriary', '2026-02-17 00:00:00', 'don', 3),
('ENTREE_DON', 10, 1500000.00, 'arriary', '2026-02-17 00:00:00', 'don', 4),
('ENTREE_DON', 10, 6000000.00, 'arriary', '2026-02-17 00:00:00', 'don', 5),
('ENTREE_DON', 1, 400.00, 'kg', '2026-02-16 00:00:00', 'don', 6),
('ENTREE_DON', 2, 600.00, 'litre', '2026-02-16 00:00:00', 'don', 7),
('ENTREE_DON', 6, 50.00, 'metre', '2026-02-17 00:00:00', 'don', 8),
('ENTREE_DON', 8, 70.00, 'unite', '2026-02-17 00:00:00', 'don', 9),
('ENTREE_DON', 4, 100.00, 'kg', '2026-02-17 00:00:00', 'don', 10),
('ENTREE_DON', 1, 2000.00, 'kg', '2026-02-18 00:00:00', 'don', 11),
('ENTREE_DON', 6, 300.00, 'metre', '2026-02-18 00:00:00', 'don', 12),
('ENTREE_DON', 2, 5000.00, 'litre', '2026-02-18 00:00:00', 'don', 13),
('ENTREE_DON', 10, 20000000.00, 'arriary', '2026-02-19 00:00:00', 'don', 14),
('ENTREE_DON', 8, 500.00, 'unite', '2026-02-19 00:00:00', 'don', 15),
('ENTREE_DON', 4, 88.00, 'kg', '2026-02-17 00:00:00', 'don', 16);
