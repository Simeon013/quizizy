<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    categories: {
        type: Array,
        required: true
    },
    difficultyLevels: {
        type: Array,
        required: true
    }
});

// État du formulaire
const form = useForm({
    question_text: '',
    category_id: props.categories.length > 0 ? props.categories[0].id : '',
    difficulty: 'medium',
    explanation: '',
    is_active: true,
    answers: [
        { text: '', is_correct: true },
        { text: '', is_correct: false },
    ]
});

// Ajouter une nouvelle réponse
const addAnswer = () => {
    if (form.answers.length < 6) {
        form.answers.push({ text: '', is_correct: false });
    }
};

// Supprimer une réponse
const removeAnswer = (index) => {
    if (form.answers.length > 2) {
        form.answers.splice(index, 1);
    }
};

// Basculer l'état d'une réponse (correcte/incorrecte)
const toggleCorrect = (index) => {
    form.answers = form.answers.map((answer, i) => ({
        ...answer,
        is_correct: i === index
    }));
};

// Vérifier si le formulaire est valide
const isFormValid = computed(() => {
    return form.question_text.trim() !== '' &&
           form.answers.every(a => a.text.trim() !== '') &&
           form.answers.some(a => a.is_correct);
});

// Soumettre le formulaire
const submit = () => {
    if (isFormValid.value) {
        form.post(route('admin.questions.store'), {
            onSuccess: () => {
                // Réinitialiser le formulaire après une création réussie
                form.reset();
                form.answers = [
                    { text: '', is_correct: true },
                    { text: '', is_correct: false },
                ];
            },
        });
    }
};
</script>

<template>
    <Head title="Créer une question" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Créer une nouvelle question
                </h2>
                <Link
                    :href="route('admin.questions.index')"
                    class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                >
                    Retour à la liste
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Texte de la question -->
                            <div>
                                <label for="question_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Question *
                                </label>
                                <div class="mt-1">
                                    <textarea
                                        id="question_text"
                                        v-model="form.question_text"
                                        rows="3"
                                        class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        placeholder="Écrivez votre question ici..."
                                        required
                                    ></textarea>
                                </div>
                                <p v-if="form.errors.question_text" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.question_text }}
                                </p>
                            </div>

                            <!-- Catégorie et difficulté -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Catégorie -->
                                <div>
                                    <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Catégorie *
                                    </label>
                                    <select
                                        id="category_id"
                                        v-model="form.category_id"
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        required
                                    >
                                        <option v-for="category in categories" :key="category.id" :value="category.id">
                                            {{ category.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Difficulté -->
                                <div>
                                    <label for="difficulty" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Niveau de difficulté *
                                    </label>
                                    <select
                                        id="difficulty"
                                        v-model="form.difficulty"
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    >
                                        <option v-for="level in difficultyLevels" :key="level.value" :value="level.value">
                                            {{ level.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Explication (optionnelle) -->
                            <div>
                                <label for="explanation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Explication (optionnel)
                                </label>
                                <div class="mt-1">
                                    <textarea
                                        id="explanation"
                                        v-model="form.explanation"
                                        rows="2"
                                        class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        placeholder="Ajoutez une explication ou un indice..."
                                    ></textarea>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Cette explication sera affichée à l'utilisateur après avoir répondu à la question.
                                </p>
                            </div>

                            <!-- Statut -->
                            <div class="flex items-center">
                                <input
                                    id="is_active"
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600"
                                >
                                <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                    Question active
                                </label>
                            </div>

                            <!-- Réponses -->
                            <div class="mt-8">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                        Réponses *
                                    </h3>
                                    <button
                                        v-if="form.answers.length < 6"
                                        type="button"
                                        @click="addAnswer"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50"
                                    >
                                        <i class="fas fa-plus mr-1"></i>
                                        Ajouter une réponse
                                    </button>
                                </div>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Cochez la bonne réponse. Vous pouvez ajouter jusqu'à 6 réponses.
                                </p>

                                <div class="mt-4 space-y-3">
                                    <div v-for="(answer, index) in form.answers" :key="index" class="flex items-start">
                                        <div class="flex items-center h-5 mt-2.5">
                                            <input
                                                :id="`answer-${index}-correct`"
                                                type="radio"
                                                :checked="answer.is_correct"
                                                @change="toggleCorrect(index)"
                                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300"
                                            >
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <div class="relative rounded-md shadow-sm">
                                                <input
                                                    :id="`answer-${index}-text`"
                                                    v-model="answer.text"
                                                    type="text"
                                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                    :placeholder="`Réponse ${index + 1}`"
                                                    required
                                                >
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                    <button
                                                        v-if="form.answers.length > 2"
                                                        type="button"
                                                        @click="removeAnswer(index)"
                                                        class="text-red-500 hover:text-red-700 focus:outline-none"
                                                        title="Supprimer cette réponse"
                                                    >
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="form.errors.answers" class="mt-2 text-sm text-red-600">
                                    {{ form.errors.answers }}
                                </p>
                            </div>

                            <!-- Boutons d'action -->
                            <div class="pt-5">
                                <div class="flex justify-between">
                                    <Link
                                        :href="route('admin.questions.index')"
                                        class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600"
                                    >
                                        Annuler
                                    </Link>
                                    <button
                                        type="submit"
                                        :disabled="form.processing || !isFormValid"
                                        class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                        :class="{ 'opacity-50 cursor-not-allowed': !isFormValid }"
                                    >
                                        <span v-if="form.processing">
                                            <i class="fas fa-spinner fa-spin mr-2"></i>
                                            Création en cours...
                                        </span>
                                        <span v-else>
                                            <i class="fas fa-save mr-2"></i>
                                            Créer la question
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
