<?php
/**
 * Samara University Academic Performance Evaluation System
 * Management Footer
 */

// Check if this is a direct access
if (!isset($GLOBALS['BASE_PATH'])) {
    require_once 'config.php';
}
?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Custom JS -->
    <script src="<?php echo $base_url; ?>assets/js/theme.js"></script>
    <script src="<?php echo $base_url; ?>assets/js/management.js"></script>

    <!-- Page-specific scripts -->
    <?php if (isset($page_scripts) && is_array($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo $base_url . $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
