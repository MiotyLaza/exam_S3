<?php
require_once __DIR__ . '/repositories/DonationRepository.php';
require_once __DIR__ . '/controllers/DistributionController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/AchatController.php';
require_once __DIR__ . '/controllers/RecapController.php';

Flight::route('GET /', ['DistributionController', 'showForm']);
Flight::route('GET /distribution', ['DistributionController', 'showForm']);
Flight::route('POST /distribution', ['DistributionController', 'postDistribution']);
Flight::route('POST /distribution/vente', ['DistributionController', 'postVente']);
Flight::route('GET /achat', ['AchatController', 'showForm']);
Flight::route('POST /achat', ['AchatController', 'postAchat']);
Flight::route('GET /dashboard', ['DashboardController', 'showDashboard']);
Flight::route('GET /recapitulatif', ['RecapController', 'showRecap']);
Flight::route('GET /api/recapitulatif', ['RecapController', 'apiRecap']);
