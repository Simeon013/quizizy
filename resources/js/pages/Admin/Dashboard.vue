<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
        default: () => ({
            users: { total: 0, new_this_month: 0 },
            categories: { total: 0, active: 0 },
            questions: { total: 0, per_category: [] }
        })
    }
});

// Formater les nombres avec des séparateurs de milliers
const formatNumber = (num: number): string => {
    return new Intl.NumberFormat('fr-FR').format(num || 0);
};

// Couleurs pour les graphiques
const chartColors = [
    '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899',
    '#14B8A6', '#F97316', '#6366F1', '#D946EF', '#84CC16'
];

// Calculer la hauteur maximale des barres
const maxQuestions = computed(() => {
    if (!props.stats.questions?.per_category?.length) return 1;
    return Math.max(...props.stats.questions.per_category.map(c => c.questions_count || 0));
});
</script>

<template>
    <Head title="Tableau de bord" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Tableau de bord
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Cartes de statistiques -->
                <div class="grid grid-cols-1 gap-5 mt-6 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Nombre total d'utilisateurs -->
                    <div class="p-5 bg-white rounded-lg shadow dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full text-blue-500 bg-blue-100 dark:bg-blue-900/30">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <div class="ml-5">
                                <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">
                                    {{ formatNumber(stats.users?.total || 0) }}
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Utilisateurs</p>
                            </div>
                        </div>
                        <div class="mt-4 text-sm text-green-500 font-medium">
                            <span>+{{ stats.users?.new_this_month || 0 }} ce mois-ci</span>
                        </div>
                    </div>

                    <!-- Nombre total de catégories -->
                    <div class="p-5 bg-white rounded-lg shadow dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full text-green-500 bg-green-100 dark:bg-green-900/30">
                                <i class="fas fa-folder text-xl"></i>
                            </div>
                            <div class="ml-5">
                                <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">
                                    {{ formatNumber(stats.categories?.total || 0) }}
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Catégories</p>
                            </div>
                        </div>
                        <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            <span>{{ stats.categories?.active || 0 }} actives</span>
                        </div>
                    </div>

                    <!-- Nombre total de questions -->
                    <div class="p-5 bg-white rounded-lg shadow dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full text-yellow-500 bg-yellow-100 dark:bg-yellow-900/30">
                                <i class="fas fa-question-circle text-xl"></i>
                            </div>
                            <div class="ml-5">
                                <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">
                                    {{ formatNumber(stats.questions?.total || 0) }}
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Questions</p>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques de jeu -->
                    <div class="p-5 bg-white rounded-lg shadow dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full text-purple-500 bg-purple-100 dark:bg-purple-900/30">
                                <i class="fas fa-trophy text-xl"></i>
                            </div>
                            <div class="ml-5">
                                <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">
                                    {{ formatNumber(0) }}
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Parties jouées</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphique des questions par catégorie -->
                <div class="mt-8" v-if="stats.questions?.per_category?.length">
                    <div class="bg-white shadow rounded-lg p-6 dark:bg-gray-800">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Répartition des questions par catégorie
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Graphique en barres -->
                            <div class="h-64">
                                <div class="h-full flex items-end space-x-1">
                                    <div v-for="(category, index) in stats.questions.per_category"
                                         :key="category.id"
                                         class="flex-1 flex flex-col items-center min-w-0"
                                    >
                                        <div class="flex-1 w-full flex items-end">
                                            <div
                                                class="w-full rounded-t-sm min-h-[4px]"
                                                :style="{
                                                    height: `${((category.questions_count || 0) / maxQuestions) * 80}%`,
                                                    backgroundColor: chartColors[index % chartColors.length]
                                                }"
                                            ></div>
                                        </div>
                                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center truncate w-full px-1">
                                            {{ category.name }}
                                        </div>
                                        <div class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                            {{ category.questions_count || 0 }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Légende -->
                            <div class="space-y-3">
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Détails par catégorie :
                                </h4>
                                <ul class="space-y-2 max-h-48 overflow-y-auto">
                                    <li v-for="(category, index) in stats.questions.per_category"
                                        :key="category.id"
                                        class="flex items-center"
                                    >
                                        <span
                                            class="w-3 h-3 rounded-full mr-2 flex-shrink-0"
                                            :style="{ backgroundColor: chartColors[index % chartColors.length] }"
                                        ></span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300 truncate">
                                            {{ category.name }}
                                        </span>
                                        <span class="ml-auto text-sm font-medium flex-shrink-0">
                                            {{ category.questions_count || 0 }} questions
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message si pas de données -->
                <div class="mt-8" v-else>
                    <div class="bg-white shadow rounded-lg p-6 dark:bg-gray-800">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Répartition des questions par catégorie
                        </h3>
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-chart-bar text-4xl mb-4"></i>
                            <p>Aucune donnée disponible</p>
                            <p class="text-sm mt-2">Ajoutez des catégories et des questions pour voir les statistiques</p>
                        </div>
                    </div>
                </div>

                <!-- Dernières activités (à implémenter) -->
                <div class="mt-8">
                    <div class="bg-white shadow rounded-lg p-6 dark:bg-gray-800">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Activité récente
                        </h3>
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-clock text-4xl mb-4"></i>
                            <p>Les activités récentes s'afficheront ici</p>
                            <p class="text-sm mt-2">(À implémenter)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Styles spécifiques au composant */
</style>
