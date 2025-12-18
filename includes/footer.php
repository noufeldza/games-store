    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3 class="footer-title">
                    <i class="fas fa-gamepad"></i> Games Store
                </h3>
                <p class="footer-text">
                    Votre destination pour les meilleurs jeux vidéo. 
                    Des milliers de titres disponibles à prix imbattables.
                </p>
                <div class="footer-social">
                    <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-discord"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <div class="footer-section">
                <h4 class="footer-subtitle">Navigation</h4>
                <ul class="footer-links">
                    <li><a href="/index.php">Accueil</a></li>
                    <li><a href="/pages/store.php">Boutique</a></li>
                    <li><a href="/pages/store.php?featured=1">Jeux en vedette</a></li>
                    <li><a href="/pages/store.php?discount=1">Promotions</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4 class="footer-subtitle">Catégories</h4>
                <ul class="footer-links">
                    <li><a href="/pages/store.php?category=1">Action</a></li>
                    <li><a href="/pages/store.php?category=2">RPG</a></li>
                    <li><a href="/pages/store.php?category=3">FPS</a></li>
                    <li><a href="/pages/store.php?category=4">Sport</a></li>
                    <li><a href="/pages/store.php?category=5">Stratégie</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4 class="footer-subtitle">Support</h4>
                <ul class="footer-links">
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="#">Conditions d'utilisation</a></li>
                    <li><a href="#">Politique de confidentialité</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Games Store. Tous droits réservés. Projet universitaire.</p>
        </div>
    </footer>

    <!-- Scripts JavaScript -->
    <script src="/js/main.js"></script>
    <?php if (isset($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
        <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
