(function(){
    const state = {
        panier: []
    };

    function miseAJourCompteur() {
        const nombreArticles = state.panier.reduce((t, it) => t + it.quantite, 0);
        const badges = document.querySelectorAll('.shop__number');
        badges.forEach(badge => {
            if (badge) badge.textContent = nombreArticles;
        });
    }

    function getImagePath(image) {
        if (!image) return '';
        
        if (image.startsWith('uploads/')) {
            return "admin/" + image;
        } else {
            return "admin/uploads/" + image;
        }
    }

    function mettreAJourPanier() {
        const contenu = document.getElementById('panierContenu');
        if (!contenu) return;
        
        contenu.innerHTML = '';
        let total = 0;

        if (state.panier.length === 0) {
            contenu.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="ri-shopping-cart-line text-4xl mb-2"></i>
                    <p>Votre panier est vide</p>
                </div>
            `;
        } else {
            state.panier.forEach(item => {
                const sousTotal = item.prix * item.quantite;
                total += sousTotal;
                
                const imagePath = getImagePath(item.image);
                // Échappement sécurisé pour les noms de produits
                const nomEchappe = item.nom.replace(/'/g, "\\'").replace(/"/g, '\\"').replace(/`/g, '\\`');
                
                contenu.innerHTML += `
                    <div class="flex items-center justify-between mb-4 border-b pb-4">
                        <div class="flex items-center space-x-3 flex-1">
                            ${imagePath ? `
                                <img src="${imagePath}" alt="${item.nom.replace(/"/g, '&quot;')}" 
                                     class="w-16 h-16 object-cover rounded border"
                                     onerror="this.style.display='none'">
                                <div class="w-16 h-16 bg-gray-200 rounded border flex items-center justify-center" style="display: none;">
                                    <i class="ri-image-line text-gray-400"></i>
                                </div>
                            ` : `
                                <div class="w-16 h-16 bg-gray-200 rounded border flex items-center justify-center">
                                    <i class="ri-image-line text-gray-400"></i>
                                </div>
                            `}
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-800">${item.nom}</h4>
                                ${item.poids ? `<p class="text-sm text-gray-600">Poids: ${item.poids}</p>` : ''}
                                <p class="text-gray-700">
                                    ${item.prix} 
                                    <span class="font-semibold ${item.devise === 'USD' ? 'text-green-600' : 'text-red-600'}">
                                        ${item.devise === 'USD' ? '$' : 'FC'}
                                    </span>
                                    × 
                                    <input type="number" value="${item.quantite}" 
                                           min="1" max="${item.quantiteMax}" 
                                           onchange="changerQuantite('${nomEchappe}', this.value)"
                                           class="w-16 border rounded px-2 py-1 text-center">
                                </p>
                                <p class="text-sm font-semibold text-blue-600">
                                    Sous-total: ${sousTotal.toFixed(2)} ${item.devise === 'USD' ? '$' : 'FC'}
                                </p>
                            </div>
                        </div>
                        <button onclick="supprimerDuPanier('${nomEchappe}')" 
                                class="text-red-500 hover:text-red-700 ml-2 p-2 rounded-full hover:bg-red-50 transition-colors">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                `;
            });
        }

        const totalEl = document.getElementById('panierTotal');
        if (totalEl) totalEl.textContent = total.toFixed(2);
        
        const totalDevise = document.getElementById('panierTotalDevise');
        if (totalDevise && state.panier.length > 0) {
            const devise = state.panier[0].devise || 'USD';
            totalDevise.textContent = devise === 'USD' ? '$' : 'FC';
        }

        miseAJourCompteur();
    }

    function showStyledAlert(message, type = 'warning') {
        // Supprimer les alertes existantes
        const existingAlerts = document.querySelectorAll('.custom-alert');
        existingAlerts.forEach(alert => alert.remove());

        const alert = document.createElement('div');
        const styles = {
            warning: 'bg-yellow-50 border-yellow-400 text-yellow-800',
            error: 'bg-red-50 border-red-400 text-red-800',
            info: 'bg-blue-50 border-blue-400 text-blue-800'
        };

        alert.className = `custom-alert fixed top-4 left-1/2 transform -translate-x-1/2 ${styles[type]} border px-6 py-4 rounded-lg shadow-lg z-50 flex items-center space-x-3 min-w-80 max-w-md`;
        
        const icon = type === 'warning' ? 'ri-alert-line' : 
                    type === 'error' ? 'ri-close-circle-line' : 
                    'ri-information-line';
        
        alert.innerHTML = `
            <i class="${icon} text-xl"></i>
            <span class="flex-1 font-medium">${message}</span>
            <button onclick="this.parentElement.remove()" class="text-gray-500 hover:text-gray-700">
                <i class="ri-close-line"></i>
            </button>
        `;

        document.body.appendChild(alert);

        // Auto-suppression après 5 secondes
        setTimeout(() => {
            if (document.body.contains(alert)) {
                alert.remove();
            }
        }, 5000);
    }

    function ajouterAuPanier(nom, prix, devise, poids, quantiteMax, image) {
        // Vérification de connexion utilisant la variable globale
        if (typeof estConnecte === 'undefined' || !estConnecte) {
            showStyledAlert('Veuillez vous connecter pour ajouter des produits au panier.', 'warning');
            return;
        }

        if (quantiteMax <= 0) {
            showStyledAlert('Ce produit est actuellement en rupture de stock.', 'error');
            return;
        }

        let produit = state.panier.find(p => p.nom === nom);
        if (produit) {
            if (produit.quantite < produit.quantiteMax) {
                produit.quantite++;
            } else {
                showStyledAlert('Quantité maximum atteinte pour ce produit', 'warning');
                return;
            }
        } else {
            state.panier.push({ 
                nom, 
                prix: parseFloat(prix), 
                devise: devise || 'USD',
                poids: poids || '',
                quantite: 1, 
                quantiteMax: parseInt(quantiteMax), 
                image: image || '' 
            });
        }
        mettreAJourPanier();
        showNotification('Produit ajouté au panier avec succès !', 'success');
    }

    function changerQuantite(nom, nouvelleQuantite) {
        const produit = state.panier.find(p => p.nom === nom);
        if (!produit) return;
        
        const q = parseInt(nouvelleQuantite) || 1;
        if (q >= 1 && q <= produit.quantiteMax) {
            produit.quantite = q;
            mettreAJourPanier();
            showNotification('Quantité mise à jour', 'info');
        } else {
            showStyledAlert(`Quantité invalide. Veuillez choisir entre 1 et ${produit.quantiteMax}.`, 'warning');
            setTimeout(() => mettreAJourPanier(), 100);
        }
    }

    function supprimerDuPanier(nom) {
        state.panier = state.panier.filter(p => p.nom !== nom);
        mettreAJourPanier();
        showNotification('Produit retiré du panier', 'info');
    }

    function showNotification(message, type = 'success') {
        // Supprimer les notifications existantes
        const existingNotifications = document.querySelectorAll('.custom-notification');
        existingNotifications.forEach(notif => notif.remove());

        const notification = document.createElement('div');
        const styles = {
            success: 'bg-green-600 text-white',
            error: 'bg-red-600 text-white',
            warning: 'bg-yellow-600 text-white',
            info: 'bg-blue-600 text-white'
        };

        const icons = {
            success: 'ri-checkbox-circle-line',
            error: 'ri-close-circle-line',
            warning: 'ri-alert-line',
            info: 'ri-information-line'
        };

        notification.className = `custom-notification fixed top-4 right-4 ${styles[type]} px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300 flex items-center space-x-2`;
        
        notification.innerHTML = `
            <i class="${icons[type]}"></i>
            <span>${message}</span>
        `;

        document.body.appendChild(notification);

        // Animation d'entrée
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Animation de sortie après 3 secondes
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    function showPanierMessage(message, type = 'error') {
        const messageContainer = document.getElementById('panierMessage');
        if (!messageContainer) return;

        // Supprimer les messages existants
        messageContainer.innerHTML = '';

        const styles = {
            error: 'bg-red-100 border-red-300 text-red-700',
            warning: 'bg-yellow-100 border-yellow-300 text-yellow-700',
            success: 'bg-green-100 border-green-300 text-green-700',
            info: 'bg-blue-100 border-blue-300 text-blue-700'
        };

        const icons = {
            error: 'ri-error-warning-line',
            warning: 'ri-alert-line',
            success: 'ri-checkbox-circle-line',
            info: 'ri-information-line'
        };

        const messageEl = document.createElement('div');
        messageEl.className = `mt-3 p-4 rounded-lg border ${styles[type]} flex items-center space-x-3 animate-fade-in`;
        
        messageEl.innerHTML = `
            <i class="${icons[type]} text-xl"></i>
            <div class="flex-1">
                <p class="font-medium">${message}</p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-gray-500 hover:text-gray-700 transition-colors">
                <i class="ri-close-line"></i>
            </button>
        `;

        messageContainer.appendChild(messageEl);

        // Auto-suppression après 5 secondes
        setTimeout(() => {
            if (messageEl.parentNode) {
                messageEl.style.opacity = '0';
                messageEl.style.transition = 'opacity 0.3s ease';
                setTimeout(() => {
                    if (messageEl.parentNode) messageEl.remove();
                }, 300);
            }
        }, 5000);
    }

    function ouvrirPanier() {
        const modal = document.getElementById('panierModal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function fermerPanier() {
        const modal = document.getElementById('panierModal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    }

    // Fonctions globales
    window.ajouterAuPanier = ajouterAuPanier;
    window.changerQuantite = changerQuantite;
    window.supprimerDuPanier = supprimerDuPanier;
    window.ouvrirPanier = ouvrirPanier;
    window.fermerPanier = fermerPanier;
    window.showStyledAlert = showStyledAlert;
    window.showPanierMessage = showPanierMessage;

    document.addEventListener('DOMContentLoaded', function(){
        console.log('Panier.js chargé - Utilisateur connecté:', estConnecte);

        // Récupération des éléments
        const shopIcon = document.querySelector('.shop__icon');
        const fermerBtn = document.getElementById('fermerPanierBtn');
        const continuerAchatsBtn = document.getElementById('continuerAchatsBtn');
        const commanderBtn = document.getElementById('btnCommander');

        // Événements
        if (shopIcon) {
            shopIcon.addEventListener('click', ouvrirPanier);
        }

        if (fermerBtn) {
            fermerBtn.addEventListener('click', fermerPanier);
        }

        if (continuerAchatsBtn) {
            continuerAchatsBtn.addEventListener('click', fermerPanier);
        }

        if (commanderBtn) {
            commanderBtn.addEventListener('click', function() {
                const messageContainer = document.getElementById('panierMessage');

                if (messageContainer) messageContainer.innerHTML = '';

                if (state.panier.length === 0) {
                    showPanierMessage('Votre panier est vide. Ajoutez au moins un produit avant de commander.', 'warning');
                    return;
                }

                // Vérifier à nouveau la connexion
                if (typeof estConnecte === 'undefined' || !estConnecte) {
                    showStyledAlert('Veuillez vous connecter pour passer commande.', 'warning');
                    return;
                }

                localStorage.setItem('panier', JSON.stringify(state.panier));
                window.location.href = 'details-commandes.php';
            });
        }

        // Fermeture au clic sur le fond
        const modal = document.getElementById('panierModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) fermerPanier();
            });
        }

        // Chargement du panier
        const panierSauvegarde = localStorage.getItem('panier');
        if (panierSauvegarde) {
            try {
                state.panier = JSON.parse(panierSauvegarde);
                mettreAJourPanier();
            } catch (e) {
                console.error('Erreur lors du chargement du panier:', e);
                state.panier = [];
            }
        }

        miseAJourCompteur();
    });
})();