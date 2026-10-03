# CLAUDE.md

Eurêka (dépôt `quizizy`) : un quiz qui ressemble à un cahier d'élève. On coche, on se trompe, le stylo rouge corrige, le stylo vert explique, et les erreurs reviennent dans « À revoir » jusqu'à ce qu'on les réussisse. Projet personnel de Siméon, sans lien avec Layers Tec : ni charte ni logo de Layers Tec ici. Interface et code en français.

## Refonte complète (2 octobre 2026)

Tout a été réécrit à la demande de Siméon (« refonte totale, graphique et technique »). L'ancienne version (Laravel 12 + starter kit Vue, juin 2025) reste dans l'historique git. Ce qu'elle faisait de travers, à ne pas réintroduire :

- **Back-office ouvert à tout compte** : `/admin` n'exigeait que `auth`, et `is_admin` n'existait même pas en base. La colonne existe désormais, **hors de `#[Fillable]`** (un test vérifie qu'on ne peut pas se l'attribuer à l'inscription).
- **Le jeu n'avait jamais tourné** : `QuizController` sans routes, et écrit pour un autre schéma (colonnes `quizzes_completed`, `quiz_session_id` inexistantes).
- **Nom** : « Quizizy » était trop proche de la marque Quizizz. Nom retenu : **Eurêka** (le dépôt garde son nom).

## Commandes

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed   # contenu (80 questions) + compte demo@eureka.test / password hors production
php artisan serve            # + npm run dev
./vendor/bin/pest            # SQLite en mémoire (phpunit.xml)
./vendor/bin/pint            # formatage PHP
npm run build                # à lancer ET committer après tout changement CSS/JS
```

`public/build/` est **versionné** : un hébergement mutualisé n'a pas Node. La CI échoue s'il ne correspond pas aux sources.

## Pile

Laravel 13 (PHP 8.3), Inertia 3 + Vue 3.5, Tailwind 4 (plugin Vite), Vite 8, Pest 4. Pas de SSR. Polices auto-hébergées (`@fontsource`). Animations en CSS : **Motion (motion-v) a été essayé puis retiré**, il pesait 44 Ko compressés pour une seule transition (l'écran de jeu est passé de 44 à 3,6 Ko). Confettis : `canvas-confetti`, chargé à la demande sur la copie.

## Le jeu (`app/Jeu`)

**Le serveur décide de tout.** Le navigateur ne reçoit jamais le champ `juste` d'un choix avant d'avoir répondu (un test le vérifie), ni l'heure d'affichage.

- `Deroulement` : tirage (10 questions au plus), affichage, indice, réponse, fin. Le chrono démarre à `question_affichee_le`, posé quand la question est affichée ; **recharger la page ne le remet pas à zéro**. La question suivante ne démarre que sur `POST /parties/{id}/question`, pas pendant la lecture de la correction. Réponse arrivée après le chrono + `Notation::TOLERANCE_MS` : temps écoulé, comptée fausse.
- Une seule réponse par question (index unique + verrou) : un double clic renvoie 409, la page se recharge.
- Ordre des choix mélangé de façon **stable** par partie (graine = partie + question).
- `Notation` : 100 points + bonus de rapidité jusqu'à 50 ; indice = 50 points, sans bonus. Note sur 20 au demi-point.
- `AccesPartie` : une partie appartient à son joueur, ou à la session de l'invité qui l'a créée ; pour tout autre, 404. À l'inscription ou à la connexion, les copies faites en invité **dans cette même session** rejoignent le cahier.
- `Revision` (« À revoir ») : questions dont la **dernière** réponse était fausse. Lecture, pas une liste stockée : réussir la question la retire d'elle-même.
- `AttributionGommettes` : catalogue dans `App\Support\Gommettes` (clés stockées en base : ne jamais en renommer une). `SerieDeJours` compte les jours dans le fuseau `config('eureka.fuseau')`.
- `Bulletin` : moyenne sur 20 par matière ; l'appréciation, au stylo vert, encourage toujours.

Parties en ULID : l'adresse ne se devine pas. Limites de débit nommées dans `AppServiceProvider`, **une clé `by()` par limite** (deux limites de même clé partagent leur compteur).

## Mode en direct (`app/Direct`, 3 octobre 2026)

Un animateur (compte obligatoire) projette un quiz ; les joueurs rejoignent sans compte, avec un **code à 6 chiffres** et un pseudo, depuis leur téléphone (`/rejoindre`, ou le QR code du grand écran). Écrans : `pages/Direct/Ecran.vue` (grand écran, sans la mise en page du site), `Manette.vue` (téléphone), `Rejoindre.vue`.

- États d'une salle : `attente` → `question` → `correction` → … → `terminee`. `Animation` les fait avancer, `Vues` dit ce que voit chaque écran. Pendant la question, **ni le téléphone ni le grand écran ne reçoivent la bonne réponse**, et le téléphone ne montre pas ses points (ils la trahiraient).
- **Pas de tâche planifiée** : une question dont le temps est écoulé se ferme à la première lecture de l'état (`Animation::actualiser`). Elle se ferme aussi dès que tous les présents ont répondu.
- **Temps réel par interrogation régulière**, toutes les secondes, avec la dernière `version` connue : si rien n'a bougé, le serveur répond `{version, inchange}`. Fonctionne sur un hébergement mutualisé, sans service externe. Tout le transport est dans `resources/js/lib/direct.js` : passer à Pusher (ou Reverb sur un VPS) = remplacer cette seule fonction et diffuser les changements de `version` côté serveur. Onglet caché : l'interrogation s'arrête.
- **Limite de débit par joueur, pas par IP** : une classe entière partage souvent la même adresse. Pas non plus par identifiant de session : un client sans cookie en change à chaque requête et échappait à la limite (trouvé par un test). Plafond large par IP en plus.
- L'animateur peut **retirer un pseudo** (projeté au tableau, un pseudo déplacé se voit de tous). 60 joueurs au plus par salle.
- Une salle sans activité depuis 6 h est fermée ; son code peut resservir. Choix mélangés de façon stable (`Jeu\Melange`) : téléphone et grand écran montrent les mêmes symboles aux mêmes places.
- Symboles des réponses tracés au feutre (triangle, rond, carré, étoile) + couleur + texte : jamais la couleur seule. 4 choix au plus en direct.
- QR code : `uqr`, chargé à la demande sur le grand écran seulement.

## L'atelier (`app/Atelier`, 3 octobre 2026)

Tout compte peut créer ses quiz (`/atelier`) : à partir d'un **texte collé**, d'un **thème**, ou **à la main**. L'IA propose des questions **au crayon** (`questions.a_l_encre = false`, affichées en graphite, pointillé, écriture à la main) ; l'auteur les relit, les corrige, les passe **à l'encre**. **Un quiz ne se publie que si toutes ses questions sont à l'encre, et s'il en a au moins 5.** Corriger une question vaut relecture : elle passe à l'encre.

- **Rédacteur** : interface `Redacteur`, choisi par `EUREKA_REDACTEUR` : `claude` (par défaut si `ANTHROPIC_API_KEY` est posée), `factice` (tests et développement, aucun appel réseau), `aucun` (l'atelier reste utilisable à la main). `RedacteurClaude` passe par le **SDK PHP officiel** (`anthropic-ai/sdk`), modèle `claude-opus-5-5`, effort `high` (la justesse des faits compte plus que la vitesse), **sortie contrainte par un schéma JSON** (`Consignes::schema()`), et **repli automatique** si le modèle décline une demande (`fallbacks: 'default'`, en-tête `server-side-fallback-2026-07-01`). Erreurs du SDK traduites en messages lisibles (`ErreurRedaction`) ; un refus (`stop_reason: refusal`) aussi.
- **Consignes** (`Consignes::SYSTEME`) : avec un texte source, rien qui n'y figure ; sans texte, seulement des faits établis ; « écris-en moins plutôt que d'en inventer ». Le texte source est traité comme une matière, jamais comme des instructions.
- **Rien n'est enregistré tel que le modèle l'a rendu** : `Nettoyage` écarte les questions bancales (2 à 4 choix distincts, énoncé et explication non vides, longueurs bornées, balises retirées, doublons d'énoncé avec les questions existantes).
- **Coût maîtrisé** : `EUREKA_GENERATIONS_PAR_JOUR` (5) par compte, administrateurs sans limite ; journal `generations` (statut, modèle, jetons d'entrée et de sortie). Le quota se compte sur la **journée locale ramenée en UTC** : sans cette conversion, il sautait chaque nuit entre 23 h et minuit UTC (trouvé par un test lancé à cette heure-là, verrouillé par un test).
- Génération **synchrone** (pas de file d'attente sur un mutualisé) : `set_time_limit(180)`, et l'écran d'attente de Gribouille côté navigateur.
- **Visibilité** : un quiz d'atelier publié se joue par son lien (solo et direct) ; seul un administrateur le met **au catalogue** (`quiz.au_catalogue`, intercalaires). Un brouillon n'est jouable que par son auteur, pour l'essayer.
- Une question **déjà jouée ne se gomme pas** (les réponses des joueurs partiraient en cascade) : on la corrige.

## Mot de passe oublié (3 octobre 2026)

Mécanisme intégré de Laravel (`Password::sendResetLink` / `Password::reset`, table `password_reset_tokens`) : lien à usage unique, valable 60 minutes, un seul envoi par adresse et par minute. Pages `Auth/Oubli` et `Auth/NouveauMotDePasse`, email `App\Notifications\LienMotDePasse` (envoi synchrone, pas de file).

- **Même réponse que l'adresse ait un cahier ou non** (« Si un cahier existe à cette adresse… ») : sinon n'importe qui saurait quelles adresses sont inscrites. Limite `oubli` : 3 demandes par minute et 20 par jour et par IP.
- Après le changement : connexion directe au cahier, **et toutes les autres sessions du compte sont fermées** (table `sessions`), au cas où quelqu'un connaissait l'ancien mot de passe.
- Emails aux couleurs du cahier : gabarits publiés dans `resources/views/vendor/mail/html` (papier quadrillé, marge rouge, bouton encre), polices système (les messageries ignorent les polices web). Phrases anglaises des notifications traduites dans `lang/fr.json`.
- **L'envoi réel n'est pas configuré** : `MAIL_MAILER=log` écrit les emails dans `storage/logs/laravel.log`. À régler (`MAIL_*`) avant la mise en ligne.

## Contenu

`database/seeders/contenu/*.php` : une matière par fichier, **le premier choix de chaque question est le bon** (mélangé à l'affichage). `ContenuSeeder` met à jour par slug et peut tourner en production ; il ne remplace jamais les questions d'un quiz déjà joué (la suppression effacerait les réponses en cascade). Chaque question a 4 choix, une explication et un indice : un test le vérifie. **Aucun chiffre ni fait sans être sûr** : une phrase non vérifiée (« popularisée par Baudelaire ») a été retirée.

Couleurs d'onglet : liste fermée `App\Support\Onglets`, jamais une couleur libre.

## Charte « Carnet » (validée par Siméon le 2/10/2026)

Choisie parmi trois directions (Plateau, Carnet, Wax). Référence complète : `design/charte-carnet.html`. Mascotte **Gribouille** : un point d'interrogation au feutre qui se redresse en point d'exclamation au moment du déclic (`composants/Gribouille.vue`, géométrie dans `lib/gribouille.js` ; le « ? » et le « ! » ont la même suite de commandes pour pouvoir s'interpoler).

- **Chaque couleur est une fourniture avec un seul rôle** : encre (texte), graphite (provisoire), surligneur (à retenir), **rouge = corriger, vert = encourager**. Le rouge ne s'affiche jamais sans une note verte à côté. Jetons dans `resources/css/app.css`.
- L'état d'une réponse ne repose jamais sur la couleur seule : entourée, cochée, barrée.
- Objets du cahier = éléments d'interface : intercalaires (matières), crayon qui s'use (chrono), règle (progression), post-it (indice, messages), gommettes (badges), tampon (copie rendue), page cornée (partie à reprendre), gomme (recommencer).
- Rôles typographiques (`t-geant`, `t-page`, `t-section`, `t-question`, `t-carte`, `chapeau`, `surtitre`, `note-verte`, `note-rouge`) : jamais une taille au cas par cas. Permanent Marker (titres), Atkinson Hyperlegible (texte, conçue pour les lecteurs malvoyants), Caveat (annotations).
- **Deux thèmes** : le cahier (clair) et le cahier de nuit (sombre), mêmes rôles. Suit l'appareil, bouton pour choisir (`eureka:theme`, posé avant le premier rendu dans `app.blade.php`).
- Ton : on tutoie le joueur, on n'emploie jamais un mot de reproche.

### Le papier, pour de vrai (3 octobre 2026)

Retour de Siméon : la DA papier était « trop timide », il manquait la sensation de travailler sur du vrai papier. D'où :

- **Tracés à main levée, jamais identiques** : `composants/Trace.vue` (cercle, rature, barre, coche, souligne, surligne), géométrie tirée au hasard à chaque affichage dans `lib/main-levee.js` (Rough.js, `RoughGenerator` seul). Dessinés trait par trait (Web Animations, vitesse en px/ms par geste), `parLigne` pour ne couvrir que les lignes écrites. Ne pas revenir à des chemins SVG figés.
- **On rature, on ne barre pas** : gribouillis serré mais léger (opacité ~0,6, trait fin), réglé pour que le mot reste lisible. Ne pas l'épaissir.
- **Sons synthétisés** (`lib/sons.js`, Web Audio, aucun fichier) : crayon qui gratte pendant chaque tracé et chaque note écrite, page qui tourne (question suivante), tampon (copie), gomme (recommencer). Coupables par le bouton haut-parleur (`BoutonSon`, `eureka:son`). Aucun son avec « mouvement réduit », ni dans le corrigé de la copie (trop de tracés à la suite).
- **Les notes s'écrivent** (`composants/Ecrit.vue`) : lettre après lettre, hésitations entre les mots ; chaque lettre est déjà à sa place (rien ne bouge), texte entier pour les lecteurs d'écran.
- **Rien n'est aligné au cordeau** (`lib/papier.js`) : feuilles, post-it, choix, tuiles et boutons reçoivent à l'apparition une inclinaison et des coins au hasard (`rotate` et `border-radius` en ligne, qui ne gênent pas les `transform` des animations). L'angle diminue avec la taille pour ne jamais déborder à 375 px.
- **Grain du papier** : bruit fractal SVG en data URI (`--grain`), sur le fond, les feuilles et les post-it, plus clair la nuit.
- La coche du joueur est à **l'encre** : le rouge reste au correcteur.
- Gribouille en « ! » : les yeux (blanc + pupille) débordent du trait, sinon ils disparaissaient.

Vérifier le rendu à 375 et 1280 px, en clair et en sombre, sans débordement horizontal.

## Feuille de route

1. **Fait** : socle, mode solo complet (matières, partie, correction, copie, À revoir, bulletin, gommettes, série de jours), comptes.
2. **Fait** : mode en direct (voir plus haut). À envisager : Pusher si l'interrogation régulière devient trop lourde pour l'hébergement (au-delà de quelques dizaines de joueurs), limites de l'offre gratuite à vérifier.
3. **Fait** : l'atelier (voir plus haut). Jamais essayé avec une vraie clé d'API dans cette session : la qualité des questions proposées reste à juger sur de vrais textes.
4. **Fait** : mot de passe oublié (voir plus haut ; l'envoi d'emails reste à configurer à la mise en ligne).
5. Images de partage, administration.
