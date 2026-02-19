<footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-line text-danger me-2" aria-hidden="true"></i>
                        <?= escapeHTML(SITE_NAME) ?>
                    </h5>
                    <p class="text-white-50">
                        Suivez l'évolution de l'inflation au Maroc depuis <?= escapeHTML(START_YEAR) ?> avec des données officielles du HCP.
                    </p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5 class="mb-3">Liens Utiles</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="https://www.hcp.ma" target="_blank" rel="noopener noreferrer" class="text-white-50 text-decoration-none">
                                <i class="fas fa-external-link-alt me-2" aria-hidden="true"></i> HCP - Haut-Commissariat au Plan
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="https://www.bkam.ma" target="_blank" rel="noopener noreferrer" class="text-white-50 text-decoration-none">
                                <i class="fas fa-external-link-alt me-2" aria-hidden="true"></i> Bank Al-Maghrib
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="https://www.data.gov.ma" target="_blank" rel="noopener noreferrer" class="text-white-50 text-decoration-none">
                                <i class="fas fa-external-link-alt me-2" aria-hidden="true"></i> Open Data Maroc
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5 class="mb-3">Outils</h5>
                    <ul class="list-unstyled">
                        </ul>
                </div>
            </div>
            <hr class="bg-secondary my-3">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="text-white-50 mb-0">
                        © <?= date('Y') ?> <?= escapeHTML(SITE_NAME) ?> - Tous droits réservés
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="text-white-50 text-decoration-none me-3">
                        <i class="fas fa-shield-alt me-1" aria-hidden="true"></i> Confidentialité
                    </a>
                    <a href="#" class="text-white-50 text-decoration-none">
                        <i class="fas fa-envelope me-1" aria-hidden="true"></i> Contact
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>

    <script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>

    <button id="scrollTopBtn" class="btn btn-danger rounded-circle position-fixed bottom-0 end-0 m-4"
            aria-label="Remonter en haut de la page"
            style="display: none; width: 50px; height: 50px; z-index: 1000;">
        <i class="fas fa-arrow-up" aria-hidden="true"></i>
    </button>

    <script>
        // Scroll to top functionality
        document.addEventListener('DOMContentLoaded', function() {
            const scrollBtn = document.getElementById('scrollTopBtn');

            if(scrollBtn) {
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        scrollBtn.style.display = 'block';
                    } else {
                        scrollBtn.style.display = 'none';
                    }
                });

                scrollBtn.addEventListener('click', function() {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
        });
    </script>
</body>
</html>
