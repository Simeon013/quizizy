<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    questions: {
        type: Object,
        required: true
    }
});

// Options de tri
const sortOptions = [
    { name: 'Plus récentes', value: 'newest' },
    { name: 'Plus anciennes', value: 'oldest' },
    { name: 'Texte (A-Z)', value: 'text_asc' },
    { name: 'Texte (Z-A)', value: 'text_desc' },
];

// Filtres
const filters = ref({
    search: '',
    category: '',
    difficulty: '',
    status: '',
    sort: 'newest',
});

// Catégories uniques pour le filtre
const categories = computed(() => {
    return [...new Set(props.questions.data.map(q => q.category?.name).filter(Boolean))];
});

// Difficultés uniques pour le filtre
const difficulties = [
    { value: 'easy', label: 'Facile' },
    { value: 'medium', label: 'Moyen' },
    { value: 'hard', label: 'Difficile' },
];

// Statuts pour le filtre
const statuses = [
    { value: 'active', label: 'Actives' },
    { value: 'inactive', label: 'Inactives' },
];

// Formater la date
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Obtenir la classe de couleur pour la difficulté
const getDifficultyColor = (difficulty) => {
    const colors = {
        easy: 'bg-green-100 text-green-800',
        medium: 'bg-yellow-100 text-yellow-800',
        hard: 'bg-red-100 text-red-800',
    };
    return colors[difficulty] || 'bg-gray-100 text-gray-800';
};

// Obtenir le libellé de la difficulté
const getDifficultyLabel = (difficulty) => {
    const labels = {
        easy: 'Facile',
        medium: 'Moyen',
        hard: 'Difficile',
    };
    return labels[difficulty] || difficulty;
};

// Supprimer une question
const deleteQuestion = (question) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer la question : "${question.question_text}" ?`)) {
        router.delete(route('admin.questions.destroy', question.id), {
            preserveScroll: true,
            onSuccess: () => {
                // La suppression est gérée par Inertia
            },
        });
    }
};
</script>

<template>
    <Head title="Gestion des questions" />
    
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Gestion des questions
                </h2>
                <Link 
                    :href="route('admin.questions.create')" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring focus:ring-blue-300 disabled:opacity-25 transition"
                >
                    <i class="fas fa-plus mr-2"></i>
                    Nouvelle question
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filtres -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                        <!-- Recherche -->
                        <div class="lg:col-span-2">
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Rechercher
                            </label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input
                                    type="text"
                                    id="search"
                                    v-model="filters.search"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="Rechercher une question..."
                                >
                            </div>
                        </div>

                        <!-- Filtre par catégorie -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Catégorie
                            </label>
                            <select
                                id="category"
                                v-model="filters.category"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option value="">Toutes les catégories</option>
                                <option v-for="category in categories" :key="category" :value="category">
                                    {{ category }}
                                </option>
                            </select>
                        </div>

                        <!-- Filtre par difficulté -->
                        <div>
                            <label for="difficulty" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Difficulté
                            </label>
                            <select
                                id="difficulty"
                                v-model="filters.difficulty"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option value="">Tous les niveaux</option>
                                <option v-for="diff in difficulties" :key="diff.value" :value="diff.value">
                                    {{ diff.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Filtre par statut -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Statut
                            </label>
                            <select
                                id="status"
                                v-model="filters.status"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option value="">Tous les statuts</option>
                                <option v-for="status in statuses" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Tri -->
                        <div>
                            <label for="sort" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Trier par
                            </label>
                            <select
                                id="sort"
                                v-model="filters.sort"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                    {{ option.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Liste des questions -->
                <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Question
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Catégorie
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Difficulté
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Réponses
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Créée le
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="question in questions.data" :key="question.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ question.question_text }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div v-if="question.category" 
                                                 class="h-4 w-4 rounded-full mr-2"
                                                 :style="{ backgroundColor: question.category.color }">
                                            </div>
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                {{ question.category?.name || 'Sans catégorie' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                              :class="getDifficultyColor(question.difficulty)">
                                            {{ getDifficultyLabel(question.difficulty) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ question.answers_count }} 
                                        <span class="text-green-600 dark:text-green-400 font-medium">
                                            ({{ question.answers_count > 0 ? Math.round((question.answers.filter(a => a.is_correct).length / question.answers_count) * 100) : 0 }}% correctes)
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="question.is_active" 
                                              class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Active
                                        </span>
                                        <span v-else 
                                              class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Inactive
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ formatDate(question.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <Link 
                                                :href="route('admin.questions.edit', question.id)" 
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                title="Modifier"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </Link>
                                            <button
                                                @click="deleteQuestion(question)"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                title="Supprimer"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="questions.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Aucune question trouvée.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="questions.links.length > 3" class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 sm:px-6">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    Affichage de
                                    <span class="font-medium">{{ questions.from }}-{{ questions.to }}</span>
                                    sur
                                    <span class="font-medium">{{ questions.total }}</span>
                                    résultats
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <template v-for="(link, i) in questions.links" :key="i">
                                        <Link 
                                            v-if="link.url"
                                            :href="link.url"
                                            v-html="link.label"
                                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium"
                                            :class="{
                                                'bg-blue-50 border-blue-500 text-blue-600 dark:bg-blue-900/30 dark:border-blue-600 dark:text-blue-300 z-10': link.active,
                                                'bg-white dark:bg-gray-800 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700': !link.active,
                                                'rounded-l-md': i === 0,
                                                'rounded-r-md': i === questions.links.length - 1
                                            }"
                                        />
                                        <span v-else 
                                              v-html="link.label"
                                              class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        </span>
                                    </template>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
