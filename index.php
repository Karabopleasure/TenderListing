<?php
session_start();
require_once 'db_connect.php';
require_once 'functions.php';
require_once 'tender_scrape_function.php';

$outputFile = __DIR__ . '/tender.json';

if (!file_exists($outputFile) || (time() - filemtime($outputFile) > 300)) {
    scrapeTenders($outputFile);
}

$jsonFile = 'tender.json';
$tenders = [];

if (file_exists($jsonFile)) {
    $jsonData = file_get_contents($jsonFile);
    $tenders = json_decode($jsonData, true);
    if (!is_array($tenders)) {
        $tenders = [];
    }
} else {
    $tenders = [];
}

$keyword    = strtolower(trim($_GET['keyword'] ?? ''));
$department = strtolower(trim($_GET['department'] ?? ''));
$category   = strtolower(trim($_GET['category'] ?? ''));
$province   = strtolower(trim($_GET['province'] ?? ''));
$filter_type = $_GET['filter_type'] ?? ''; // New filter type parameter

// Handle different filter types from stat cards
switch ($filter_type) {
    case 'active':
        $tenders = array_filter($tenders, function($t) { 
            return strtotime($t['closing_Date']) > time(); 
        });
        break;
    case 'new_week':
        $tenders = array_filter($tenders, function($t) { 
            return strtotime($t['date_Published']) > strtotime('-7 days'); 
        });
        break;
    case 'departments':
        // Show department list view
        break;
}

// Apply search filters
if ($keyword || $department || $category || $province) {
    $tenders = array_filter($tenders, function ($tender) use ($keyword, $department, $category, $province) {
        $textFields = strtolower(
            ($tender['description'] ?? '') . ' ' .
            ($tender['tender_No'] ?? '') . ' ' .
            ($tender['organ_of_State'] ?? '')
        );
        return 
            (!$keyword || strpos($textFields, $keyword) !== false) &&
            (!$department || strpos(strtolower($tender['department'] ?? ''), $department) !== false) &&
            (!$category || strpos(strtolower($tender['category'] ?? ''), $category) !== false) &&
            (!$province || strpos(strtolower($tender['province'] ?? ''), $province) !== false);
    });
}

// Get user's preferred departments if logged in
$userPreferences = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT preferred_departments FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && $result['preferred_departments']) {
            $userPreferences = json_decode($result['preferred_departments'], true) ?? [];
        }
    } catch (Exception $e) {
        // Handle error silently or log it
    }
}

$queryParams = $_GET;

$perPage = 25;
$total = count($tenders);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $perPage;
$paginatedTenders = array_slice($tenders, $start, $perPage);
$totalPages = ceil($total / $perPage);

// Get unique departments for department listing
$allDepartments = array_unique(array_filter(array_column($tenders, 'department')));
sort($allDepartments);

function formatDateTime($dateString) {
    if (empty($dateString)) return 'N/A';
    try {
        $dt = new DateTime($dateString);
        return $dt->format('d M Y, H:i');
    } catch (Exception $e) {
        return htmlspecialchars($dateString);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse the latest government tenders and business opportunities in South Africa. Find relevant tenders, save favorites, and stay updated with new opportunities.">
    <title>Tender Listings | TenderAlert</title>
    <!-- Open Graph tags -->
    <meta property="og:title" content="Tender Listings | TenderAlert">
    <meta property="og:description" content="Browse the latest government tenders and business opportunities in South Africa">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://yourwebsite.com/index.php">
    <!-- Font Awesome -->
     <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stats-section .stat-card {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .stats-section .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .department-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .department-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid #25559D;
        }
        
        .department-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .department-card.preferred {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #f8fff9 0%, #ffffff 100%);
        }
        
        .department-card h3 {
            margin: 0 0 0.5rem 0;
            color: #333;
            font-size: 1.1rem;
        }
        
        .department-card .tender-count {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .department-card .department-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .preference-btn {
            background: none;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .preference-btn.active {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        
        .preference-btn:hover {
            background: #25559D;
            color: white;
            border-color: #25559D;
        }
        
        .preferences-panel {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .preferences-panel h3 {
            margin: 0 0 1rem 0;
            color: #333;
        }
        
        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .filter-breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .filter-breadcrumb a {
            color: #25559D;
            text-decoration: none;
        }
        
        .filter-breadcrumb a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <div class="top-nav">
            <nav class="centered-nav">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="saved_tender.php"><i class="fas fa-bookmark"></i> Saved Tenders</a>
                <?php endif;?>
                <a href="subscriptions.php"><i class="fas fa-credit-card"></i> Subscriptions</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> SignIn</a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="header-text">
            <h1>Explore Government Tenders</h1>
            <p>Stay updated with the latest eTenders and opportunities from departments across South Africa.</p>
        </div>
    </header>

    <div class="container">
        <!-- Search Section -->
        <div class="search-section">
            <h2><i class="fas fa-search"></i> Find Your Perfect Tender</h2>
            <form method="GET" class="search-form">
                <input type="text" name="keyword" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>" placeholder="Search by keyword...">
                <input type="text" name="department" value="<?= htmlspecialchars($_GET['department'] ?? '') ?>" placeholder="Department">
                <input type="text" name="category" value="<?= htmlspecialchars($_GET['category'] ?? '') ?>" placeholder="Category">
                <input type="text" name="province" value="<?= htmlspecialchars($_GET['province'] ?? '') ?>" placeholder="Province">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Search Tenders
                </button>
            </form>
        </div>

        <!-- User Preferences Panel (for logged in users) -->
        <?php if (isset($_SESSION['user_id']) && empty($_GET['filter_type'])): ?>
        <div class="preferences-panel">
            <h3><i class="fas fa-star"></i> Your Preferred Departments</h3>
            <?php if (!empty($userPreferences)): ?>
                <p>Quick access to tenders from your preferred departments:</p>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem;">
                    <?php foreach ($userPreferences as $dept): ?>
                        <a href="?department=<?= urlencode($dept) ?>" class="preference-btn active">
                            <?= htmlspecialchars($dept) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Set your preferred departments to get quick access to relevant tenders. <a href="?filter_type=departments">Browse all departments</a> to get started.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Filter Breadcrumb -->
       <?php $filterType = $_GET['filter_type'] ?? null;?>
        <?php if (!empty($_GET['filter_type']) || !empty($_GET['department'])): ?>
        <div class="filter-header">
            <div class="filter-breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <?php if ($filterType === 'departments'): ?>
    <i class="fas fa-chevron-right"></i>
    <span>All Departments</span>
<?php elseif ($filterType === 'active'): ?>
    <i class="fas fa-chevron-right"></i>
    <span>Active Tenders</span>
<?php elseif ($filterType === 'new_week'): ?>
    <i class="fas fa-chevron-right"></i>
    <span>New This Week</span>
<?php elseif (!empty($_GET['department'])): ?>
    <i class="fas fa-chevron-right"></i>
    <span><?= htmlspecialchars($_GET['department']) ?> Tenders</span>
<?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Section -->
        <div class="stats-section">
            <div class="stat-card" onclick="window.location.href='index.php'">
                <div class="icon"><i class="fas fa-file-contract"></i></div>
                <h3><?= number_format($total) ?></h3>
                <p>Total Tenders</p>
            </div>
            <div class="stat-card" onclick="window.location.href='?filter_type=active'">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <h3><?= count(array_filter($tenders, function($t) { return strtotime($t['closing_Date']) > time(); })) ?></h3>
                <p>Active Tenders</p>
            </div>
            <div class="stat-card" onclick="window.location.href='?filter_type=new_week'">
                <div class="icon"><i class="fas fa-calendar-day"></i></div>
                <h3><?= count(array_filter($tenders, function($t) { return strtotime($t['date_Published']) > strtotime('-7 days'); })) ?></h3>
                <p>New This Week</p>
            </div>
            <div class="stat-card" onclick="window.location.href='?filter_type=departments'">
                <div class="icon"><i class="fas fa-users"></i></div>
                <h3><?= count(array_unique(array_column($tenders, 'department'))) ?></h3>
                <p>Departments</p>
            </div>
        </div>

        <?php if ($filterType === 'departments'): ?>
            <!-- Department Grid View -->
            <div class="department-grid">
                <?php foreach ($allDepartments as $dept): 
                    $deptTenderCount = count(array_filter($tenders, function($t) use ($dept) {
                        return strtolower($t['department'] ?? '') === strtolower($dept);
                    }));
                    $isPreferred = in_array($dept, $userPreferences);
                ?>
                    <div class="department-card <?= $isPreferred ? 'preferred' : '' ?>" onclick="window.location.href='?department=<?= urlencode($dept) ?>'">
                        <h3><?= htmlspecialchars($dept) ?></h3>
                        <div class="tender-count">
                            <i class="fas fa-file-contract"></i> <?= $deptTenderCount ?> tenders available
                        </div>
                        <div class="department-actions">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button class="preference-btn <?= $isPreferred ? 'active' : '' ?>" 
                                        onclick="event.stopPropagation(); togglePreference('<?= htmlspecialchars($dept) ?>', this)"
                                        data-department="<?= htmlspecialchars($dept) ?>">
                                    <i class="fas fa-star"></i> 
                                    <?= $isPreferred ? 'Preferred' : 'Add to Preferences' ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Regular Tender Grid -->
            <div class="tender-grid">
                <?php if (empty($paginatedTenders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No tenders found</h3>
                        <p>Try adjusting your search criteria or browse all available tenders.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($paginatedTenders as $index => $tender): ?>
                        <div class="tender-card">
                            <div class="tender-header">
                                <div class="tender-number"><?= htmlspecialchars($tender['tender_No'] ?? 'N/A') ?></div>
                                <div class="tender-status">
                                    <?php
                                    $closingDate = strtotime($tender['closing_Date']);
                                    $now = time();
                                    if ($closingDate > $now) {
                                        echo '<i class="fas fa-check-circle"></i> Active';
                                    } else {
                                        echo '<i class="fas fa-times-circle"></i> Closed';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="tender-title"><?= htmlspecialchars($tender['description'] ?? 'No description') ?></div>
                            
                            <div class="tender-meta">
                                <div class="meta-item">
                                    <i class="fas fa-building"></i>
                                    <span><?= htmlspecialchars($tender['department'] ?? 'N/A') ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?= htmlspecialchars($tender['province'] ?? 'N/A') ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-tag"></i>
                                    <span><?= htmlspecialchars($tender['category'] ?? 'N/A') ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?= date('d M Y', strtotime($tender['closing_Date'])) ?></span>
                                </div>
                            </div>
                            
                            
        <div class="tender-actions">
            <button class="btn btn-primary" onclick="openModal('modal<?= $index ?>')">
                <i class="fas fa-eye"></i> View Details
            </button>
            <?php if (isset($_SESSION['user_id'])): ?>
            <button class="btn btn-secondary bookmark-btn" data-tender='<?= htmlspecialchars(json_encode($tender), ENT_QUOTES, 'UTF-8') ?>'>
                <i class="fas fa-bookmark"></i> Bookmark
            </button>
            <?php else: ?>
                <button class="btn btn-secondary" onclick="window.location.href='login.php'">
                <i class="fas fa-bookmark"></i> Bookmark
            </button>
        
    <?php endif; ?>
        </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="?<?= http_build_query(array_merge($queryParams, ['page' => max(1, $page - 1)])) ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
                <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>
                <a class="<?= $page >= $totalPages ? 'disabled' : '' ?>" href="?<?= http_build_query(array_merge($queryParams, ['page' => min($totalPages, $page + 1)])) ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modals -->
    <?php foreach ($paginatedTenders as $index => $tender): ?>
        <div class="modal" id="modal<?= $index ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i class="fas fa-file-contract"></i>
                        <?= htmlspecialchars($tender['tender_No'] ?? 'Tender Details') ?>
                    </h3>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
            <button class=" bookmark-btn" data-tender='<?= htmlspecialchars(json_encode($tender), ENT_QUOTES, 'UTF-8') ?>'>
                <i class="fas fa-bookmark"></i> Bookmark
            </button>
            <?php else: ?>
                <button class="btn btn-secondary" onclick="window.location.href='login.php'">
                <i class="fas fa-bookmark"></i> Bookmark
            </button>
            <?php endif; ?>
                        <button class="close-btn" onclick="closeModal('modal<?= $index ?>')">&times;</button>
                    </div>
                </div>
                <div class="modal-body">
                    <table class="tender-details-table">
                        <tr><th><i class="fas fa-info-circle"></i> Description</th><td><?= htmlspecialchars($tender['description'] ?? 'N/A') ?></td></tr>
                        <tr><th><i class="fas fa-tag"></i> Category</th><td><?= htmlspecialchars($tender['category'] ?? 'N/A') ?></td></tr>
                        <tr><th><i class="fas fa-cog"></i> Type</th><td><?= htmlspecialchars($tender['type'] ?? 'N/A') ?></td></tr>
                        <tr><th><i class="fas fa-building"></i> Organ of State</th><td><?= htmlspecialchars($tender['organ_of_State'] ?? 'N/A') ?></td></tr>
                        <tr><th><i class="fas fa-info"></i> Status</th><td><?= htmlspecialchars($tender['status'] ?? 'N/A') ?></td></tr>
                        <tr><th><i class="fas fa-calendar-times"></i> Closing Date</th><td><?= date('Y-m-d H:i', strtotime($tender['closing_Date'])) ?></td></tr>
                        <tr><th><i class="fas fa-calendar-plus"></i> Published</th><td><?= date('Y-m-d', strtotime($tender['date_Published'])) ?></td></tr>
                        <tr><th><i class="fas fa-university"></i> Department</th><td><?= htmlspecialchars($tender['department'] ?? 'N/A') ?></td></tr>
                        <tr><th><i class="fas fa-map-marker-alt"></i> Province</th><td><?= htmlspecialchars($tender['province'] ?? 'N/A') ?></td></tr>
                        <tr><th><i class="fas fa-user"></i> Contact Person</th><td><?= htmlspecialchars($tender['contactPerson'] ?? 'N/A') ?></td></tr>
                        <tr><th><i class="fas fa-envelope"></i> Email</th><td><?= htmlspecialchars($tender['email'] ?? 'N/A') ?></td></tr>
                        <tr><th><i class="fas fa-phone"></i> Telephone</th><td><?= htmlspecialchars($tender['telephone'] ?? 'N/A') ?></td></tr>
                        <tr><th><i class="fas fa-fax"></i> Fax</th><td><?= htmlspecialchars($tender['fax'] ?? 'N/A') ?></td></tr>
                        <tr><th><i class="fas fa-map"></i> Address</th><td><?= htmlspecialchars($tender['streetname'] ?? '') ?>, <?= htmlspecialchars($tender['surburb'] ?? '') ?>, <?= htmlspecialchars($tender['town'] ?? '') ?>, <?= htmlspecialchars($tender['code'] ?? '') ?></td></tr>
                        <tr><th><i class="fas fa-list"></i> Conditions</th><td><?= htmlspecialchars($tender['conditions'] ?? 'N/A') ?></td></tr>
                        <tr><th><i class="fas fa-users"></i> Briefing Session</th><td><?= ($tender['briefingSession'] ?? false) ? 'Yes' : 'No' ?></td></tr>
                        <tr><th><i class="fas fa-exclamation-triangle"></i> Compulsory</th><td><?= ($tender['briefingCompulsory'] ?? false) ? 'Yes' : 'No' ?></td></tr>
                        <tr><th><i class="fas fa-map-pin"></i> Briefing Venue</th><td><?= htmlspecialchars($tender['briefingVenue'] ?? 'N/A') ?></td></tr>
                        <tr><th><i class="fas fa-clock"></i> Briefing Date and Time</th><td><?= !empty($tender['compulsory_briefing_session']) ? date('Y-m-d H:i', strtotime($tender['compulsory_briefing_session'])) : 'N/A' ?></td></tr>
                        <tr><th><i class="fas fa-file"></i> Documents</th>
                <td>
                    <?php if (!empty($tender['supportDocument']) && is_array($tender['supportDocument'])): ?>
                        <ul>
                        <?php foreach ($tender['supportDocument'] as $doc):
                            $blobName = $doc['supportDocumentID'] ?? '';
                            $fileName = $doc['fileName'] ?? 'Document.pdf';
                            $extension = $doc['extension'] ?? '';
                            $docUrl = "https://www.etenders.gov.za/home/Download?blobName=" . urlencode($blobName) . ".pdf&downloadedFileName=" . urlencode($fileName);
                        ?>
                            <li><a href="<?= htmlspecialchars($docUrl) ?>" target="_blank"><?= htmlspecialchars($fileName) ?> (<?= strtoupper(ltrim($extension, '.')) ?>)</a></li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        None listed.
                    <?php endif; ?>
                </td>
            </tr>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Notification -->
    <div class="notification" id="notification">
        <span id="notificationMessage"></span>
    </div>

    <script>

        // Department preference functionality
        function togglePreference(department, button) {
            fetch('update_preferences.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_department',
                    department: department
                })
            })

            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const isActive = button.classList.contains('active');
                    if (isActive) {
                        button.classList.remove('active');
                        button.innerHTML = '<i class="fas fa-star"></i> Add to Preferences';
                        button.closest('.department-card').classList.remove('preferred');
                        showNotification('Department removed from preferences', 'success');

                    } else {

                        button.classList.add('active');
                        button.innerHTML = '<i class="fas fa-star"></i> Preferred';
                        button.closest('.department-card').classList.add('preferred');
                        showNotification('Department added to preferences', 'success');

                    }

                } else {

                    showNotification(data.message || 'Failed to update preferences', 'error');

                }

            })

            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while updating preferences', 'error');
            });
        }
        // Modal functionality

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        // Close modal when clicking outside

        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        }
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (modal.style.display === 'block') {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                });
            }
        });

        // Notification functionality
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            const messageElement = document.getElementById('notificationMessage');

            messageElement.textContent = message;
            notification.className = `notification ${type} show`;

            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // Bookmark functionality
        document.addEventListener('DOMContentLoaded', function() {
            const bookmarkButtons = document.querySelectorAll('.bookmark-btn');

            bookmarkButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tenderData = JSON.parse(this.getAttribute('data-tender'));

                    // Send AJAX request to save bookmark
                    fetch('save_bookmark.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(tenderData)
                    })

                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Tender bookmarked successfully!', 'success');
                            this.innerHTML = '<i class="fas fa-check"></i> Bookmarked';
                            this.disabled = true;
                            this.style.opacity = '0.6';

                        } else {
                            showNotification(data.message || 'Failed to bookmark tender', 'error');
                        }
                    })

                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred while bookmarking', 'error');
                    });
                });
            });
        });

        // Search form enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.querySelector('.search-form');
            const searchInputs = searchForm.querySelectorAll('input');

            // Add enter key support for all inputs

            searchInputs.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        searchForm.querySelector('.search-btn').click();
                    }
                });
            }); 
        });

        // Smooth scrolling for pagination

        document.addEventListener('DOMContentLoaded', function() {
            const paginationLinks = document.querySelectorAll('.pagination a:not(.disabled)');

            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Scroll to top of container smoothly
                    document.querySelector('.container').scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });

        // Filter chips (for active filters display)

        function createFilterChips() {
            const params = new URLSearchParams(window.location.search);
            const chipContainer = document.createElement('div');
            chipContainer.className = 'filter-chips';

            chipContainer.style.cssText = `
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-bottom: 1rem;
                padding: 0 2rem;

            `;

            const filterParams = ['keyword', 'department', 'category', 'province'];
            let hasFilters = false;

            filterParams.forEach(param => {
                const value = params.get(param);
                if (value) {
                    hasFilters = true;
                    const chip = document.createElement('div');
                    chip.className = 'filter-chip';
                    chip.style.cssText = `

                        background-color: #25559D;
                        color: white;
                        padding: 0.25rem 0.75rem;
                        border-radius: 20px;
                        font-size: 14px;
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;

                    `;
                    chip.innerHTML = `

                        <span>${param}: ${value}</span>

                        <button onclick="removeFilter('${param}')" style="
                            background: none;
                            border: none;
                            color: white;
                            cursor: pointer;
                            font-size: 16px;
                            padding: 0;
                            line-height: 1;
                        ">&times;</button>

                    `;     

                    chipContainer.appendChild(chip);
               }
            });

            if (hasFilters) {
                const searchSection = document.querySelector('.search-section');
                searchSection.appendChild(chipContainer);
            }
        }

        function removeFilter(filterName) {
            const params = new URLSearchParams(window.location.search);
            params.delete(filterName);
            window.location.search = params.toString();
        }

        // Initialize filter chips on page load
        document.addEventListener('DOMContentLoaded', createFilterChips);

        // Auto-refresh functionality (optional)
        let autoRefreshInterval;

        function startAutoRefresh() {
            autoRefreshInterval = setInterval(() => {

                // Check if there are new tenders available

                fetch('check_updates.php')

                    .then(response => response.json())

                    .then(data => {

                        if (data.hasUpdates) {
                            showNotification('New tenders available! Refresh to view.', 'info');
                        }
                    })
                    .catch(error => console.error('Auto-refresh error:', error));
            }, 300000); // Check every 5 minutes

        }

        function stopAutoRefresh() {

            if (autoRefreshInterval) {

                clearInterval(autoRefreshInterval);
            }
        }
        // Start auto-refresh on page load
        document.addEventListener('DOMContentLoaded', startAutoRefresh);

        // Stop auto-refresh when page is not visible

        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopAutoRefresh();
            } else {
                startAutoRefresh();
            }
        });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>