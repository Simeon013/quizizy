<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#FCFDFF" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#121834" media="(prefers-color-scheme: dark)">
        <title inertia>Eurêka · Le quiz qui corrige au stylo vert</title>
        <meta name="description" content="Des quiz qui ressemblent à un cahier : le stylo rouge corrige, le stylo vert explique, et tes erreurs reviennent jusqu'à ce que tu les retiennes.">
        <meta property="og:site_name" content="Eurêka">
        <meta property="og:type" content="website">
        <meta property="og:locale" content="fr_FR">
        <meta property="og:title" content="Eurêka · Le quiz qui corrige au stylo vert">
        <meta property="og:description" content="Ici, se tromper fait partie du jeu.">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        {{-- Thème choisi posé avant le premier rendu : pas d'éclair blanc en cahier de nuit. --}}
        <script>
            try { var t = localStorage.getItem('eureka:theme'); if (t === 'clair' || t === 'sombre') document.documentElement.dataset.theme = t; } catch (e) {}
        </script>
        @vite(['resources/js/app.js'])
        <x-inertia::head />
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
