<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>

<footer class="dsm-site-footer">
    <div class="dsm-container">
        <p>
            &copy;
            <?php echo esc_html(gmdate('Y')); ?>
            <?php bloginfo('name'); ?>
        </p>
    </div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
