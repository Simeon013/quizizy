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

Vérifier le rendu à 375 et 1280 px, en clair et en sombre, sans débordement horizontal.

## Feuille de route

1. **Fait** : socle, mode solo complet (matières, partie, correction, copie, À revoir, bulletin, gommettes, série de jours), comptes.
2. Mode en direct : code de partie, téléphone-manette, grand écran de l'animateur, palmarès. Temps réel : l'hébergement mutualisé ne peut pas faire tourner Reverb ; piste retenue, un service hébergé (Pusher) derrière une interface, limites de l'offre gratuite à vérifier.
3. L'atelier : création de quiz assistée par IA, propositions « au crayon » (graphite) à repasser « à l'encre » après relecture. Rien n'est publié sans relecture.
4. Mot de passe oublié (demande un envoi d'emails configuré), images de partage, administration.
