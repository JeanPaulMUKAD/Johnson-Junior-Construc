
    let a = 0;
    let masque = document.createElement('div');
    let cercle = document.createElement('div');

    let angle = 0;

    window.addEventListener('load', () => {
        a = 1;

        // Le cercle commence à tourner immédiatement
        anime = setInterval(() => {
            angle += 10; // Vitesse de rotation du cercle
            cercle.style.transform = `translate(-50%, -50%) rotate(${angle}deg)`;
        }, 20);

        // Après 1 seconde, on arrête l'animation et on fait disparaître le masque
        setTimeout(() => {
            clearInterval(anime);
            masque.style.opacity = '0';
        }, 1000);

        setTimeout(() => {
            masque.style.visibility = 'hidden';
        }, 1500);
    });

    // Création du masque
    masque.style.width = '100%';
    masque.style.height = '100vh';
    masque.style.zIndex = 100000;
    masque.style.background = '#ffffff';
    masque.style.position = 'fixed';
    masque.style.top = '0';
    masque.style.left = '0';
    masque.style.opacity = '1';
    masque.style.transition = '0.5s ease';
    masque.style.display = 'flex';
    masque.style.justifyContent = 'center';
    masque.style.alignItems = 'center';
    document.body.appendChild(masque);

    // Création du cercle (réduit)
    cercle.style.width = '40px';  // Au lieu de 15vh
    cercle.style.height = '40px'; // Au lieu de 15vh
    cercle.style.border = '2px solid #f3f3f3'; // Bordure plus fine
    cercle.style.borderTop = '2px solid #2F1C6A';
    cercle.style.borderRadius = '50%';
    cercle.style.position = 'absolute';
    cercle.style.top = '50%';
    cercle.style.left = '50%';
    cercle.style.transform = 'translate(-50%, -50%)';
    cercle.style.boxSizing = 'border-box';
    cercle.style.zIndex = '1';
    masque.appendChild(cercle);

    // Variable de l'animation
    let anime;
