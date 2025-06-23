<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const sidebarOpen = ref(false);
const profileMenuOpen = ref(false);
const page = usePage();

const navigation = [
    {
        name: 'Tableau de bord',
        href: route('admin.dashboard'),
        icon: 'tachometer-alt',
        active: ['admin/dashboard']
    },
    {
        name: 'Catégories',
        href: route('admin.categories.index'),
        icon: 'folder',
        active: ['admin/categories']
    },
    {
        name: 'Questions',
        href: '#',
        icon: 'question-circle',
        active: ['admin/questions']
    },
    {
        name: 'Utilisateurs',
        href: '#',
        icon: 'users',
        active: ['admin/users']
    },
    {
        name: 'Statistiques',
        href: '#',
        icon: 'chart-bar',
        active: ['admin/stats']
    },
];

const userNavigation = [
    { name: 'Votre profil', href: '#', icon: 'user' },
    { name: 'Paramètres', href: '#', icon: 'cog' },
    { name: 'Déconnexion', href: route('logout'), method: 'post', icon: 'sign-out-alt' },
];

// Vérifier si un élément de menu est actif
const isActive = (menuItem) => {
    if (!menuItem.active) return false;
    return menuItem.active.some(path => page.url.startsWith('/' + path));
};

// Gérer la fermeture du menu profil en cliquant à l'extérieur
const handleClickOutside = (event) => {
    const profileMenu = document.getElementById('profile-menu');
    if (profileMenu && !profileMenu.contains(event.target)) {
        profileMenuOpen.value = false;
    }
};

// Gérer le redimensionnement de la fenêtre
const handleResize = () => {
    if (window.innerWidth >= 1024) {
        sidebarOpen.value = false;
    }
};

// Fermer le menu latéral lors du changement de page
const closeSidebar = () => {
    if (window.innerWidth < 1024) {
        sidebarOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('resize', handleResize);
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Barre de navigation supérieure -->
        <header class="bg-white dark:bg-gray-800 shadow-sm z-20">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Logo et bouton menu mobile -->
                    <div class="flex items-center">
                        <button
                            @click="sidebarOpen = !sidebarOpen"
                            class="lg:hidden mr-2 p-2 text-gray-500 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 rounded-md"
                            aria-label="Ouvrir le menu"
                        >
                            <i class="fas fa-bars h-5 w-5"></i>
                        </button>
                        <Link :href="route('admin.dashboard')" class="flex-shrink-0 flex items-center">
                            <span class="text-xl font-bold text-gray-900 dark:text-white">Quizizy Admin</span>
                        </Link>
                    </div>

                    <!-- Barre de recherche et profil -->
                    <div class="flex items-center">
                        <!-- Barre de recherche (masquée sur mobile) -->
                        <div class="hidden md:block relative mx-4">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input
                                type="text"
                                class="block w-64 pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                placeholder="Rechercher..."
                            >
                        </div>

                        <!-- Menu utilisateur -->
                        <div class="ml-4 relative" id="profile-menu">
                            <button
                                @click="profileMenuOpen = !profileMenuOpen"
                                class="max-w-xs flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                id="user-menu"
                                aria-expanded="false"
                                aria-haspopup="true"
                            >
                                <span class="sr-only">Ouvrir le menu utilisateur</span>
                                <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold">
                                    {{ $page.props.auth?.user?.name?.charAt(0) || 'U' }}
                                </div>
                                <span class="hidden md:inline-block ml-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ $page.props.auth?.user?.name || 'Utilisateur' }}
                                </span>
                                <i class="fas fa-chevron-down ml-1 text-gray-400 text-xs"></i>
                            </button>

                            <!-- Menu déroulant utilisateur -->
                            <transition
                                enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95"
                            >
                                <div
                                    v-show="profileMenuOpen"
                                    class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
                                    role="menu"
                                    aria-orientation="vertical"
                                    aria-labelledby="user-menu"
                                >
                                    <Link
                                        v-for="(item, index) in userNavigation"
                                        :key="index"
                                        :href="item.href"
                                        :method="item.method"
                                        as="button"
                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                                        :class="{ 'border-t border-gray-200 dark:border-gray-700 mt-1 pt-2': index === userNavigation.length - 2 }"
                                        role="menuitem"
                                        @click="profileMenuOpen = false"
                                    >
                                        <i v-if="item.icon" :class="`fas fa-${item.icon} mr-2 w-4 text-center`"></i>
                                        {{ item.name }}
                                    </Link>
                                </div>
                            </transition>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Conteneur principal -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Barre latérale -->
            <div
                class="fixed inset-y-0 left-0 z-30 w-64 transform lg:translate-x-0 bg-white dark:bg-gray-800 shadow-lg transition duration-200 ease-in-out"
                :class="{
                    'translate-x-0': sidebarOpen,
                    '-translate-x-full': !sidebarOpen,
                    'lg:translate-x-0': true
                }"
            >
                <div class="flex-1 flex flex-col h-0 pt-5 pb-4 overflow-y-auto">
                    <!-- Logo et bouton de fermeture (mobile) -->
                    <div class="flex items-center justify-between px-4 mb-5">
                        <div class="flex-shrink-0 flex items-center">
                            <span class="text-xl font-bold text-gray-900 dark:text-white">Menu</span>
                        </div>
                        <button
                            @click="sidebarOpen = false"
                            class="lg:hidden p-2 rounded-md text-gray-500 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none"
                        >
                            <i class="fas fa-times h-5 w-5"></i>
                        </button>
                    </div>

                    <!-- Navigation -->
                    <nav class="px-2 space-y-1">
                        <Link
                            v-for="item in navigation"
                            :key="item.name"
                            :href="item.href"
                            @click="closeSidebar"
                            class="group flex items-center px-3 py-3 text-sm font-medium rounded-md transition-colors duration-150"
                            :class="{
                                'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300': isActive(item),
                                'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700': !isActive(item)
                            }"
                        >
                            <i
                                :class="[
                                    `fas fa-${item.icon} mr-3 flex-shrink-0 h-5 w-5 text-center`,
                                    isActive(item)
                                        ? 'text-blue-600 dark:text-blue-400'
                                        : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400'
                                ]"
                            ></i>
                            <span class="truncate">{{ item.name }}</span>
                            <span
                                v-if="isActive(item)"
                                class="ml-auto h-2 w-2 rounded-full bg-blue-500"
                            ></span>
                        </Link>
                    </nav>

                    <!-- Informations utilisateur (mobile) -->
                    <div class="mt-auto pt-4 border-t border-gray-200 dark:border-gray-700 px-4">
                        <div class="flex items-center">
                            <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                <span class="text-blue-600 dark:text-blue-400 font-medium">
                                    {{ $page.props.auth?.user?.name?.charAt(0) || 'U' }}
                                </span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ $page.props.auth?.user?.name || 'Utilisateur' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $page.props.auth?.user?.email || '' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="flex-1 overflow-auto focus:outline-none lg:ml-64 transition-all duration-200" tabindex="0">
                <!-- Overlay pour mobile -->
                <div
                    v-show="sidebarOpen"
                    @click="sidebarOpen = false"
                    class="fixed inset-0 z-20 bg-black bg-opacity-50 lg:hidden"
                ></div>

                <!-- Contenu de la page -->
                <main class="p-6">
                    <slot name="header">
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                            {{ $page.props.title || 'Tableau de bord' }}
                        </h1>
                    </slot>
                    
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Transitions pour le menu latéral */
.slide-enter-active, .slide-leave-active {
    transition: transform 0.2s ease;
}
.slide-enter-from, .slide-leave-to {
    transform: translateX(-100%);
}

/* Animation pour le menu utilisateur */
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

/* Personnalisation de la barre de défilement */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Mode sombre pour la barre de défilement */
.dark ::-webkit-scrollbar-track {
    background: #2d3748;
}

.dark ::-webkit-scrollbar-thumb {
    background: #4a5568;
}

.dark ::-webkit-scrollbar-thumb:hover {
    background: #718096;
}

/* Amélioration de la transition des couleurs */
* {
    transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}
</style>
