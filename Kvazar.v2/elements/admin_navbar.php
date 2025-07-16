<?php
// Set default page title if not provided
if (!isset($page_title)) {
    $page_title = 'Admin Panel';
}
?>

<nav class="navbar">
    <div class="navbar-left">
        <button type="button" id="sidebarToggle" class="btn">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <div class="navbar-brand">
        <h4><?php echo htmlspecialchars($page_title); ?></h4>
    </div>
</nav> 