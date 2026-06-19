    </main>
    <footer class="site-footer">
        <div class="container">
            <p>Простой блог - производственная практика 2026</p>
        </div>
    </footer>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/post-gallery.js"></script>
    <?php foreach ($pageScripts ?? [] as $script): ?>
        <script src="<?= e($script) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
