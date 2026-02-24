<?php
require_once __DIR__ . '/helpers.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="brand">
                <img src="/css/logo.jpg" alt="<?= h(APP_NAME) ?> Logo" class="logo">
                <span><?= h(APP_NAME) ?></span>
            </a>
            <div class="nav-links" id="navLinks">
                <a href="/">Home</a>
                <?php if ($user): ?>
                    <?php if ($user['role'] === 'student'): ?>
                        <a href="/student/dashboard.php">My Doubts</a>
                        <a href="/student/new_doubt.php">Ask Doubt</a>
                    <?php else: ?>
                        <a href="/mentor/dashboard.php">Mentor Panel</a>
                    <?php endif; ?>
                    <a href="/logout.php" class="btn-heyyguru">Logout (<?= h($user['name']) ?>)</a>
                <?php else: ?>
                    <a href="/login.php">Login / Signup</a>
                <?php endif; ?>
                <a href="https://heyyguru.in/courses" class="btn-explore">Explore Courses</a>
            </div>
            <div class="nav-toggle" id="navToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="nav-overlay" id="navOverlay"></div>
        </div>
    </nav>

    <div class="hero">
        <h1>Prashna</h1>
        <p>Your personal doubt-solving companion. Ask questions, get expert mentor guidance.</p>
        <?php if (!$user): ?>
        <div class="hero-buttons">
            <a href="/register.php" class="btn btn-primary">Get Started</a>
            <a href="/login.php" class="btn btn-secondary">Login / Register</a>
        </div>
        <?php endif; ?>
    </div>

    <div class="chat-container">
        <div class="chat-header">
            <div class="chat-header-info">
                <h2>Chat with Prashna</h2>
                <div class="status-indicator">
                    <div class="status-dot"></div>
                    <span>Online</span>
                </div>
            </div>
        </div>
        <div class="chat-messages" id="chatMessages"></div>
        <form class="chat-input-area" id="chatForm">
            <input type="text" id="chatInput" placeholder="Ask your academic doubt here..." autocomplete="off">
            <button type="submit" class="chat-send-btn">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path></svg>
            </button>
        </form>
    </div>

    <div class="container">
        <div class="marketing-section">
            <h2 style="font-size: 2.5rem; font-weight: 900; margin-bottom: 15px;">Transform Your Learning Journey</h2>
            <p style="color: var(--text-light); font-size: 1.2rem;">Join the revolution with HeyyGuru's exclusive programs.</p>
            
            <div class="marketing-grid">
                <!-- AARAMBH Course -->
                <div class="promo-card aarambh">
                    <span class="badge" style="background: #feb2b2; color: #9b2c2c; margin-bottom: 15px;">BEST SELLER</span>
                    <h3 style="font-size: 2rem; font-weight: 850; margin-bottom: 10px;">AARAMBH</h3>
                    <p><strong>Perfect Start for Young Learners</strong></p>
                    <ul>
                        <li>Strong foundation in Maths, English & EVS</li>
                        <li>Fun and interactive live classes</li>
                        <li>Concept clarity from basics</li>
                        <li>Focus on confidence and interest in studies</li>
                        <li>Activity-based learning + worksheets</li>
                        <li>Daily practice for better understanding</li>
                        <li>Personal mentor support</li>
                        <li>Ideal for Class 1 to 5 beginners</li>
                    </ul>
                    <div class="price-tag">₹19</div>
                    <p class="promo-goal">Goal: Build strong basics, confidence, and love for learning.</p>
                    <a href="/register.php" class="btn btn-primary btn-block" style="background: #e53e3e; margin-top: 20px;">Book Now @ ₹19</a>
                </div>

                <!-- Learn India Initiative -->
                <div class="promo-card learn-india">
                    <span class="badge" style="background: #9ae6b4; color: #22543d; margin-bottom: 15px;">INITIATIVE</span>
                    <h3 style="font-size: 2rem; font-weight: 850; margin-bottom: 10px;">Learn India</h3>
                    <p><strong>Real-Life Skills Beyond School</strong></p>
                    <ul>
                        <li>Financial literacy & money management</li>
                        <li>AI awareness & future technology</li>
                        <li>Communication & personality growth</li>
                        <li>Logical thinking & problem solving</li>
                        <li>Career awareness from early stage</li>
                        <li>Entrepreneurship mindset</li>
                        <li>Real-world projects & practical learning</li>
                        <li>Digital safety & smart internet use</li>
                    </ul>
                    <div class="price-tag">₹599</div>
                    <p class="promo-goal">Goal: Prepare students for real life, future careers, and smart decision making.</p>
                    <a href="/register.php" class="btn btn-primary btn-block" style="background: #2f855a; margin-top: 20px;">Join Movement @ ₹599</a>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$user): ?>
    <div class="container">
        <div class="auth-redirect-box">
            <p>Ready to get expert answers? Login to submit your doubt!</p>
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="/login.php" class="btn-auth-redirect">Login / Register</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>window.CSRF_TOKEN = "<?= csrf_token() ?>";</script>
    <script src="/js/chat.js"></script>
</body>
</html>
