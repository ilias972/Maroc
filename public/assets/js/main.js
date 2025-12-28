/**
 * MAROC INFLATION - JavaScript Principal
 */

// Configuration globale Chart.js
if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    Chart.defaults.color = '#495057';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;

    // Options par défaut pour tous les graphiques
    Chart.defaults.responsive = true;
    Chart.defaults.maintainAspectRatio = true;
}

// ===================================
// UTILITAIRES
// ===================================

const Utils = {
    /**
     * Formater un nombre en dirham marocain
     */
    formatDirham: function(montant) {
        return new Intl.NumberFormat('fr-MA', {
            style: 'currency',
            currency: 'MAD'
        }).format(montant);
    },

    /**
     * Formater un pourcentage
     */
    formatPourcentage: function(valeur) {
        const signe = valeur >= 0 ? '+' : '';
        return signe + valeur.toFixed(2) + '%';
    },

    /**
     * Copier dans le presse-papier
     */
    copyToClipboard: function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                this.showToast('Copié dans le presse-papier !', 'success');
            }).catch(err => {
                console.error('Erreur copie:', err);
            });
        }
    },

    /**
     * Afficher un toast Bootstrap
     */
    showToast: function(message, type = 'info') {
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(container);
        }

        container.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = container.lastElementChild;
        const toast = new bootstrap.Toast(toastElement);
        toast.show();

        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    },

    /**
     * Charger des données depuis l'API
     */
    fetchAPI: async function(endpoint, params = {}) {
        try {
            const url = new URL(endpoint, window.location.origin);
            Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Erreur API:', error);
            this.showToast('Erreur de chargement des données', 'danger');
            return null;
        }
    }
};

// ===================================
// VALIDATION DE FORMULAIRES
// ===================================

document.addEventListener('DOMContentLoaded', function() {
    // Validation Bootstrap
    const forms = document.querySelectorAll('.needs-validation');

    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Popovers Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Smooth scroll pour les ancres
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href !== '#!') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // Animation au scroll (Intersection Observer)
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.card, .alert').forEach(el => {
        observer.observe(el);
    });
});

// ===================================
// FONCTIONS POUR LE CALCULATEUR
// ===================================

const Calculator = {
    /**
     * Valider les dates du calculateur
     */
    validate: function() {
        const form = document.getElementById('calculatorForm');
        if (!form) return true;

        const montant = parseFloat(form.montant.value);
        const anneeDepart = parseInt(form.annee_depart.value);
        const anneeArrivee = parseInt(form.annee_arrivee.value);
        const moisDepart = parseInt(form.mois_depart.value);
        const moisArrivee = parseInt(form.mois_arrivee.value);

        // Vérifier que la date de départ est antérieure
        const dateDepart = new Date(anneeDepart, moisDepart - 1);
        const dateArrivee = new Date(anneeArrivee, moisArrivee - 1);

        if (dateDepart >= dateArrivee) {
            Utils.showToast('La date de départ doit être antérieure à la date d\'arrivée', 'warning');
            return false;
        }

        if (montant <= 0) {
            Utils.showToast('Le montant doit être supérieur à 0', 'warning');
            return false;
        }

        return true;
    },

    /**
     * Calculer via l'API
     */
    calculate: async function() {
        const form = document.getElementById('calculatorForm');
        if (!form || !this.validate()) return;

        const params = {
            montant: form.montant.value,
            annee_depart: form.annee_depart.value,
            mois_depart: form.mois_depart.value,
            annee_arrivee: form.annee_arrivee.value,
            mois_arrivee: form.mois_arrivee.value
        };

        const data = await Utils.fetchAPI('/api/calculate.php', params);

        if (data && data.success) {
            this.displayResult(data.resultat);
        }
    },

    /**
     * Afficher le résultat
     */
    displayResult: function(resultat) {
        const resultDiv = document.getElementById('calculatorResult');
        if (!resultDiv) return;

        resultDiv.innerHTML = `
            <div class="alert alert-success fade-in">
                <h4 class="alert-heading">
                    <i class="fas fa-check-circle"></i> Résultat du calcul
                </h4>
                <hr>
                <div class="row mt-4">
                    <div class="col-md-6 text-center">
                        <p class="text-muted mb-1">Montant initial (${resultat.periode_depart})</p>
                        <h2 class="fw-bold text-success">${Utils.formatDirham(resultat.montant_initial)}</h2>
                    </div>
                    <div class="col-md-6 text-center">
                        <p class="text-muted mb-1">Équivalent en (${resultat.periode_arrivee})</p>
                        <h2 class="fw-bold text-danger">${Utils.formatDirham(resultat.montant_equivalent)}</h2>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Inflation cumulée :</strong></p>
                        <h4 class="text-danger">${Utils.formatPourcentage(resultat.inflation_cumulee)}</h4>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Perte de pouvoir d'achat :</strong></p>
                        <h4 class="text-warning">${Utils.formatDirham(resultat.perte_pouvoir_achat)}</h4>
                    </div>
                </div>
            </div>
        `;

        resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
};

// ===================================
// EXPORT DE DONNÉES
// ===================================

/**
 * Exporter un tableau en CSV
 */
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    for (let row of rows) {
        let rowData = [];
        const cols = row.querySelectorAll('td, th');

        for (let col of cols) {
            rowData.push('"' + col.innerText.replace(/"/g, '""') + '"');
        }

        csv.push(rowData.join(','));
    }

    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');

    if (navigator.msSaveBlob) {
        navigator.msSaveBlob(blob, filename);
    } else {
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    Utils.showToast('Fichier CSV téléchargé avec succès !', 'success');
}

// ===================================
// PARTAGE & IMPRESSION
// ===================================

/**
 * Partager la page sur les réseaux sociaux
 */
function partagerPage() {
    const url = window.location.href;
    const texte = 'Découvrez l\'évolution de l\'inflation au Maroc sur Maroc-Inflation.com';

    if (navigator.share) {
        navigator.share({
            title: 'Maroc Inflation',
            text: texte,
            url: url
        }).catch(err => console.log('Partage annulé'));
    } else {
        Utils.copyToClipboard(url);
    }
}

/**
 * Imprimer la page
 */
function imprimerPage() {
    window.print();
}

// ===================================
// RECHERCHE DANS TABLEAU
// ===================================

/**
 * Rechercher dans un tableau
 */
function rechercherDansTableau(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);

    if (!input || !table) return;

    const filter = input.value.toUpperCase();
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let found = false;

        for (let cell of cells) {
            if (cell.textContent.toUpperCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }

        row.style.display = found ? '' : 'none';
    }
}

// ===================================
// CONSOLE LOG
// ===================================

console.log('%c 🇲🇦 Maroc Inflation ', 'background: #C1272D; color: white; font-size: 20px; padding: 10px;');
console.log('Site développé pour suivre l\'inflation au Maroc');
console.log('Données officielles du HCP');