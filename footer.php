<div class="scripts">
    <?php wp_footer(); ?>
    <?php if (get_post_type() == 'tereny' && (is_single() || is_singular())): ?>
        <script>
            if (!document.querySelector('script[src*="tereny/single/script.js"]')) {
                var script = document.createElement('script');
                script.src = '<?php echo get_stylesheet_directory_uri(); ?>/page-templates/tereny/single/script.js?v=<?php echo time(); ?>';
                script.async = false;
                document.body.appendChild(script);
            }
        </script>
    <?php endif; ?>
</div>

</body>

</html>