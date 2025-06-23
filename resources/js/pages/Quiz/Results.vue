<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    quizSession: {
        type: Object,
        required: true
    },
    userStats: {
        type: Object,
        required: true
    }
});

// Calculer le temps moyen par question
const averageTimePerQuestion = computed(() => {
    if (!props.quizSession.session_questions || props.quizSession.session_questions.length === 0) return 0;
    
    const totalTime = props.quizSession.session_questions
        .filter(q => q.time_taken)
        .reduce((sum, q) => sum + q.time_taken, 0);
    
    return Math.round(totalTime / props.quizSession.session_questions.length);
});

// Formater le temps (secondes en MM:SS)
const formatTime = (seconds) => {
    if (!seconds) return '--:--';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

// Formater la date
const formatDate = (dateString) => {
    if (!dateString) return '';
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
};

// Obtenir la classe de couleur pour le score
const getScoreColor = (score) => {
    if (score >= 80) return 'text-green-500';
    if (score >= 50) return 'text-yellow-500';
    return 'text-red-500';
};

// Obtenir l'icône pour une réponse
const getAnswerIcon = (isCorrect) => {
    return isCorrect 
        ? 'fas fa-check-circle text-green-500 text-xl'
        : 'fas fa-times-circle text-red-500 text-xl';
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
        default: return 'Inconnu';
    }
};
</script>

<template>
    <Head :title="`Résultats - ${quizSession.category?.name || 'Quiz'}`" />
    
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <Link 
                        :href="route('quiz.history')" 
                        class="mr-4 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                        title="Retour à l'historique"
                    >
                        <i class="fas fa-arrow-left"></i>
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Résultats du quiz
                    </h2>
                </div>
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
            <div class="max-w-5xl px-4 mx-auto sm:px-6 lg:px-8">
                <!-- En-tête des résultats -->
                <div class="p-6 mb-8 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div class="mb-4 md:mb-0">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ quizSession.category?.name || 'Quiz' }}
                            </h1>
                            <p class="text-gray-600 dark:text-gray-300">
                                Complété le {{ formatDate(quizSession.completed_at) }}
                            </p>
                            <div class="mt-2">
                                <span 
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="getDifficultyClass(quizSession.difficulty)"
                                >
                                    {{ getDifficultyLabel(quizSession.difficulty) }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Score principal -->
                        <div class="text-center">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Score</div>
                            <div 
                                class="text-5xl font-bold" 
                                :class="getScoreColor(quizSession.score)"
                            >
                                {{ quizSession.score }}%
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ quizSession.correct_answers || 0 }} bonnes réponses sur {{ quizSession.question_count }}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistiques rapides -->
                    <div class="grid grid-cols-2 gap-4 mt-8 sm:grid-cols-4">
                        <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-700">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Temps moyen</div>
                            <div class="text-xl font-semibold text-gray-900 dark:text-white">
                                {{ formatTime(averageTimePerQuestion) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">par question</div>
                        </div>
                        
                        <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-700">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Temps total</div>
                            <div class="text-xl font-semibold text-gray-900 dark:text-white">
                                {{ formatTime(quizSession.session_questions?.reduce((sum, q) => sum + (q.time_taken || 0), 0)) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">pour le quiz</div>
                        </div>
                        
                        <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-700">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">XP gagnée</div>
                            <div class="text-xl font-semibold text-gray-900 dark:text-white">
                                +{{ quizSession.xp_earned || 0 }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">points d'expérience</div>
                        </div>
                        
                        <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-700">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Niveau</div>
                            <div class="text-xl font-semibold text-gray-900 dark:text-white">
                                {{ userStats.level }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ userStats.xp % 1000 }}/1000 XP pour le niveau {{ userStats.level + 1 }}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Barre de progression du niveau -->
                    <div class="mt-6">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Progression du niveau
                            </span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ Math.round((userStats.xp % 1000) / 10) }}%
                            </span>
                        </div>
                        <div class="w-full h-2 bg-gray-200 rounded-full dark:bg-gray-700">
                            <div 
                                class="h-2 rounded-full bg-indigo-600" 
                                :style="`width: ${(userStats.xp % 1000) / 10}%`"
                            ></div>
                        </div>
                    </div>
                </div>
                
                <!-- Détails des questions -->
                <div class="mb-8 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Détail des réponses
                        </h3>
                    </div>
                    
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div 
                            v-for="(sessionQuestion, index) in quizSession.session_questions" 
                            :key="sessionQuestion.id"
                            class="p-6"
                            :class="{
                                'bg-green-50 dark:bg-green-900/20': sessionQuestion.is_correct,
                                'bg-red-50 dark:bg-red-900/20': !sessionQuestion.is_correct && sessionQuestion.answered'
                            }"
                        >
                            <div class="flex items-start">
                                <!-- Numéro de la question -->
                                <div 
                                    class="flex items-center justify-center flex-shrink-0 w-8 h-8 mr-4 text-sm font-medium rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': sessionQuestion.is_correct,
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': !sessionQuestion.is_correct && sessionQuestion.answered
                                    }"
                                >
                                    {{ index + 1 }}
                                </div>
                                
                                <!-- Contenu de la question -->
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-base font-medium text-gray-900 dark:text-white">
                                            {{ sessionQuestion.question?.question_text || 'Question non disponible' }}
                                        </h4>
                                        <div class="flex items-center ml-4">
                                            <i 
                                                :class="getAnswerIcon(sessionQuestion.is_correct)" 
                                                class="ml-2"
                                            ></i>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-2">
                                        <span 
                                            v-if="sessionQuestion.question?.difficulty"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mr-2"
                                            :class="getDifficultyClass(sessionQuestion.question.difficulty)"
                                        >
                                            {{ getDifficultyLabel(sessionQuestion.question.difficulty) }}
                                        </span>
                                        <span 
                                            v-if="sessionQuestion.time_taken"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium text-gray-700 bg-gray-100 dark:bg-gray-700 dark:text-gray-300"
                                        >
                                            <i class="mr-1 text-gray-500 fas fa-clock"></i>
                                            {{ formatTime(sessionQuestion.time_taken) }}
                                        </span>
                                    </div>
                                    
                                    <!-- Réponses -->
                                    <div class="mt-4 space-y-2">
                                        <div 
                                            v-for="answer in sessionQuestion.question?.answers || []" 
                                            :key="answer.id"
                                            class="p-3 border rounded-md"
                                            :class="{
                                                'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-900/30': answer.is_correct,
                                                'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-900/30': sessionQuestion.answer_id === answer.id && !answer.is_correct,
                                                'border-gray-200 dark:border-gray-700': !answer.is_correct && sessionQuestion.answer_id !== answer.id
                                            }"
                                        >
                                            <div class="flex items-center">
                                                <div 
                                                    class="flex items-center justify-center flex-shrink-0 w-5 h-5 mr-3 border rounded-full"
                                                    :class="{
                                                        'border-green-500 bg-green-500': answer.is_correct,
                                                        'border-red-500 bg-white dark:bg-gray-800': sessionQuestion.answer_id === answer.id && !answer.is_correct,
                                                        'border-gray-300 bg-white dark:bg-gray-800': !answer.is_correct && sessionQuestion.answer_id !== answer.id
                                                    }"
                                                >
                                                    <i 
                                                        v-if="answer.is_correct || sessionQuestion.answer_id === answer.id"
                                                        class="text-xs text-white"
                                                        :class="answer.is_correct ? 'fas fa-check' : 'fas fa-times'"
                                                    ></i>
                                                </div>
                                                <span 
                                                    class="text-sm"
                                                    :class="{
                                                        'font-medium text-green-800 dark:text-green-200': answer.is_correct,
                                                        'font-medium text-red-800 dark:text-red-200': sessionQuestion.answer_id === answer.id && !answer.is_correct,
                                                        'text-gray-700 dark:text-gray-300': !answer.is_correct && sessionQuestion.answer_id !== answer.id
                                                    }"
                                                >
                                                    {{ answer.answer_text }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Explication -->
                                    <div 
                                        v-if="sessionQuestion.question?.explanation"
                                        class="p-3 mt-4 text-sm text-gray-700 bg-gray-100 border border-gray-200 rounded-md dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300"
                                    >
                                        <p class="font-medium">
                                            <i class="mr-1 text-blue-500 fas fa-info-circle"></i>
                                            Explication :
                                        </p>
                                        <p class="mt-1">{{ sessionQuestion.question.explanation }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="flex flex-col space-y-3 sm:flex-row sm:space-y-0 sm:space-x-3">
                    <Link 
                        :href="route('quiz.select-category')" 
                        class="inline-flex items-center justify-center px-6 py-3 text-base font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <i class="mr-2 fas fa-home"></i>
                        Retour à l'accueil
                    </Link>
                    
                    <Link 
                        v-if="quizSession.category"
                        :href="route('quiz.start', { category: quizSession.category.slug })" 
                        class="inline-flex items-center justify-center px-6 py-3 text-base font-medium text-indigo-700 bg-indigo-100 border border-transparent rounded-md hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <i class="mr-2 fas fa-redo"></i>
                        Rejouer ce quiz
                    </Link>
                    
                    <Link 
                        :href="route('quiz.leaderboard')" 
                        class="inline-flex items-center justify-center px-6 py-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:bg-gray-700"
                    >
                        <i class="mr-2 fas fa-trophy"></i>
                        Voir le classement
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Animation pour les réponses */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Appliquer l'animation aux questions */
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}

/* Délai d'animation pour chaque question */
.question-item {
    opacity: 0;
    animation: fadeIn 0.3s ease-out forwards;
}

.question-item:nth-child(1) { animation-delay: 0.1s; }
.question-item:nth-child(2) { animation-delay: 0.2s; }
.question-item:nth-child(3) { animation-delay: 0.3s; }
.question-item:nth-child(4) { animation-delay: 0.4s; }
.question-item:nth-child(5) { animation-delay: 0.5s; }
</style>
