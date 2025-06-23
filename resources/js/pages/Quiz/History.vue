<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    quizSessions: {
        type: Array,
        required: true
    },
    userStats: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({
            category: null,
            date_from: null,
            date_to: null,
            difficulty: null,
            score_min: null,
            score_max: null
        })
    },
    categories: {
        type: Array,
        default: () => []
    }
});

// Options de tri
const sortOptions = [
    { value: 'newest', label: 'Plus récent' },
    { value: 'oldest', label: 'Plus ancien' },
    { value: 'score_high', label: 'Meilleur score' },
    { value: 'score_low', label: 'Plus bas score' },
    { value: 'duration_fastest', label: 'Plus rapide' },
    { value: 'duration_slowest', label: 'Plus lent' }
];

// État du filtre et du tri
const sortBy = ref('newest');
const activeFilters = ref({ ...props.filters });
const showFilters = ref(false);

// Formater la date
const formatDate = (dateString) => {
    if (!dateString) return '';
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
};

// Formater la durée (secondes en MM:SS)
const formatDuration = (seconds) => {
    if (!seconds) return '--:--';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

// Obtenir la classe de couleur pour le score
const getScoreColor = (score) => {
    if (score >= 80) return 'text-green-500';
    if (score >= 50) return 'text-yellow-500';
    return 'text-red-500';
};

// Obtenir la classe de difficulté
const getDifficultyClass = (difficulty) => {
    switch (difficulty) {
        case 'easy': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'medium': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        case 'hard': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
};

// Obtenir le libellé de la difficulté
const getDifficultyLabel = (difficulty) => {
    switch (difficulty) {
        case 'easy': return 'Facile';
        case 'medium': return 'Moyen';
        case 'hard': return 'Difficile';
        default: return 'Tous niveaux';
    }
};

// Filtrer et trier les sessions de quiz
const filteredSessions = computed(() => {
    let sessions = [...props.quizSessions];
    
    // Appliquer les filtres
    if (activeFilters.value.category) {
        sessions = sessions.filter(session => 
            session.category_id === parseInt(activeFilters.value.category)
        );
    }
    
    if (activeFilters.value.difficulty) {
        sessions = sessions.filter(session => 
            session.difficulty === activeFilters.value.difficulty
        );
    }
    
    if (activeFilters.value.date_from) {
        const dateFrom = new Date(activeFilters.value.date_from);
        sessions = sessions.filter(session => 
            new Date(session.completed_at) >= dateFrom
        );
    }
    
    if (activeFilters.value.date_to) {
        const dateTo = new Date(activeFilters.value.date_to);
        dateTo.setHours(23, 59, 59, 999);
        sessions = sessions.filter(session => 
            new Date(session.completed_at) <= dateTo
        );
    }
    
    if (activeFilters.value.score_min) {
        sessions = sessions.filter(session => 
            session.score >= parseInt(activeFilters.value.score_min)
        );
    }
    
    if (activeFilters.value.score_max) {
        sessions = sessions.filter(session => 
            session.score <= parseInt(activeFilters.value.score_max)
        );
    }
    
    // Trier les sessions
    switch (sortBy.value) {
        case 'newest':
            sessions.sort((a, b) => new Date(b.completed_at) - new Date(a.completed_at));
            break;
        case 'oldest':
            sessions.sort((a, b) => new Date(a.completed_at) - new Date(b.completed_at));
            break;
        case 'score_high':
            sessions.sort((a, b) => b.score - a.score);
            break;
        case 'score_low':
            sessions.sort((a, b) => a.score - b.score);
            break;
        case 'duration_fastest':
            sessions.sort((a, b) => (a.duration_seconds || 0) - (b.duration_seconds || 0));
            break;
        case 'duration_slowest':
            sessions.sort((a, b) => (b.duration_seconds || 0) - (a.duration_seconds || 0));
            break;
    }
    
    return sessions;
});

// Réinitialiser les filtres
const resetFilters = () => {
    activeFilters.value = {
        category: null,
        date_from: null,
        date_to: null,
        difficulty: null,
        score_min: null,
        score_max: null
    };
    sortBy.value = 'newest';
};

// Vérifier s'il y a des filtres actifs
const hasActiveFilters = computed(() => {
    return Object.values(activeFilters.value).some(value => value !== null && value !== '');
});

// Calculer la durée totale de jeu
const totalPlayTime = computed(() => {
    return filteredSessions.value.reduce((total, session) => {
        return total + (session.duration_seconds || 0);
    }, 0);
});

// Calculer le score moyen
const averageScore = computed(() => {
    if (filteredSessions.value.length === 0) return 0;
    const sum = filteredSessions.value.reduce((total, session) => {
        return total + session.score;
    }, 0);
    return Math.round(sum / filteredSessions.value.length);
});

// Calculer le nombre de quiz réussis (score >= 80%)
const successfulQuizzes = computed(() => {
    return filteredSessions.value.filter(session => session.score >= 80).length;
});

// Calculer le meilleur score
const bestScore = computed(() => {
    if (filteredSessions.value.length === 0) return 0;
    return Math.max(...filteredSessions.value.map(session => session.score));
});
</script>

<template>
    <Head title="Historique des quiz" />
    
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                    Historique des quiz
                </h2>
                <div class="flex items-center space-x-4">
                    <Link 
                        :href="route('quiz.select-category')" 
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        <i class="mr-2 fas fa-home"></i>
                        Accueil
                    </Link>
                    <Link 
                        :href="route('quiz.leaderboard')" 
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <i class="mr-2 fas fa-trophy"></i>
                        Classement
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl px-4 mx-auto sm:px-6 lg:px-8">
                <!-- Statistiques rapides -->
                <div class="grid grid-cols-1 gap-5 mb-8 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Nombre de quiz -->
                    <div class="px-4 py-5 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 p-3 bg-indigo-500 rounded-md">
                                <i class="text-white fas fa-list-ol"></i>
                            </div>
                            <div class="flex-1 w-0 ml-5">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-300">
                                        Quiz terminés
                                    </dt>
                                    <dd>
                                        <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                            {{ filteredSessions.length }}
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Score moyen -->
                    <div class="px-4 py-5 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 p-3 bg-green-500 rounded-md">
                                <i class="text-white fas fa-chart-line"></i>
                            </div>
                            <div class="flex-1 w-0 ml-5">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-300">
                                        Score moyen
                                    </dt>
                                    <dd>
                                        <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                            {{ averageScore }}%
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Meilleur score -->
                    <div class="px-4 py-5 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 p-3 bg-yellow-500 rounded-md">
                                <i class="text-white fas fa-trophy"></i>
                            </div>
                            <div class="flex-1 w-0 ml-5">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-300">
                                        Meilleur score
                                    </dt>
                                    <dd>
                                        <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                            {{ bestScore }}%
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Temps de jeu total -->
                    <div class="px-4 py-5 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 p-3 bg-blue-500 rounded-md">
                                <i class="text-white fas fa-clock"></i>
                            </div>
                            <div class="flex-1 w-0 ml-5">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-300">
                                        Temps de jeu total
                                    </dt>
                                    <dd>
                                        <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                            {{ Math.floor(totalPlayTime / 60) }} min
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtres et tri -->
                <div class="mb-6">
                    <div class="flex flex-col justify-between mb-4 space-y-4 md:items-center md:flex-row md:space-y-0">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Mes sessions de quiz
                        </h3>
                        
                        <div class="flex items-center space-x-3">
                            <div class="relative">
                                <button 
                                    @click="showFilters = !showFilters"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700"
                                >
                                    <i class="mr-2 fas fa-filter"></i>
                                    Filtres
                                    <span 
                                        v-if="hasActiveFilters"
                                        class="absolute -top-1 -right-1 flex h-3 w-3"
                                    >
                                        <span class="absolute inline-flex w-full h-full rounded-full bg-indigo-400 opacity-75 animate-ping"></span>
                                        <span class="relative inline-flex w-3 h-3 rounded-full bg-indigo-500"></span>
                                    </span>
                                </button>
                            </div>
                            
                            <div class="flex items-center
                                <select
                                    v-model="sortBy"
                                    class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                >
                                    <option 
                                        v-for="option in sortOptions" 
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        Trier par : {{ option.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panneau des filtres -->
                    <div 
                        v-show="showFilters"
                        class="p-4 mb-6 bg-white rounded-lg shadow dark:bg-gray-800"
                    >
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                            <!-- Filtre par catégorie -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Catégorie
                                </label>
                                <select
                                    v-model="activeFilters.category"
                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                >
                                    <option :value="null">Toutes les catégories</option>
                                    <option 
                                        v-for="category in categories" 
                                        :key="category.id"
                                        :value="category.id"
                                    >
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>
                            
                            <!-- Filtre par difficulté -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Difficulté
                                </label>
                                <select
                                    v-model="activeFilters.difficulty"
                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                >
                                    <option :value="null">Tous les niveaux</option>
                                    <option value="easy">Facile</option>
                                    <option value="medium">Moyen</option>
                                    <option value="hard">Difficile</option>
                                </select>
                            </div>
                            
                            <!-- Filtre par date de début -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    À partir du
                                </label>
                                <input
                                    type="date"
                                    v-model="activeFilters.date_from"
                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                />
                            </div>
                            
                            <!-- Filtre par date de fin -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Jusqu'au
                                </label>
                                <input
                                    type="date"
                                    v-model="activeFilters.date_to"
                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                />
                            </div>
                            
                            <!-- Filtre par score minimum -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Score minimum (%)
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    v-model.number="activeFilters.score_min"
                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="0"
                                />
                            </div>
                            
                            <!-- Filtre par score maximum -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Score maximum (%)
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    v-model.number="activeFilters.score_max"
                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="100"
                                />
                            </div>
                            
                            <!-- Boutons d'action des filtres -->
                            <div class="flex items-end space-x-3 sm:col-span-2 md:col-span-3 lg:col-span-4">
                                <button
                                    @click="resetFilters"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600"
                                >
                                    Réinitialiser les filtres
                                </button>
                                <button
                                    @click="showFilters = false"
                                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    Appliquer les filtres
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Liste des sessions de quiz -->
                <div class="overflow-hidden bg-white shadow sm:rounded-md dark:bg-gray-800">
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- Aucun résultat -->
                        <li v-if="filteredSessions.length === 0" class="p-6 text-center">
                            <i class="text-4xl text-gray-400 fas fa-inbox"></i>
                            <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">
                                Aucune session trouvée
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Aucune session de quiz ne correspond à vos critères de recherche.
                            </p>
                            <button
                                @click="resetFilters"
                                class="inline-flex items-center px-4 py-2 mt-4 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                <i class="mr-2 fas fa-undo"></i>
                                Réinitialiser les filtres
                            </button>
                        </li>
                        
                        <!-- Sessions de quiz -->
                        <li v-for="session in filteredSessions" :key="session.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <Link 
                                :href="route('quiz.results', { session: session.id })" 
                                class="block hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <div class="px-4 py-4 sm:px-6">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span 
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="getDifficultyClass(session.difficulty)"
                                            >
                                                {{ getDifficultyLabel(session.difficulty) }}
                                            </span>
                                            <span class="ml-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                                                {{ session.category?.name || 'Catégorie inconnue' }}
                                            </span>
                                        </div>
                                        <div class="flex-shrink-0 ml-2">
                                            <span 
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium"
                                                :class="{
                                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': session.score >= 80,
                                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': session.score >= 50 && session.score < 80,
                                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': session.score < 50
                                                }"
                                            >
                                                {{ session.score }}%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-2 sm:flex sm:justify-between">
                                        <div class="sm:flex">
                                            <p class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                                <i class="mr-1.5 fas fa-check-circle text-gray-500"></i>
                                                {{ session.correct_answers }} bonnes réponses sur {{ session.question_count }}
                                            </p>
                                        </div>
                                        <div class="flex items-center mt-2 text-sm text-gray-500 dark:text-gray-400 sm:mt-0">
                                            <p>
                                                <i class="mr-1.5 fas fa-clock text-gray-500"></i>
                                                {{ formatDate(session.completed_at) }}
                                                <span v-if="session.duration_seconds" class="ml-2">
                                                    • {{ formatDuration(session.duration_seconds) }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </li>
                    </ul>
                    
                    <!-- Pagination -->
                    <div 
                        v-if="quizSessions.length > 0" 
                        class="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-200 sm:px-6 dark:bg-gray-800 dark:border-gray-700"
                    >
                        <div class="flex justify-between flex-1 sm:hidden">
                            <a href="#" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                                Précédent
                            </a>
                            <a href="#" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                                Suivant
                            </a>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    Affichage de
                                    <span class="font-medium">1</span>
                                    à
                                    <span class="font-medium">{{ Math.min(filteredSessions.length, 10) }}</span>
                                    sur
                                    <span class="font-medium">{{ filteredSessions.length }}</span>
                                    résultats
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                    <a href="#" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                                        <span class="sr-only">Précédent</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <!-- Current: "z-10 bg-indigo-50 border-indigo-500 text-indigo-600", Default: "bg-white border-gray-300 text-gray-500 hover:bg-gray-50" -->
                                    <a href="#" aria-current="page" class="relative z-10 inline-flex items-center px-4 py-2 text-sm font-medium border border-indigo-500 bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:border-indigo-700 dark:text-indigo-200">
                                        1
                                    </a>
                                    <a href="#" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                                        2
                                    </a>
                                    <a href="#" class="relative items-center hidden px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50 md:inline-flex dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                                        3
                                    </a>
                                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400">
                                        ...
                                    </span>
                                    <a href="#" class="relative items-center hidden px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50 md:inline-flex dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                                        8
                                    </a>
                                    <a href="#" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                                        <span class="sr-only">Suivant</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Animation pour le panneau des filtres */
.filter-panel-enter-active,
.filter-panel-leave-active {
    transition: all 0.3s ease;
    max-height: 1000px;
    overflow: hidden;
}

.filter-panel-enter-from,
.filter-panel-leave-to {
    opacity: 0;
    max-height: 0;
    margin-bottom: 0;
    padding-top: 0;
    padding-bottom: 0;
    border: none;
}

/* Animation pour les éléments de la liste */
.list-item {
    transition: all 0.3s ease;
}

.list-item:hover {
    transform: translateX(5px);
}

/* Style personnalisé pour la barre de défilement */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Mode sombre pour la barre de défilement */
.dark ::-webkit-scrollbar-track {
    background: #374151;
}

.dark ::-webkit-scrollbar-thumb {
    background: #4b5563;
}

.dark ::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}
</style>
