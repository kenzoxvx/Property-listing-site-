<div class="realtor-nav">
  <a href="realtor_dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'realtor_dashboard.php' ? 'active' : ''; ?>">
    <i class="fas fa-home"></i> Dashboard
  </a>
  <a href="realtor_properties.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'realtor_properties.php' ? 'active' : ''; ?>">
    <i class="fas fa-building"></i> Properties
  </a>
  <a href="realtor_messages.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'realtor_messages.php' ? 'active' : ''; ?>">
    <i class="fas fa-comments"></i> Messages
  </a>
  <a href="realtor_profile.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'realtor_profile.php' ? 'active' : ''; ?>">
    <i class="fas fa-user"></i> Profile
  </a>
</div>
