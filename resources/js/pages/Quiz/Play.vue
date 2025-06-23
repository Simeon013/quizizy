<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';

const props = defineProps({
    category: {
        type: Object,
        required: true
    },
    questions: {
        type: Array,
        required: true
    },
    questionCount: {
        type: Number,
        required: true
    },
    timeLimit: {
        type: Number,
        default: 0
    }
});

// État du quiz
const currentQuestionIndex = ref(0);
const selectedAnswerId = ref(null);
const showExplanation = ref(false);
const isSubmitting = ref(false);
const timeLeft = ref(props.timeLimit);
const timer = ref(null);
const quizCompleted = ref(false);
const score = ref(0);
const correctAnswers = ref(0);
const xpEarned = ref(0);
const levelUp = ref(null);

// Référence pour le conteneur de la question (pour le défilement)
const questionContainer = ref(null);

// Question actuelle
const currentQuestion = computed(() => {
    return props.questions[currentQuestionIndex.value];
});

// Progression du quiz
const progress = computed(() => {
    return ((currentQuestionIndex.value) / props.questions.length) * 100;
});

// Vérifier si une réponse est sélectionnée
const hasSelectedAnswer = computed(() => {
    return selectedAnswerId.value !== null;
});

// Formater le temps restant (MM:SS)
const formattedTime = computed(() => {
    const minutes = Math.floor(timeLeft.value / 60);
    const seconds = timeLeft.value % 60;
    return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
});

// Démarrer le compte à rebours
const startTimer = () => {
    if (props.timeLimit <= 0) return;
    
    timeLeft.value = props.timeLimit;
    
    timer.value = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--;
        } else {
            // Temps écoulé, passer à la question suivante
            clearInterval(timer.value);
            handleNextQuestion();
        }
    }, 1000);
};

// Réinitialiser le minuteur
const resetTimer = () => {
    if (timer.value) {
        clearInterval(timer.value);
        timer.value = null;
    }
};

// Vérifier la réponse sélectionnée
const checkAnswer = () => {
    if (isSubmitting.value || !hasSelectedAnswer.value) return;
    
    isSubmitting.value = true;
    const selectedAnswer = currentQuestion.value.answers.find(a => a.id === selectedAnswerId.value);
    const isCorrect = selectedAnswer ? selectedAnswer.is_correct : false;
    
    // Mettre à jour le score
    if (isCorrect) {
        correctAnswers.value++;
    }
    
    // Afficher l'explication si elle existe
    showExplanation.value = true;
    
    // Envoyer la réponse au serveur
    submitAnswer(selectedAnswerId.value);
};

// Soumettre la réponse au serveur
const submitAnswer = async (answerId) => {
    try {
        const response = await axios.post(route('quiz.submit-answer'), {
            question_id: currentQuestion.value.id,
            answer_id: answerId,
            time_taken: props.timeLimit > 0 ? (props.timeLimit - timeLeft.value) : null
        });
        
        // Mettre à jour l'état avec la réponse du serveur
        if (response.data) {
            // Mettre à jour la question avec la réponse correcte
            const questionIndex = props.questions.findIndex(q => q.id === currentQuestion.value.id);
            if (questionIndex !== -1) {
                props.questions[questionIndex].correct_answer_id = response.data.correct_answer_id;
                props.questions[questionIndex].explanation = response.data.explanation;
            }
        }
    } catch (error) {
        console.error('Erreur lors de la soumission de la réponse:', error);
    } finally {
        isSubmitting.value = false;
    }
};

// Passer à la question suivante
const handleNextQuestion = () => {
    // Réinitialiser l'état pour la prochaine question
    selectedAnswerId.value = null;
    showExplanation.value = false;
    
    // Vérifier s'il reste des questions
    if (currentQuestionIndex.value < props.questions.length - 1) {
        currentQuestionIndex.value++;
        
        // Faire défiler vers le haut de la question
        if (questionContainer.value) {
            questionContainer.value.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Redémarrer le minuteur si nécessaire
        if (props.timeLimit > 0) {
            resetTimer();
            startTimer();
        }
    } else {
        // Terminer le quiz
        finishQuiz();
    }
};

// Terminer le quiz
const finishQuiz = async () => {
    try {
        const response = await axios.post(route('quiz.complete'));
        
        if (response.data) {
            // Mettre à jour l'état avec les résultats du quiz
            quizCompleted.value = true;
            score.value = response.data.score;
            correctAnswers.value = response.data.correct_answers;
            xpEarned.value = response.data.xp_earned;
            levelUp.value = response.data.level_up || null;
            
            // Arrêter le minuteur
            resetTimer();
        }
    } catch (error) {
        console.error('Erreur lors de la finalisation du quiz:', error);
    }
};

// Quitter le quiz
const quitQuiz = () => {
    if (confirm('Êtes-vous sûr de vouloir quitter le quiz ? Votre progression sera perdue.')) {
        router.visit(route('quiz.select-category'));
    }
};

// Démarrer le quiz
onMounted(() => {
    if (props.timeLimit > 0) {
        startTimer();
    }
    
    // Ajouter un gestionnaire d'événements pour empêcher de quitter la page pendant le quiz
    window.addEventListener('beforeunload', handleBeforeUnload);
});

// Nettoyer à la destruction du composant
onBeforeUnmount(() => {
    resetTimer();
    window.removeEventListener('beforeunload', handleBeforeUnload);
});

// Gérer la tentative de quitter la page
const handleBeforeUnload = (e) => {
    if (!quizCompleted.value) {
        e.preventDefault();
        e.returnValue = 'Êtes-vous sûr de vouloir quitter ? Votre progression sera perdue.';
        return e.returnValue;
    }
};

// Obtenir la classe de couleur pour la difficulté
const getDifficultyColor = (difficulty) => {
    switch (difficulty) {
        case 'easy':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'medium':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        case 'hard':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
};

// Obtenir le libellé de la difficulté
const getDifficultyLabel = (difficulty) => {
    switch (difficulty) {
        case 'easy':
            return 'Facile';
        case 'medium':
            return 'Moyen';
        case 'hard':
            return 'Difficile';
        default:
            return 'Inconnu';
    }
};
</script>

<template>
    <Head :title="`Quiz - ${category.name}`" />
    
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <Link 
                        :href="route('quiz.select-category')" 
                        class="mr-4 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                        title="Retour aux catégories"
                    >
                        <i class="fas fa-arrow-left"></i>
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        {{ category.name }}
                    </h2>
                </div>
                <div class="flex items-center space-x-4">
                    <div v-if="timeLimit > 0" class="flex items-center px-3 py-1 bg-gray-100 rounded-full dark:bg-gray-700">
                        <i class="mr-2 text-yellow-500 fas fa-clock"></i>
                        <span class="font-mono font-medium">{{ formattedTime }}</span>
                    </div>
                    <button
                        @click="quitQuiz"
                        class="px-3 py-1 text-sm text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50 dark:bg-gray-800 dark:border-red-800 dark:text-red-400 dark:hover:bg-gray-700"
                    >
                        <i class="mr-1 fas fa-times"></i>
                        Quitter
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-3xl px-4 mx-auto sm:px-6 lg:px-8">
                <!-- Barre de progression -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Question {{ currentQuestionIndex + 1 }} sur {{ questions.length }}
                        </span>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ Math.round(progress) }}%
                        </span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 rounded-full dark:bg-gray-700">
                        <div 
                            class="h-2 rounded-full bg-indigo-600 transition-all duration-300 ease-in-out"
                            :style="`width: ${progress}%`"
                        ></div>
                    </div>
                </div>

                <!-- Écran de résultats -->
                <div 
                    v-if="quizCompleted" 
                    class="p-8 text-center bg-white rounded-lg shadow-md dark:bg-gray-800"
                >
                    <div class="inline-flex items-center justify-center w-20 h-20 mx-auto mb-6 text-green-500 bg-green-100 rounded-full dark:bg-green-900">
                        <i class="text-4xl fas fa-trophy"></i>
                    </div>
                    
                    <h3 class="mb-2 text-2xl font-bold text-gray-900 dark:text-white">
                        Quiz terminé !
                    </h3>
                    
                    <p class="mb-6 text-gray-600 dark:text-gray-300">
                        Vous avez répondu correctement à {{ correctAnswers }} sur {{ questions.length }} questions.
                    </p>
                    
                    <div class="grid grid-cols-1 gap-4 mb-8 sm:grid-cols-3">
                        <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-700">
                            <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">
                                {{ score }}%
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Score final
                            </div>
                        </div>
                        
                        <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-700">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                +{{ xpEarned }} XP
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Points d'expérience
                            </div>
                        </div>
                        
                        <div 
                            v-if="levelUp"
                            class="p-4 bg-white rounded-lg shadow dark:bg-gray-700"
                        >
                            <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                                Niveau {{ levelUp }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Nouveau niveau atteint !
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col space-y-3 sm:flex-row sm:space-y-0 sm:space-x-3 sm:justify-center">
                        <Link 
                            :href="route('quiz.select-category')" 
                            class="inline-flex items-center justify-center px-6 py-3 text-base font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <i class="mr-2 fas fa-home"></i>
                            Retour à l'accueil
                        </Link>
                        
                        <Link 
                            :href="route('quiz.leaderboard')" 
                            class="inline-flex items-center justify-center px-6 py-3 text-base font-medium text-indigo-700 bg-indigo-100 border border-transparent rounded-md hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <i class="mr-2 fas fa-trophy"></i>
                            Voir le classement
                        </Link>
                    </div>
                </div>

                <!-- Question actuelle -->
                <div 
                    v-else
                    ref="questionContainer"
                    class="overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800"
                >
                    <!-- En-tête de la question -->
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <span 
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="getDifficultyColor(currentQuestion.difficulty)"
                            >
                                {{ getDifficultyLabel(currentQuestion.difficulty) }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Question {{ currentQuestionIndex + 1 }}/{{ questions.length }}
                            </span>
                        </div>
                        
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white">
                            {{ currentQuestion.question_text }}
                        </h3>
                    </div>
                    
                    <!-- Réponses -->
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div 
                            v-for="answer in currentQuestion.answers" 
                            :key="answer.id"
                            class="p-4 transition-colors cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                            :class="{
                                'bg-blue-50 dark:bg-blue-900/30': selectedAnswerId === answer.id,
                                'bg-green-50 dark:bg-green-900/30': showExplanation && answer.is_correct,
                                'bg-red-50 dark:bg-red-900/30': showExplanation && selectedAnswerId === answer.id && !answer.is_correct
                            }"
                            @click="!showExplanation && (selectedAnswerId = answer.id)"
                        >
                            <div class="flex items-start">
                                <div 
                                    class="flex items-center justify-center flex-shrink-0 w-6 h-6 mt-0.5 mr-3 border-2 rounded-full"
                                    :class="{
                                        'border-gray-300': !showExplanation && selectedAnswerId !== answer.id,
                                        'border-indigo-500 bg-indigo-500': selectedAnswerId === answer.id && !showExplanation,
                                        'border-green-500 bg-green-500': showExplanation && answer.is_correct,
                                        'border-red-500': showExplanation && selectedAnswerId === answer.id && !answer.is_correct
                                    }"
                                >
                                    <span 
                                        v-if="selectedAnswerId === answer.id || (showExplanation && answer.is_correct)"
                                        class="text-white"
                                    >
                                        <i 
                                            v-if="showExplanation" 
                                            :class="answer.is_correct ? 'fas fa-check' : 'fas fa-times'"
                                        ></i>
                                        <i v-else class="text-xs fas fa-circle"></i>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-gray-800 dark:text-gray-200">
                                        {{ answer.answer_text }}
                                    </p>
                                    
                                    <!-- Explication -->
                                    <div 
                                        v-if="showExplanation && answer.id === selectedAnswerId && currentQuestion.explanation"
                                        class="p-3 mt-2 text-sm text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300"
                                    >
                                        <p class="font-medium">
                                            <i class="mr-1 text-blue-500 fas fa-info-circle"></i>
                                            Explication :
                                        </p>
                                        <p class="mt-1">{{ currentQuestion.explanation }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Boutons de navigation -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ currentQuestionIndex + 1 }} sur {{ questions.length }} questions
                        </div>
                        
                        <button
                            v-if="!showExplanation"
                            @click="checkAnswer"
                            :disabled="!hasSelectedAnswer"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Valider la réponse
                        </button>
                        
                        <button
                            v-else
                            @click="handleNextQuestion"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            {{ currentQuestionIndex < questions.length - 1 ? 'Question suivante' : 'Voir les résultats' }}
                        </button>
                    </div>
                </div>
                
                <!-- Indicateur de chargement -->
                <div 
                    v-if="isSubmitting"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                >
                    <div class="p-6 bg-white rounded-lg shadow-xl dark:bg-gray-800">
                        <div class="flex items-center">
                            <div class="w-8 h-8 mr-3 border-t-2 border-b-2 border-indigo-500 rounded-full animate-spin"></div>
                            <p class="text-lg font-medium text-gray-900 dark:text-white">
                                Vérification en cours...
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Transitions pour les réponses */
.answer-enter-active,
.answer-leave-active {
    transition: all 0.3s ease;
}

.answer-enter-from,
.answer-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

/* Animation pour les réponses correctes/incorrectes */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.bg-green-50 {
    animation: pulse 0.5s ease-in-out;
}

.bg-red-50 {
    animation: pulse 0.5s ease-in-out;
}
</style>
