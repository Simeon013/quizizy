/**
 * Le transport du mode en direct : on interroge l'état de la salle à
 * intervalle régulier, en envoyant la dernière version reçue. Tant que rien
 * ne bouge, le serveur répond en quelques octets.
 *
 * C'est le seul endroit qui sait comment l'état arrive : pour passer un jour
 * à un service de temps réel (Pusher…), il suffira de remplacer cette fonction.
 */
export function suivreEtat(url, { version = -1, intervalle = 1000, surEtat, surErreur }) {
    let derniere = version;
    let minuteur = 0;
    let arrete = false;
    let echecs = 0;

    async function interroger() {
        if (arrete) return;
        try {
            const reponse = await fetch(`${url}?v=${derniere}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!reponse.ok) throw new Error(String(reponse.status));
            const etat = await reponse.json();
            echecs = 0;
            surErreur?.(null);
            if (!etat.inchange) {
                derniere = etat.version;
                surEtat(etat);
            }
        } catch (e) {
            echecs++;
            surErreur?.(e);
        }
        // Après des échecs, on espace les tentatives (réseau mobile capricieux).
        const attente = echecs ? Math.min(8000, intervalle * 2 ** echecs) : intervalle;
        if (!arrete && !document.hidden) minuteur = setTimeout(interroger, attente);
    }

    // Onglet caché : on arrête d'interroger ; on reprend tout de suite au retour.
    const visibilite = () => {
        clearTimeout(minuteur);
        if (!document.hidden) interroger();
    };
    document.addEventListener('visibilitychange', visibilite);
    minuteur = setTimeout(interroger, intervalle);

    return {
        maintenant() {
            clearTimeout(minuteur);
            interroger();
        },
        connue(v) {
            derniere = v;
        },
        arreter() {
            arrete = true;
            clearTimeout(minuteur);
            document.removeEventListener('visibilitychange', visibilite);
        },
    };
}

/** Compte à rebours local, recalé à chaque état reçu (le serveur fait foi). */
export function compteARebours(restantMs, surTic) {
    const fin = performance.now() + restantMs;
    const tic = () => {
        const reste = Math.max(0, fin - performance.now());
        surTic(reste);
        if (reste <= 0) clearInterval(id);
    };
    const id = setInterval(tic, 200);
    tic();
    return () => clearInterval(id);
}
