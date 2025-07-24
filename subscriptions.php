<?php
session_start();
require_once 'db_connect.php';
require_once 'functions.php';

// Check if user is logged in for certain features
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Mock user subscription data (replace with actual database queries)
$currentPlan = null;
$subscriptionStatus = 'none';
$subscriptionExpiry = null;

if ($isLoggedIn) {
    // Query user's current subscription
    // $stmt = $pdo->prepare("SELECT * FROM user_subscriptions WHERE user_id = ? AND status = 'active'");
    // $stmt->execute([$userId]);
    // $subscription = $stmt->fetch();
    
    $currentPlan = 'trial'; // renamed for clarity
    $subscriptionStatus = 'active';
    $subscriptionStart = strtotime("-6 days"); // mock: trial started 6 days ago
    $subscriptionExpiry = date('Y-m-d', strtotime("+1 day", $subscriptionStart)); // 7 days total
}

// Subscription plans data
$plans = [
    'trial' => [
        'name' => '7-Day Free Trial',
        'price' => 0,
        'period' => '7 days',
        'features' => [
            'Up to 10 tender alerts',
            'Basic search functionality',
            'Email notifications',
            'Save up to 5 tenders',
            'Community support'
        ],
        'color' => '#6C757D',
        'recommended' => false
    ],
    'basic' => [
        'name' => 'Basic Plan',
        'price' => 299,
        'period' => 'month',
        'features' => [
            'Up to 50 tender alerts per month',
            'Basic search filters',
            'Email notifications',
            'Save up to 20 tenders',
            'Standard support'
        ],
        'color' => '#25559D',
        'recommended' => false
    ],
    'premium' => [
        'name' => 'Premium Plan',
        'price' => 599,
        'period' => 'month',
        'features' => [
            'Unlimited tender alerts',
            'Advanced search filters',
            'Real-time notifications',
            'Unlimited saved tenders',
            'Priority support',
            'Custom keyword alerts',
            'Export to PDF/Excel',
            'Weekly summary reports'
        ],
        'color' => '#FE8900',
        'recommended' => true
    ],
    'enterprise' => [
        'name' => 'Enterprise Plan',
        'price' => 1299,
        'period' => 'month',
        'features' => [
            'Everything in Premium',
            'Multi-user access (up to 10 users)',
            'Advanced analytics dashboard',
            'Custom integrations',
            'Dedicated account manager',
            'API access',
            'Custom reporting',
            'White-label options'
        ],
        'color' => '#259B45',
        'recommended' => false
    ]
];

// Handle subscription actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn) {
    $action = $_POST['action'] ?? '';
    $planId = $_POST['plan'] ?? '';
    
    if ($action === 'subscribe' && isset($plans[$planId])) {
        // Process subscription (integrate with payment gateway)
        // For demo purposes, we'll just show success message
        if ($planId === 'free') {
            $subscriptionMessage = "Welcome to TenderAlert! You're now on the Free plan.";
        } else {
            $subscriptionMessage = "Subscription to {$plans[$planId]['name']} initiated successfully!";
        }
        $messageType = 'success';
    } elseif ($action === 'cancel') {
        // Cancel subscription (downgrade to free)
        $subscriptionMessage = "Subscription cancelled. You've been moved to the Free plan.";
        $messageType = 'success';
    } elseif ($action === 'upgrade') {
        // Upgrade subscription
        $subscriptionMessage = "Subscription upgraded successfully!";
        $messageType = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Choose the perfect subscription plan for your tender monitoring needs. Start free or upgrade for premium features.">
    <title>Subscription Plans | TenderAlert</title>
    <!-- Open Graph tags -->
    <meta property="og:title" content="Subscription Plans | TenderAlert">
    <meta property="og:description" content="Choose the perfect subscription plan for your tender monitoring needs">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://yourwebsite.com/subscriptions.php">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional styles for subscription page */
        .subscription-header {
            background: linear-gradient(135deg, rgba(37, 85, 157, 0.95), rgba(26, 59, 110, 0.95)), url('Tender_Application_Image.jpg') no-repeat center/cover;
            min-height: 400px;
        }

        .subscription-header .header-text h1 {
            font-size: 42px;
        }

.plans-grid {
    display: flex;
    flex-wrap: nowrap;
    gap: 2rem;  
    padding-bottom: 1rem;
}
.plan-card {
    flex: 0 0 300px;
}

        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .plan-card.recommended {
            border: 3px solid #FE8900;
            transform: scale(1.05);
        }

        .plan-card.recommended::before {
            content: 'RECOMMENDED';
            position: absolute;
            top: 20px;
            right: -30px;
            background: #FE8900;
            color: white;
            padding: 5px 40px;
            font-size: 12px;
            font-weight: bold;
            transform: rotate(45deg);
            z-index: 10;
        }

        .plan-header {
            padding: 2rem;
            text-align: center;
            color: white;
            position: relative;
        }

        .plan-header.free {
            background: linear-gradient(135deg, #6C757D, #5a6268);
        }

        .plan-header.basic {
            background: linear-gradient(135deg, #25559D, #1c4279);
        }

        .plan-header.premium {
            background: linear-gradient(135deg, #FE8900, #e67a00);
        }

        .plan-header.enterprise {
            background: linear-gradient(135deg, #259B45, #1e7738);
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .plan-price {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .plan-price.free {
            font-size: 2.5rem;
        }

        .plan-period {
            opacity: 0.9;
            font-size: 1rem;
        }

        .plan-body {
            padding: 2rem;
        }

        .plan-features {
            list-style: none;
            margin-bottom: 2rem;
        }

        .plan-features li {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .plan-features li:last-child {
            border-bottom: none;
        }

        .plan-features li i {
            color: #259B45;
            font-size: 1.1rem;
        }

        .plan-button {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .plan-button.free {
            background: #5a6268;
            color: white;
        }

        .plan-button.basic {
            background: #25559D;
            color: white;
        }

        .plan-button.premium {
            background: #FE8900;
            color: white;
        }

        .plan-button.enterprise {
            background: #259B45;
            color: white;
        }

        .plan-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .plan-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .current-subscription {
            background: linear-gradient(135deg, #25559D, #1c4279);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .current-subscription.free {
            background: linear-gradient(135deg, #6C757D, #5a6268);
        }

        .current-subscription h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .subscription-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .faq-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .faq-item {
            border-bottom: 1px solid #e5e5e5;
            padding: 1.5rem 0;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-question {
            font-weight: 600;
            color: #25559D;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .faq-answer {
            margin-top: 1rem;
            color: #666;
            line-height: 1.6;
            display: none;
        }

        .faq-answer.show {
            display: block;
        }

        .features-comparison {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .comparison-table th,
        .comparison-table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e5e5;
            text-align: center;
        }

        .comparison-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #25559D;
        }

        .comparison-table td.feature-name {
            text-align: left;
            font-weight: 500;
        }

        .comparison-table .check {
            color: #259B45;
            font-size: 1.2rem;
        }

        .comparison-table .cross {
            color: #dc3545;
            font-size: 1.2rem;
        }

        .free-trial-banner {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 2rem;
        }

        .free-trial-banner h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.3rem;
        }

        .free-trial-banner p {
            margin: 0;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .plans-grid {
                grid-template-columns: 1fr;
            }
            
            .plan-card.recommended {
                transform: none;
            }
            
            .subscription-actions {
                flex-direction: column;
            }
            
            .comparison-table {
                font-size: 14px;
            }
            
            .comparison-table th,
            .comparison-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="subscription-header">
        <div class="top-nav">
            <nav class="centered-nav">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <?php if ($isLoggedIn): ?>
                <a href="saved_tender.php"><i class="fas fa-bookmark"></i> Saved Tenders</a>
                <?php endif; ?>
                <a href="subscriptions.php" class="active"><i class="fas fa-credit-card"></i> Subscriptions</a>
                <?php if ($isLoggedIn): ?>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> SignIn</a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="header-text">
            <h1>Choose Your Plan</h1>
            <p>Start free and upgrade anytime. Unlock premium features and stay ahead of the competition.</p>
        </div>
    </header>

    <div class="container">
        <?php if (isset($subscriptionMessage)): ?>
            <div class="notification <?= $messageType ?> show">
                <?= htmlspecialchars($subscriptionMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (!$isLoggedIn): ?>
            <div class="free-trial-banner">
            <h3><i class="fas fa-gift"></i> Start Your 7-Day Free Trial</h3>
                <p>No credit card required. Get instant access to tender alerts and upgrade anytime.</p>
            </div>
        <?php endif; ?>

        <?php if ($isLoggedIn && $currentPlan): ?>
            <div class="current-subscription <?= $currentPlan ?>">
                <h3>
                    <i class="fas fa-<?= $currentPlan === 'free' ? 'user' : 'crown' ?>"></i> 
                    Current Subscription
                </h3>
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h4><?= htmlspecialchars($plans[$currentPlan]['name']) ?></h4>
                        <p>Status: <strong><?= ucfirst($subscriptionStatus) ?></strong></p>
                        <?php if ($subscriptionExpiry): ?>
                            <p>Expires: <strong><?= date('d M Y', strtotime($subscriptionExpiry)) ?></strong></p>
                        <?php endif; ?>
                    </div>
                    <div class="subscription-actions">
                        <?php if ($currentPlan !== 'enterprise'): ?>
                            <button class="btn btn-secondary" onclick="showUpgradeModal()">
                                <i class="fas fa-arrow-up"></i> Upgrade
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-primary" onclick="showManageModal()">
                            <i class="fas fa-cog"></i> Manage
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="plans-grid">
            <?php foreach ($plans as $planId => $plan): ?>
                <div class="plan-card <?= $plan['recommended'] ? 'recommended' : '' ?>">
                    <div class="plan-header <?= $planId ?>">
                        <div class="plan-name"><?= htmlspecialchars($plan['name']) ?></div>
                        <div class="plan-price <?= $planId ?>">
                            <?php if ($planId === 'free'): ?>
                                FREE
                            <?php else: ?>
                                R<?= number_format($plan['price']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="plan-period">
                            <?php if ($planId === 'free'): ?>
                                <?= htmlspecialchars($plan['period']) ?>
                            <?php else: ?>
                                per <?= htmlspecialchars($plan['period']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="plan-body">
                        <ul class="plan-features">
                            <?php foreach ($plan['features'] as $feature): ?>
                                <li>
                                    <i class="fas fa-check"></i>
                                    <?= htmlspecialchars($feature) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($isLoggedIn): ?>
                            <?php if ($currentPlan === $planId): ?>
                                <button class="plan-button <?= $planId ?>" disabled>
                                    <i class="fas fa-check"></i> Current Plan
                                </button>
                            <?php else: ?>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="subscribe">
                                    <input type="hidden" name="plan" value="<?= $planId ?>">
                                    <button type="submit" class="plan-button <?= $planId ?>">
                                        <?php if ($planId === 'free'): ?>
                                            <i class="fas fa-user"></i> Get Started Free
                                        <?php else: ?>
                                            <i class="fas fa-credit-card"></i> Subscribe Now
                                        <?php endif; ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="plan-button <?= $planId ?>" onclick="window.location.href='login.php'">
                                <i class="fas fa-sign-in-alt"></i> Sign In to Get Started
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="features-comparison">
            <h2 style="text-align: center; color: #25559D; margin-bottom: 1rem;">
                <i class="fas fa-table"></i> Feature Comparison
            </h2>
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>Free</th>
                        <th>Basic</th>
                        <th>Premium</th>
                        <th>Enterprise</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="feature-name">Tender Alerts</td>
                        <td>5/month</td>
                        <td>50/month</td>
                        <td>Unlimited</td>
                        <td>Unlimited</td>
                    </tr>
                    <tr>
                        <td class="feature-name">Saved Tenders</td>
                        <td>3 tenders</td>
                        <td>20 tenders</td>
                        <td>Unlimited</td>
                        <td>Unlimited</td>
                    </tr>
                    <tr>
                        <td class="feature-name">Advanced Search</td>
                        <td><i class="fas fa-times cross"></i></td>
                        <td><i class="fas fa-times cross"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Real-time Notifications</td>
                        <td><i class="fas fa-times cross"></i></td>
                        <td><i class="fas fa-times cross"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Export to PDF/Excel</td>
                        <td><i class="fas fa-times cross"></i></td>
                        <td><i class="fas fa-times cross"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Multi-user Access</td>
                        <td><i class="fas fa-times cross"></i></td>
                        <td><i class="fas fa-times cross"></i></td>
                        <td><i class="fas fa-times cross"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td class="feature-name">API Access</td>
                        <td><i class="fas fa-times cross"></i></td>
                        <td><i class="fas fa-times cross"></i></td>
                        <td><i class="fas fa-times cross"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Dedicated Support</td>
                        <td><i class="fas fa-times cross"></i></td>
                        <td><i class="fas fa-times cross"></i></td>
                        <td><i class="fas fa-times cross"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="faq-section">
            <h2 style="text-align: center; color: #25559D; margin-bottom: 2rem;">
                <i class="fas fa-question-circle"></i> Frequently Asked Questions
            </h2>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>Is the free plan really free?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Yes! The free plan is completely free with no hidden costs. You get 5 tender alerts per month, can save 3 tenders, and have access to our basic features forever.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>How do I cancel my subscription?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    You can cancel your subscription at any time from your account settings. Your subscription will remain active until the end of your current billing period, after which you'll be moved to the free plan.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>Can I upgrade or downgrade my plan?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Yes, you can upgrade or downgrade your plan at any time. Changes take effect immediately, and billing adjustments will be made accordingly.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>What payment methods do you accept?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    We accept all major credit cards, PayPal, and bank transfers. All payments are processed securely using industry-standard encryption.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>Is there a free trial available?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Our free plan serves as a permanent trial! You can use it forever, and if you need more features, you can upgrade to any paid plan at any time.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>Do you offer refunds?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    We offer a 30-day money-back guarantee on all paid plans. If you're not satisfied with our service, contact us within 30 days for a full refund.
                </div>
            </div>
        </div>
    </div>

    <!-- Upgrade Modal -->
    <div class="modal" id="upgradeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-arrow-up"></i> Upgrade Your Plan
                </h3>
                <button class="close-btn" onclick="closeModal('upgradeModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Choose a plan to upgrade to:</p>
                <div style="display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap;">
                    <?php foreach ($plans as $planId => $plan): ?>
                        <?php if ($planId !== $currentPlan && $planId !== 'free'): ?>
                            <form method="POST" style="flex: 1; min-width: 200px;">
                                <input type="hidden" name="action" value="upgrade">
                                <input type="hidden" name="plan" value="<?= $planId ?>">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    <?= htmlspecialchars($plan['name']) ?> - R<?= number_format($plan['price']) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Manage Modal -->
    <div class="modal" id="manageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-cog"></i> Manage Subscription
                </h3>
                <button class="close-btn" onclick="closeModal('manageModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button class="btn btn-primary" onclick="window.location.href='account.php'">
                        <i class="fas fa-user"></i> Account Settings
                    </button>
                    <?php if ($currentPlan !== 'free'): ?>
                        <button class="btn btn-secondary" onclick="window.location.href='billing.php'">
                            <i class="fas fa-receipt"></i> Billing History
                        </button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="btn" style="background: #dc3545; color: white;" onclick="return confirm('Are you sure you want to cancel your subscription? You will be moved to the free plan.')">
                                <i class="fas fa-times"></i> Cancel Subscription
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // FAQ Toggle
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('i');
            
            if (answer.classList.contains('show')) {
                answer.classList.remove('show');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                answer.classList.add('show');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        }

        // Modal functions
        function showUpgradeModal() {
            document.getElementById('upgradeModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function showManageModal() {
            document.getElementById('manageModal').style.display = 'block';
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

// Notification auto-hide
document.addEventListener('DOMContentLoaded', function() {
    const notifications = document.querySelectorAll('.notification');
    
    notifications.forEach(notification => {
        // Auto-hide after 5 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-20px)';
            
            // Remove from DOM after animation
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
        
        // Add close button functionality
        const closeBtn = notification.querySelector('.close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-20px)';
                
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            });
        }
    });
});

// Additional modal utilities
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
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

// Show notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span class="notification-message">${message}</span>
        <button class="close-btn">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateY(0)';
    }, 100);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-20px)';
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
    
    // Add close button functionality
    const closeBtn = notification.querySelector('.close-btn');
    closeBtn.addEventListener('click', function() {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-20px)';
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    });
}
</script>

</body>
</html>