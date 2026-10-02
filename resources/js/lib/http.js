/**
 * Appels JSON du jeu (réponse, indice, question suivante). Laravel pose le
 * cookie XSRF-TOKEN ; on le renvoie en en-tête, comme le ferait axios.
 */
function jetonXsrf() {
    const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
}

export class ErreurHttp extends Error {
    constructor(statut, message) {
        super(message);
        this.statut = statut;
    }
}

export async function envoyer(url, donnees = {}) {
    const reponse = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': jetonXsrf(),
        },
        body: JSON.stringify(donnees),
    });
    const corps = await reponse.json().catch(() => ({}));
    if (!reponse.ok) throw new ErreurHttp(reponse.status, corps.message || 'La demande a échoué.');
    return corps;
}
