<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>GeoTrees Community Platform</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <!-- MarkerCluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />
    
    <link rel="stylesheet" href="main.css" />
</head>
<body>

<header>
    <div class="nav-container container" role="banner">
        <div class="logo" tabindex="0" aria-label="GeoTress Logo">GeoTrees</div>
        <nav aria-label="Primary navigation">
            <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="#">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</a></li>
                    <li><a href="?logout=true">Logout</a></li>
                <?php else: ?>
                    <li><a href="#login-register">Login / Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<?php if (isset($_SESSION['user_id'])): ?>
    <section id="map-section" class="container" aria-label="Map section">
        <h2>Our Community Map</h2>
        <div id="map" style="width:100%; height:600px; border-radius: 0.75rem; border: 1.5px solid #6ee7b7;"></div>
    </section>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <!-- MarkerCluster JS -->
    <script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        console.log("Initializing Leaflet map with clustering...");

        const trees = <?php echo json_encode($trees ?? []); ?>;
        console.log("Loaded trees:", trees);

        const map = L.map('map').setView([1.559694, 103.635519], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        const treeIcon = L.icon({
            iconUrl: 'http://localhost/geotress2/uploads/pokok.png',
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });

        const markerCluster = L.markerClusterGroup();

        if (Array.isArray(trees)) {
            trees.forEach(tree => {
                const lat = parseFloat(tree.latitude);
                const lng = parseFloat(tree.longitude);

                if (!isNaN(lat) && !isNaN(lng)) {
                    const marker = L.marker([lat, lng], { icon: treeIcon });
                    const popupContent = `
                        <div>
                            <strong>${tree.name || 'Tree'}</strong><br>
                            Tree ID: ${tree.tree_id}<br>
                            <a href="tree_detail.php?tree_id=${tree.tree_id}">View & comment</a>
                        </div>
                    `;
                    marker.bindPopup(popupContent);
                    markerCluster.addLayer(marker);
                }
            });

            map.addLayer(markerCluster);
        } else {
            console.warn("Tree data is not an array:", trees);
        }
    });
    </script>

<?php else: ?>
    <section class="hero container" aria-label="Hero section">
        <h1>Connect, Learn, and Support with GeoTrees</h1>
        <p>Our community platform empowers you to share insights, solve challenges, and maximize your GeoTrees experience.</p>
        <div class="btn-group" role="group" aria-label="Hero action buttons">
            <button class="btn btn-primary" type="button" onclick="window.location.href='#login-register'">Get Started</button>
            
        </div>
    </section>

    <main class="container" role="main" tabindex="-1">
        <section class="auth-forms-section" id="login-register">
            <h2>Join the GeoTress Community</h2>
            <?php if (!empty($feedback_message)): ?>
                <div class="auth-form message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($feedback_message); ?>
                </div>
            <?php endif; ?>

            <div style="display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap;">
                <div class="auth-form">
                    <h2>Login</h2>
                    <form action="index.php" method="POST">
                        <label for="login_identifier">Username or Email:</label>
                        <input type="text" id="login_identifier" name="login_identifier" required />

                        <label for="login_password">Password:</label>
                        <input type="password" id="login_password" name="login_password" required />

                        <button type="submit" name="login_submit">Login</button>
                        <p class="text-center">Don't have an account? <a href="#register-form">Register here</a></p>
                    </form>
                </div>

                <div class="auth-form" id="register-form">
                    <h2>Register</h2>
                    <form action="index.php" method="POST">
                        <label for="register_username">Username:</label>
                        <input type="text" id="register_username" name="register_username" required />

                        <label for="register_email">Email:</label>
                        <input type="email" id="register_email" name="register_email" required />

                        <label for="register_password">Password:</label>
                        <input type="password" id="register_password" name="register_password" required />

                        <button type="submit" name="register_submit">Register</button>
                        <p class="text-center">Already have an account? <a href="#login-form">Login here</a></p>
                    </form>
                </div>
            </div>
        </section>
    </main>
<?php endif; ?>
</body>
</html>
