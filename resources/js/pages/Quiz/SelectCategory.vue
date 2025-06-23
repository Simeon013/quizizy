<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    categories: {
        type: Array,
        required: true
    },
    userStats: {
        type: Object,
        default: null
    }
});

// Options pour le nombre de questions
const questionCounts = [5, 10, 15, 20];

// Niveaux de difficulté
const difficultyLevels = [
    { value: 'all', label: 'Tous les niveaux' },
    { value: 'easy', label: 'Facile' },
    { value: 'medium', label: 'Moyen' },
    { value: 'hard', label: 'Difficile' }
];

// État du formulaire
const form = {
    question_count: 10,
    difficulty: 'all'
};

// Vérifier si l'utilisateur a un niveau
const userLevel = computed(() => {
    return props.userStats?.level || 1;
});

// Calculer la progression vers le prochain niveau
const progressToNextLevel = computed(() => {
    if (!props.userStats) return 0;
    const currentXp = props.userStats.xp % 1000;
    return (currentXp / 1000) * 100;
});

// Formater le temps de jeu
const formatPlayTime = (minutes) => {
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h${mins > 0 ? ` ${mins}min` : ''}`;
};

// Obtenir la classe de couleur en fonction de la catégorie
const getCategoryColor = (color) => {
    const colors = {
        'red': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'blue': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'green': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'yellow': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'indigo': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        'purple': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        'pink': 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
        'gray': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    };
    return colors[color] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
};
</script>

<template>
    <Head title="Sélectionnez une catégorie" />
    
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                    Choisissez une catégorie
                </h2>
                <div class="flex items-center space-x-4">
                    <Link 
                        :href="route('quiz.history')" 
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        <i class="mr-2 fas fa-history"></i>
                        Historique
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
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Statistiques utilisateur (si connecté) -->
                <div v-if="userStats" class="p-6 mb-8 bg-white rounded-lg shadow dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">
                        Vos statistiques
                    </h3>
                    <div class="grid grid-cols-1 gap-5 mt-5 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Niveau actuel -->
                        <div class="px-4 py-5 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-700 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 p-3 bg-indigo-500 rounded-md">
                                    <i class="text-white fas fa-trophy"></i>
                                </div>
                                <div class="flex-1 w-0 ml-5">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-300">
                                            Niveau actuel
                                        </dt>
                                        <dd class="flex items-baseline">
                                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                                {{ userStats.level }}
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-600">
                                    <div 
                                        class="h-2.5 rounded-full bg-indigo-600" 
                                        :style="`width: ${progressToNextLevel}%`"
                                    ></div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ userStats.xp % 1000 }} / 1000 XP pour le niveau {{ userStats.level + 1 }}
                                </p>
                            </div>
                        </div>

                        <!-- XP totale -->
                        <div class="px-4 py-5 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-700 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 p-3 bg-green-500 rounded-md">
                                    <i class="text-white fas fa-star"></i>
                                </div>
                                <div class="flex-1 w-0 ml-5">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-300">
                                            XP totale
                                        </dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                                {{ userStats.xp.toLocaleString() }}
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Quiz terminés -->
                        <div class="px-4 py-5 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-700 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 p-3 bg-blue-500 rounded-md">
                                    <i class="text-white fas fa-check-circle"></i>
                                </div>
                                <div class="flex-1 w-0 ml-5">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-300">
                                            Quiz terminés
                                        </dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                                {{ userStats.quizzes_completed || 0 }}
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Temps de jeu -->
                        <div class="px-4 py-5 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-700 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 p-3 bg-yellow-500 rounded-md">
                                    <i class="text-white fas fa-clock"></i>
                                </div>
                                <div class="flex-1 w-0 ml-5">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-300">
                                            Temps de jeu
                                        </dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                                {{ formatPlayTime(userStats.play_time_minutes || 0) }}
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="p-6 mb-8 bg-white rounded-lg shadow dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">
                        Options du quiz
                    </h3>
                    <div class="grid grid-cols-1 gap-6 mt-4 md:grid-cols-2">
                        <!-- Nombre de questions -->
                        <div>
                            <label for="question_count" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Nombre de questions
                            </label>
                            <select
                                id="question_count"
                                v-model="form.question_count"
                                class="block w-full px-3 py-2 mt-1 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option v-for="count in questionCounts" :key="count" :value="count">
                                    {{ count }} questions
                                </option>
                            </select>
                        </div>

                        <!-- Niveau de difficulté -->
                        <div>
                            <label for="difficulty" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Niveau de difficulté
                            </label>
                            <select
                                id="difficulty"
                                v-model="form.difficulty"
                                class="block w-full px-3 py-2 mt-1 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option v-for="level in difficultyLevels" :key="level.value" :value="level.value">
                                    {{ level.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Liste des catégories -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div 
                        v-for="category in categories" 
                        :key="category.id"
                        class="overflow-hidden transition-all duration-200 bg-white rounded-lg shadow dark:bg-gray-800 hover:shadow-lg hover:-translate-y-1"
                    >
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div 
                                        class="flex items-center justify-center flex-shrink-0 w-12 h-12 rounded-full"
                                        :class="getCategoryColor(category.color)"
                                    >
                                        <i :class="category.icon + ' text-xl'"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ category.name }}
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ category.questions_count }} questions disponibles
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span 
                                        v-if="category.difficulty" 
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': category.difficulty === 'easy',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': category.difficulty === 'medium',
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': category.difficulty === 'hard',
                                        }"
                                    >
                                        {{ category.difficulty === 'easy' ? 'Facile' : category.difficulty === 'medium' ? 'Moyen' : 'Difficile' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <Link 
                                    :href="route('quiz.start', { category: category.slug })"
                                    :data="{ question_count: form.question_count, difficulty: form.difficulty === 'all' ? null : form.difficulty }"
                                    method="post"
                                    as="button"
                                    class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    :class="{ 'opacity-50 cursor-not-allowed': category.questions_count < 5 }"
                                    :disabled="category.questions_count < 5"
                                >
                                    <i class="mr-2 fas fa-play"></i>
                                    {{ category.questions_count < 5 ? 'Pas assez de questions' : 'Commencer le quiz' }}
                                </Link>
                            </div>
                            
                            <div v-if="category.user_stats" class="mt-4">
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>Meilleur score</span>
                                    <span class="font-medium">{{ category.user_stats.best_score || 0 }}%</span>
                                </div>
                                <div class="w-full h-1.5 mt-1 bg-gray-200 rounded-full dark:bg-gray-700">
                                    <div 
                                        class="h-1.5 rounded-full" 
                                        :class="{
                                            'bg-red-500': category.user_stats.best_score < 50,
                                            'bg-yellow-500': category.user_stats.best_score >= 50 && category.user_stats.best_score < 80,
                                            'bg-green-500': category.user_stats.best_score >= 80
                                        }"
                                        :style="`width: ${category.user_stats.best_score || 0}%`"
                                    ></div>
                                </div>
                                <div class="flex justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ category.user_stats.quizzes_completed || 0 }} quiz</span>
                                    <span>Niv. {{ category.user_stats.level || 1 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aucune catégorie disponible -->
                <div 
                    v-if="categories.length === 0" 
                    class="p-8 text-center bg-white rounded-lg shadow dark:bg-gray-800"
                >
                    <i class="text-4xl text-gray-400 fas fa-inbox"></i>
                    <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">
                        Aucune catégorie disponible
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Aucune catégorie avec suffisamment de questions n'est disponible pour le moment.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Styles spécifiques à ce composant */
.category-card {
    transition: all 0.2s ease-in-out;
}

.category-card:hover {
    transform: translateY(-2px);
}
</style>
