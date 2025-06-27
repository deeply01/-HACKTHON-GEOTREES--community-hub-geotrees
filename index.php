<?php
// index.php

// Start the PHP session
session_start();

// Include database connection details
require_once 'config/db.php';

// Include helper functions
require_once 'includes/functions.php';

// Initialize feedback variables for user messages
$feedback_message = '';
$message_type = ''; // Can be 'success' or 'error'

// Include authentication handler (Login, Logout, Registration)
require_once 'handlers/auth_handler.php';

// Include post and comment handler
require_once 'handlers/post_comment_handler.php';

// --- Fetch Posts for Forum Section ---
$posts = [];
$posts_query = "SELECT p.id, p.title, p.content, p.created_at, u.username
                FROM posts p
                JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC";

$posts_result = $conn->query($posts_query);
if ($posts_result) {
    while ($row = $posts_result->fetch_assoc()) {
        $posts[] = $row;
    }
} else {
    error_log("Error fetching posts: " . $conn->error);
}

// --- Fetch Tree Data for Google Maps ---
$trees = []; // Initialize array
$tree_query = "SELECT tree_id, name, latitude, longitude FROM trees WHERE latitude != 0 AND longitude != 0";

$tree_result = $conn->query($tree_query);
if ($tree_result) {
    while ($row = $tree_result->fetch_assoc()) {
        $trees[] = $row;
    }
} else {
    error_log("Error fetching trees: " . $conn->error);
}

// Debugging: Print $trees to check if data is correctly fetched
//echo '<pre>';print_r($trees);echo '</pre>';

// Include the main HTML structure after variables are set
include 'main.php';

// Close the database connection
//$conn->close();
?>
<script>
  window.initMap = function () {
    console.log("Initializing map...");

    const trees = <?php echo json_encode($trees); ?>;
    console.log("Loaded trees:", trees);

    const map = new google.maps.Map(document.getElementById("map"), {
      center: { lat: 1.559694912, lng: 103.635519 },
      zoom: 14
    });

    if (Array.isArray(trees)) {
      trees.forEach(tree => {
        if (tree.latitude && tree.longitude) {
          const marker = new google.maps.Marker({
            position: { lat: parseFloat(tree.latitude), lng: parseFloat(tree.longitude) },
            map: map,
            title: tree.name || "Tree"
          });

          const infoWindow = new google.maps.InfoWindow({
  content: `
    <div>
      <strong>${tree.name}</strong><br>
      Tree ID: ${tree.tree_id}<br>
      <a href="tree_detail.php?tree_id=${encodeURIComponent(tree.tree_id)}">View & comment</a>
    </div>
  `
});

          marker.addListener("click", () => {
            infoWindow.open(map, marker);
          });
        }
      });
    } else {
      console.warn("trees is not an array:", trees);
    }
  };
</script>