<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: log.php");
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM saved_tenders WHERE user_id = :user_id ORDER BY saved_at DESC");
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();

$savedTenders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's subscription status for feature restrictions
$userPlan = 'free'; // This should come from your user subscription logic
$maxSavedTenders = [
    'free' => 3,
    'basic' => 20,
    'premium' => 999999,
    'enterprise' => 999999
];

$canSaveMore = count($savedTenders) < $maxSavedTenders[$userPlan];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage your saved tender opportunities. View, organize and export your bookmarked tenders.">
    <title>Saved Tenders | TenderAlert</title>
    <!-- Open Graph tags -->
    <meta property="og:title" content="Saved Tenders | TenderAlert">
    <meta property="og:description" content="Manage your saved tender opportunities">
    <meta property="og:type" content="website">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Enhanced styles matching subscription page */
        .saved-header {
            background: linear-gradient(135deg, rgba(37, 85, 157, 0.95), rgba(26, 59, 110, 0.95)), url('Tender_Application_Image.jpg') no-repeat center/cover;
            min-height: 400px;
        }

        .saved-header .header-text h1 {
            font-size: 42px;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-left: 5px solid #25559D;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card.premium {
            border-left-color: #FE8900;
        }

        .stat-card.success {
            border-left-color: #259B45;
        }

        .stat-card.warning {
            border-left-color: #FFC107;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #25559D;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            float: right;
            font-size: 2rem;
            color: #25559D;
            opacity: 0.3;
        }

        .actions-bar {
            background: white;
            border-radius: 15px;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .search-container {
            position: relative;
            flex: 1;
            min-width: 300px;
        }

        .search-input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 3rem;
            border: 2px solid #e5e5e5;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: #25559D;
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 85, 157, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.6rem 1rem;
            border: 2px solid #e5e5e5;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .filter-btn.active,
        .filter-btn:hover {
            border-color: #25559D;
            background: #25559D;
            color: white;
        }

        .export-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .export-btn {
            padding: 0.8rem 1.2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .export-btn.excel {
            background: #259B45;
            color: white;
        }

        .export-btn.pdf {
            background: #FE8900;
            color: white;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .tenders-grid {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .tender-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            border-left: 5px solid #25559D;
        }

        .tender-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .tender-card.urgent {
            border-left-color: #dc3545;
        }

        .tender-card.closing-soon {
            border-left-color: #FFC107;
        }

        .tender-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .tender-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #25559D;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .tender-number {
            font-size: 0.9rem;
            color: #ffffff;
            font-weight: 500;
        }

        .tender-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .action-btn.view {
            background: #25559D;
            color: white;
        }

        .action-btn.remove {
            background: #dc3545;
            color: white;
        }

        .action-btn:hover {
            transform: scale(1.05);
        }

        .tender-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .meta-icon {
            color: #25559D;
            width: 16px;
        }

        .meta-value {
            font-weight: 500;
            color: #333;
        }

        .tender-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .tender-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .tender-tag {
            background: rgba(37, 85, 157, 0.1);
            color: #25559D;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-badge {
            position: absolute;
            top: 5rem;
            right: 2rem;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.urgent {
            background: #dc3545;
            color: white;
        }

        .status-badge.closing-soon {
            background: #FFC107;
            color: #333;
        }

        .status-badge.active {
            background: #259B45;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #999;
            margin-bottom: 2rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination button {
            padding: 0.5rem 1rem;
            border: 2px solid #e5e5e5;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pagination button.active,
        .pagination button:hover {
            border-color: #25559D;
            background: #25559D;
            color: white;
        }

        /* Modal Enhancement */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #25559D, #1c4279);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            position: relative;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            background: none;
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .modal-close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 2rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        .detail-table th,
        .detail-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e5e5;
        }

        .detail-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #25559D;
            width: 30%;
        }

        .detail-table td {
            color: #333;
        }

        .document-list {
            list-style: none;
            padding: 0;
        }

        .document-list li {
            margin-bottom: 0.5rem;
        }

        .document-list a {
            color: #25559D;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 6px;
            transition: background 0.3s ease;
        }

        .document-list a:hover {
            background: rgba(37, 85, 157, 0.1);
        }

        .plan-upgrade-notice {
            background: linear-gradient(135deg, #FE8900, #e67a00);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .plan-upgrade-notice h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
        }

        .plan-upgrade-notice p {
            margin: 0 0 1rem 0;
            opacity: 0.9;
        }

        .upgrade-btn {
            background: white;
            color: #FE8900;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upgrade-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            .saved-header .header-text h1 {
                font-size: 2.5rem;
            }

            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .actions-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-container {
                min-width: auto;
            }

            .export-buttons,
            .filter-buttons {
                justify-content: center;
            }

            .tender-header {
                flex-direction: column;
                gap: 1rem;
            }

            .tender-meta {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 5% auto;
            }

            .modal-header,
            .modal-body {
                padding: 1.5rem;
            }

            .detail-table th,
            .detail-table td {
                padding: 0.8rem 0.5rem;
                font-size: 0.9rem;
            }

            .detail-table th {
                width: 35%;
            }
        }

        .demo-container {
            display: none; /* Hidden by default */
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        text-align: center;
        max-width: 400px;
        width: 100%;
        margin: 20px auto;
        }

        .demo-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(238, 90, 82, 0.3);
        }

        .demo-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(238, 90, 82, 0.4);
        }

        /* Confirmation Modal Overlay */
        .confirmation-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .confirmation-modal {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            max-width: 450px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            animation: slideIn 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: scale(0.8) translateY(-50px);
            }
            to { 
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .confirmation-modal::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #ff6b6b, #ee5a52, #ff9ff3, #f368e0);
            background-size: 300% 100%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .modal-icon {
            font-size: 4rem;
            color: #ff6b6b;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .modal-message {
            font-size: 16px;
            color: #7f8c8d;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .tender-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 4px solid #ff6b6b;
            text-align: left;
        }

        .tender-info strong {
            color: #2c3e50;
            font-weight: 600;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .modal-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-width: 120px;
        }

        .confirm-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            box-shadow: 0 5px 15px rgba(238, 90, 82, 0.3);
        }

        .confirm-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(238, 90, 82, 0.4);
        }

        .confirm-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .cancel-btn {
            background: #f8f9fa;
            color: #6c757d;
            border: 2px solid #e9ecef;
        }

        .cancel-btn:hover {
            background: #e9ecef;
            color: #495057;
            transform: translateY(-1px);
        }

        /* Notification Styles */
        .notification {
            position: fixed;
            top: 30px;
            right: 30px;
            max-width: 400px;
            padding: 20px 25px;
            border-radius: 15px;
            color: white;
            font-weight: 500;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            transform: translateX(100%);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: linear-gradient(135deg, #00b894, #00a085);
        }

        .notification.error {
            background: linear-gradient(135deg, #e17055, #d63031);
        }

        .notification-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .notification-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: background 0.3s ease;
            margin-left: auto;
            flex-shrink: 0;
        }

        .notification-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .confirmation-modal {
                margin: 20px;
                padding: 30px 20px;
            }
            
            .modal-actions {
                flex-direction: column;
            }
            
            .modal-btn {
                width: 100%;
            }
            
            .notification {
                top: 20px;
                right: 20px;
                left: 20px;
                max-width: none;
                transform: translateY(-100%);
            }
            
            .notification.show {
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <header class="saved-header">
        <div class="top-nav">
            <nav class="centered-nav">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="#" class="active"><i class="fas fa-bookmark"></i> Saved Tenders</a>
                <a href="subscriptions.php"><i class="fas fa-credit-card"></i> Subscriptions</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        <div class="header-text">
            <h1>Saved Tenders</h1>
            <p>Manage your bookmarked opportunities. Review, organize and export your saved tenders.</p>
        </div>
    </header>

    <div class="container">
        <!-- Stats Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <i class="fas fa-bookmark stat-icon"></i>
                <div class="stat-number"><?= count($savedTenders) ?></div>
                <div class="stat-label">Total Saved</div>
            </div>
            <div class="stat-card success">
                <i class="fas fa-calendar-check stat-icon"></i>
                <div class="stat-number"><?= count(array_filter($savedTenders, function($t) { return strtotime($t['closing_Date']) > time(); })) ?></div>
                <div class="stat-label">Still Active</div>
            </div>
            <div class="stat-card warning">
                <i class="fas fa-clock stat-icon"></i>
                <div class="stat-number"><?= count(array_filter($savedTenders, function($t) { return strtotime($t['closing_Date']) > time() && strtotime($t['closing_Date']) < strtotime('+7 days'); })) ?></div>
                <div class="stat-label">Closing Soon</div>
            </div>
            <div class="stat-card premium">
                <i class="fas fa-chart-line stat-icon"></i>
                <div class="stat-number"><?= $maxSavedTenders[$userPlan] === 999999 ? '∞' : $maxSavedTenders[$userPlan] ?></div>
                <div class="stat-label">Plan Limit</div>
            </div>
        </div>

        <?php if (!$canSaveMore): ?>
        <div class="plan-upgrade-notice">
            <h4><i class="fas fa-exclamation-triangle"></i> Upgrade Required</h4>
            <p>You've reached your plan limit of <?= $maxSavedTenders[$userPlan] ?> saved tenders. Upgrade to save more opportunities!</p>
            <button class="upgrade-btn" onclick="window.location.href='subscriptions.php'">
                <i class="fas fa-arrow-up"></i> Upgrade Now
            </button>
        </div>
        <?php endif; ?>

        <!-- Actions Bar -->
        <div class="actions-bar">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" id="searchInput" placeholder="Search tenders by title, description, or department...">
            </div>
            
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="active">Active</button>
                <button class="filter-btn" data-filter="closing-soon">Closing Soon</button>
                <button class="filter-btn" data-filter="expired">Expired</button>
            </div>

            <!-- <div class="export-buttons">
                <button class="export-btn excel" onclick="exportTableToExcel()">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <button class="export-btn pdf" onclick="printTable()">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
            </div> -->
        </div>

        <!-- Tenders Grid -->
        <div class="tenders-grid" id="tendersGrid">
            <?php if (empty($savedTenders)): ?>
                <div class="empty-state">
                    <i class="fas fa-bookmark"></i>
                    <h3>No Saved Tenders</h3>
                    <p>You haven't saved any tenders yet. Browse available tenders and bookmark the ones that interest you.</p>
                    <a href="index.php" class="btn btn-primary">Browse Tenders</a>
                </div>
            <?php else: ?>
                <?php foreach ($savedTenders as $tender): 
                    $closingDate = strtotime($tender['closing_Date']);
                    $isActive = $closingDate > time();
                    $isClosingSoon = $isActive && $closingDate < strtotime('+7 days');
                    $isUrgent = $isActive && $closingDate < strtotime('+3 days');
                    
                    $statusClass = '';
                    $statusText = '';
                    if ($isUrgent) {
                        $statusClass = 'urgent';
                        $statusText = 'Urgent';
                    } elseif ($isClosingSoon) {
                        $statusClass = 'closing-soon';
                        $statusText = 'Closing Soon';
                    } elseif ($isActive) {
                        $statusClass = 'active';
                        $statusText = 'Active';
                    } else {
                        $statusClass = 'expired';
                        $statusText = 'Expired';
                    }
                ?>
                <div class="tender-card <?= $statusClass ?>" data-filter="<?= $statusClass ?>" data-search="<?= strtolower($tender['tender_No'] . ' ' . $tender['description'] . ' ' . $tender['department'] . ' ' . $tender['category']) ?>">
                    <?php if ($statusText): ?>
                        <div class="status-badge <?= $statusClass ?>"><?= $statusText ?></div>
                    <?php endif; ?>
                    
                    <div class="tender-header">
                        <div>
                            <h3 class="tender-title"><?= htmlspecialchars($tender['tender_No']) ?></h3>
                            <div class="tender-number" >Saved on <?= date('d M Y', strtotime($tender['saved_at'])) ?></div>
                        </div>
                        <div class="tender-actions">
                            <button class="action-btn view" onclick='viewTender(this)' data-tender='<?= htmlspecialchars(json_encode($tender), ENT_QUOTES, "UTF-8") ?>' title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn remove" onclick="unbookmarkTender(<?= $tender['id'] ?>)" title="Remove">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="tender-description">
                        <?= htmlspecialchars($tender['description']) ?>
                    </div>

                    <div class="tender-meta">
                        <div class="meta-item">
                            <i class="fas fa-building meta-icon"></i>
                            <span class="meta-value"><?= htmlspecialchars($tender['department']) ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt meta-icon"></i>
                            <span class="meta-value"><?= htmlspecialchars($tender['province']) ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt meta-icon"></i>
                            <span class="meta-value"><?= date('d M Y', strtotime($tender['date_Published'])) ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-clock meta-icon"></i>
                            <span class="meta-value"><?= date('d M Y', strtotime($tender['closing_Date'])) ?></span>
                        </div>
                    </div>

                    <div class="tender-tags">
                        <span class="tender-tag"><?= htmlspecialchars($tender['category']) ?></span>
                        <?php if (!empty($tender['contactPerson'])): ?>
                            <span class="tender-tag"><i class="fas fa-user"></i> Contact Available</span>
                        <?php endif; ?>
                        <?php if (!empty($tender['briefingSession']) && $tender['briefingSession']): ?>
                            <span class="tender-tag"><i class="fas fa-presentation"></i> Briefing Session</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Enhanced Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Tender Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <div class="demo-container" id="demoSection">
    <h2 style="margin-bottom: 20px; color: #2c3e50;">Demo: Tender Removal</h2>
    <p style="margin-bottom: 30px; color: #7f8c8d;">Click the button below to see the confirmation popup in action:</p>
    <button class="demo-btn" onclick="showRemoveConfirmation('tender123', 'Government IT Infrastructure Upgrade Project - Phase 2')">
        <i class="fas fa-trash-alt"></i> Remove Tender
    </button>
</div>

    <!-- Confirmation Modal -->
    <div id="confirmationOverlay" class="confirmation-overlay">
        <div class="confirmation-modal">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="modal-title">Confirm Removal</h3>
            <p class="modal-message">Are you sure you want to remove this tender from your saved list? This action cannot be undone.</p>
            
            <div id="tenderInfo" class="tender-info">
                <!-- Tender info will be populated here -->
            </div>
            
            <div class="modal-actions">
                <button id="confirmRemoveBtn" class="modal-btn confirm-btn">
                    <i class="fas fa-trash-alt"></i> Yes, Remove
                </button>
                <button class="modal-btn cancel-btn" onclick="hideRemoveConfirmation()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const cards = document.querySelectorAll('.tender-card');
            
            cards.forEach(card => {
                const searchData = card.getAttribute('data-search');
                if (searchData.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active filter button
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const filter = this.getAttribute('data-filter');
                const cards = document.querySelectorAll('.tender-card');

                cards.forEach(card => {
                    if (filter === 'all') {
                        card.style.display = 'block';
                    } else {
                        const cardFilter = card.getAttribute('data-filter');
                        if (filter === 'closing-soon' && (cardFilter === 'closing-soon' || cardFilter === 'urgent')) {
                            card.style.display = 'block';
                        } else if (cardFilter === filter) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
            });
        });

        function viewTender(button) {
            try {
                const tenderJson = button.getAttribute("data-tender");
                const tender = JSON.parse(tenderJson);

                if (tender.supportDocument) {
                    try {
                        tender.supportDocument = JSON.parse(tender.supportDocument);
                    } catch (error) {
                        console.warn('Invalid supportDocument JSON:', error);
                        tender.supportDocument = [];
                    }
                } else {
                    tender.supportDocument = [];
                }

                const documentLinksHtml = tender.supportDocument.length > 0
                    ? tender.supportDocument.map(doc => {
                        const blobName = doc.supportDocumentID || '';
                        const fileName = doc.fileName || 'Document.pdf';
                        const extension = doc.extension || '';
                        const docUrl = `https://www.etenders.gov.za/home/Download?blobName=${encodeURIComponent(blobName)}.pdf&downloadedFileName=${encodeURIComponent(fileName)}`;
                        return `<li><a href="${docUrl}" target="_blank"><i class="fas fa-file-pdf"></i> ${fileName} (${extension.toUpperCase().replace('.', '')})</a></li>`;
                    }).join('')
                    : '<p style="color: #666; font-style: italic;">No documents available</p>';

                document.getElementById('modalTitle').textContent = tender.tender_No;
                document.getElementById("modalContent").innerHTML = `
                    <table class="detail-table">
                        <tr><th><i class="fas fa-info-circle"></i> Description</th><td>${tender.description}</td></tr>
                        <tr><th><i class="fas fa-tags"></i> Category</th><td>${tender.category}</td></tr>
                        <tr><th><i class="fas fa-clock"></i> Closing Date</th><td>${tender.closing_Date}</td></tr>
                        <tr><th><i class="fas fa-calendar-alt"></i> Published</th><td>${tender.date_Published}</td></tr>
                        <tr><th><i class="fas fa-building"></i> Department</th><td>${tender.department}</td>
                        <tr><th><i class="fas fa-map-marker-alt"></i> Province</th><td>${tender.province}</td></tr>
                        <tr><th><i class="fas fa-user"></i> Contact Person</th><td>${tender.contactPerson}</td></tr>
                        <tr><th><i class="fas fa-envelope"></i> Email</th><td>${tender.email}</td></tr>
                        <tr><th><i class="fas fa-phone"></i> Telephone</th><td>${tender.telephone}</td></tr>
                        <tr><th><i class="fas fa-fax"></i> Fax</th><td>${tender.fax}</td></tr>
                        <tr><th><i class="fas fa-map"></i> Address</th><td>${tender.streetname}, ${tender.surburb}, ${tender.town}, ${tender.code}</td></tr>
                        <tr><th><i class="fas fa-list"></i> Conditions</th><td>${tender.conditions}</td></tr>
                        <tr><th><i class="fas fa-users"></i> Briefing Session</th><td>${tender.briefingSession ? 'Yes' : 'No'} </td></tr>
                        <tr><th><i class="fas fa-exclamation-triangle"></i> Compulsory:</th><td>${tender.briefingCompulsory ? 'Yes' : 'No'}</td></tr>
                        <tr><th><i class="fas fa-map-pin"></i> Briefing Venue</th><td>${tender.briefingVenue} </td></tr>
                        <tr><th><i class="fas fa-clock"></i> Briefing Date and Time</th><td><?= !empty($tender['compulsory_briefing_session']) ? date('Y-m-d H:i', strtotime($tender['compulsory_briefing_session'])) : 'N/A' ?></td></tr>
                        <tr><th><i class="fas fa-file"></i> Documents</th><td><ul>${documentLinksHtml}</ul></td></tr>
            </table>
        `;

        document.getElementById("modal").style.display = "block";
        document.querySelector(".container").style.display = "none";

    } catch (error) {
        console.error("Failed to parse tender JSON:", error);
        alert("Could not load tender details. Check console for details.");
    }

}
function closeModal() {
    document.getElementById("modal").style.display = "none";
    document.querySelector(".container").style.display = "block";
}

function unbookmarkTender(id) {
        if (confirm("Are you sure you want to remove this tender?")) {
            fetch("remove_saved_tender.php?id=" + id)
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    location.reload();
                })
                .catch(err => alert("Error removing tender."));
        }
    }

    let currentTenderId = null;
        let currentTenderTitle = null;

        function showRemoveConfirmation(tenderId, tenderTitle) {
    currentTenderId = tenderId;
    currentTenderTitle = tenderTitle;

    // Update the tender info in the modal
    document.getElementById('tenderInfo').innerHTML = `
        <strong>Tender:</strong><br>
        ${tenderTitle}
    `;

    // Update the confirm button click handler
    const confirmBtn = document.getElementById('confirmRemoveBtn');
    confirmBtn.onclick = () => confirmRemoveTender();

    // Show the modal
    document.getElementById('confirmationOverlay').style.display = 'flex';

    // Prevent body scrolling
    document.body.style.overflow = 'hidden';

    // Show the demo section only when delete button is clicked
    document.getElementById('demoSection').style.display = 'block';
}

function hideRemoveConfirmation() {
    document.getElementById('confirmationOverlay').style.display = 'none';
    document.body.style.overflow = 'auto';

    // Optionally hide the demo section when modal is closed/cancelled
    document.getElementById('demoSection').style.display = 'none';

    currentTenderId = null;
    currentTenderTitle = null;
}

// No other change needed unless you want to hide demo on successful removal
function confirmRemoveTender() {
    if (!currentTenderId) {
        showNotification('Error: No tender selected', 'error');
        return;
    }

    // Add loading state
    const confirmBtn = document.getElementById('confirmRemoveBtn');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner spinner"></i> Removing...';
    confirmBtn.disabled = true;

    // Simulate API call (replace with actual API)
    setTimeout(() => {
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;

        // Simulate success
        const success = true; // or your actual API response

        if (success) {
            showNotification('Tender removed successfully!', 'success');

            // Optionally hide the demo section after removal
            document.getElementById('demoSection').style.display = 'none';

            // Close modal
            hideRemoveConfirmation();

            // Remove the tender card from DOM (if desired)
            // document.querySelector(`[data-tender='${currentTenderId}']`).remove();

        } else {
            showNotification('Failed to remove tender. Please try again.', 'error');
        }
    }, 2000);
}
            
        function showNotification(message, type = 'success') {
            // Remove any existing notifications
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notification => notification.remove());

            // Create new notification
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            notification.innerHTML = `
                <i class="fas ${icon} notification-icon"></i>
                <span>${message}</span>
                <button class="notification-close" onclick="this.parentNode.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;

            document.body.appendChild(notification);

            // Show notification with animation
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 400);
                }
            }, 5000);
        }

        // Close modal when clicking outside
        document.getElementById('confirmationOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                hideRemoveConfirmation();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideRemoveConfirmation();
            }
        });

</script>
<?php
include 'footer.php';
?>
</body>
</html>