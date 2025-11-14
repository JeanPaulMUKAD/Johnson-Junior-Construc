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
                const nomEchappe = item.nom.replace(/'/g, "\\'").replace(/"/g, '\\"');
                
                contenu.innerHTML += `
                    <div class="flex items-center justify-between mb-4 border-b pb-4">
                        <div class="flex items-center space-x-3 flex-1">
                            ${imagePath ? `
                                <img src="${imagePath}" alt="${item.nom}" 
                                     class="w-16 h-16 object-cover rounded border"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-16 h-16 bg-gray-200 rounded border flex items-center justify-center hidden">
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

    function ajouterAuPanier(nom, prix, devise, poids, quantiteMax, image) {
        <?php if (!isset($_SESSION['user_id'])): ?>
            alert('Veuillez vous connecter pour ajouter des produits au panier.');
            return;
        <?php endif; ?>

        if (quantiteMax <= 0) {
            alert('Ce produit est actuellement en rupture de stock.');
            return;
        }

        let produit = state.panier.find(p => p.nom === nom);
        if (produit) {
            if (produit.quantite < produit.quantiteMax) {
                produit.quantite++;
            } else {
                alert('Quantité maximum atteinte pour ce produit');
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
        showNotification('Produit ajouté au panier avec succès !');
    }

    function changerQuantite(nom, nouvelleQuantite) {
        const produit = state.panier.find(p => p.nom === nom);
        if (!produit) return;
        
        const q = parseInt(nouvelleQuantite) || 1;
        if (q >= 1 && q <= produit.quantiteMax) {
            produit.quantite = q;
            mettreAJourPanier();
        } else {
            alert('Quantité invalide (1 - ' + produit.quantiteMax + ')');
            setTimeout(() => mettreAJourPanier(), 100);
        }
    }

    function supprimerDuPanier(nom) {
        state.panier = state.panier.filter(p => p.nom !== nom);
        mettreAJourPanier();
        showNotification('Produit retiré du panier');
    }

    function showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300';
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
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

    document.addEventListener('DOMContentLoaded', function(){
        // Récupération des éléments
        const shopIcon = document.querySelector('.shop__icon');
        const fermerBtn = document.getElementById('fermerPanierBtn');
        const continuerAchatsBtn = document.getElementById('continuerAchatsBtn');
        const commanderBtn = document.getElementById('btnCommander');

        console.log('Éléments trouvés:', {
            shopIcon: !!shopIcon,
            fermerBtn: !!fermerBtn,
            continuerAchatsBtn: !!continuerAchatsBtn,
            commanderBtn: !!commanderBtn
        });

        // Événements
        if (shopIcon) {
            shopIcon.addEventListener('click', ouvrirPanier);
        }

        if (fermerBtn) {
            fermerBtn.addEventListener('click', fermerPanier);
        }

        if (continuerAchatsBtn) {
            continuerAchatsBtn.addEventListener('click', fermerPanier);
            console.log('Événement attaché à continuerAchatsBtn');
        }

        if (commanderBtn) {
            commanderBtn.addEventListener('click', function() {
                const messageContainer = document.getElementById('panierMessage');

                if (messageContainer) messageContainer.innerHTML = '';

                if (state.panier.length === 0) {
                    const msg = document.createElement('div');
                    msg.className = 'mt-3 p-3 rounded-lg bg-red-100 border border-red-300 text-red-700 text-center font-medium';
                    msg.textContent = "⚠️ Votre panier est vide. Ajoutez au moins un produit avant de commander.";
                    messageContainer?.appendChild(msg);

                    setTimeout(() => {
                        if (msg.parentNode) msg.remove();
                    }, 4000);
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
            }
        }

        miseAJourCompteur();
    });
})();