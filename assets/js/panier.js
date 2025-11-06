
(function(){
    const state = {
        panier: []
    };

    function miseAJourCompteur() {
        const nombreArticles = state.panier.reduce((t, it) => t + it.quantite, 0);
        const badge = document.querySelector('.shop__number');
        if (badge) badge.textContent = nombreArticles;
    }

    function mettreAJourPanier() {
        const contenu = document.getElementById('panierContenu');
        if (!contenu) return;
        contenu.innerHTML = '';
        let total = 0;

        state.panier.forEach(item => {
            const sousTotal = item.prix * item.quantite;
            total += sousTotal;
            contenu.innerHTML += `
                <div class="flex items-center justify-between mb-4 border-b pb-4">
                    <img src="${item.image}" alt="${item.nom}" class="w-16 h-16 object-cover rounded">
                    <div class="flex-1 ml-4">
                        <h4 class="font-medium">${item.nom}</h4>
                        <p>${item.prix} € × 
                            <input type="number" value="${item.quantite}" 
                                   min="1" max="${item.quantiteMax}" 
                                   onchange="changerQuantite('${item.nom}', this.value)"
                                   class="w-16 border rounded px-2">
                        </p>
                    </div>
                    <button onclick="supprimerDuPanier('${item.nom}')" class="text-red-500 hover:text-red-700">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            `;
        });

        const totalEl = document.getElementById('panierTotal');
        if (totalEl) totalEl.textContent = total.toFixed(2);
        miseAJourCompteur();
    }

    function ajouterAuPanier(nom, prix, quantiteMax, image) {
        let produit = state.panier.find(p => p.nom === nom);
        if (produit) {
            if (produit.quantite < produit.quantiteMax) {
                produit.quantite++;
            } else {
                alert('Quantité maximum atteinte pour ce produit');
                return;
            }
        } else {
            state.panier.push({ nom, prix: parseFloat(prix), quantite: 1, quantiteMax: parseInt(quantiteMax), image });
        }
        mettreAJourPanier();
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
            mettreAJourPanier();
        }
    }

    function supprimerDuPanier(nom) {
        state.panier = state.panier.filter(p => p.nom !== nom);
        mettreAJourPanier();
    }

    function ouvrirPanier() {
        document.getElementById('panierModal')?.classList.remove('hidden');
    }
    function fermerPanier() {
        document.getElementById('panierModal')?.classList.add('hidden');
    }

    // Fonctions accessibles globalement
    window.ajouterAuPanier = ajouterAuPanier;
    window.changerQuantite = changerQuantite;
    window.supprimerDuPanier = supprimerDuPanier;

    document.addEventListener('DOMContentLoaded', function(){
        const shopIcon = document.querySelector('.shop__icon');
        const fermerBtn = document.getElementById('fermerPanier');
        const commanderBtn = document.getElementById('btnCommander');

        if (shopIcon) shopIcon.addEventListener('click', ouvrirPanier);
        if (fermerBtn) fermerBtn.addEventListener('click', fermerPanier);

        // ✅ Bouton Commander — version stylisée
if (commanderBtn) {
    commanderBtn.addEventListener('click', function() {
        const messageContainer = document.getElementById('panierMessage');

        // Supprime les anciens messages
        if (messageContainer) messageContainer.innerHTML = '';

        if (state.panier.length === 0) {
            // Crée un message stylisé d'erreur
            const msg = document.createElement('div');
            msg.className = 'mt-3 p-3 rounded-lg bg-red-100 border border-red-300 text-red-700 text-center font-medium animate-fadeIn';
            msg.textContent = "⚠️ Votre panier est vide. Ajoutez au moins un produit avant de commander.";
            messageContainer?.appendChild(msg);

            // Supprime le message après 4 secondes
            setTimeout(() => msg.remove(), 4000);
            return;
        }

        // Récupère le premier produit (ou plusieurs)
        const premierProduit = state.panier[0];
        const nomEnc = encodeURIComponent(premierProduit.nom);

        // Redirection vers la page détails
        window.location.href = `details-commandes.php?nom=${nomEnc}`;
    });
}


        // Fermeture au clic sur le fond gris
        const modal = document.getElementById('panierModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) fermerPanier();
            });
        }
    });
})();

