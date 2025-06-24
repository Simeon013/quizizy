<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, defineProps } from 'vue';

// Import des composants shadcn
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

// Import des icônes Lucide
import { 
    Users, 
    Folder, 
    CheckCircle, 
    ArrowUpRight, 
    HelpCircle, 
    Trophy,
    Plus
} from 'lucide-vue-next';

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
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Nombre total d'utilisateurs -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                Utilisateurs
                            </CardTitle>
                            <div class="h-4 w-4 text-muted-foreground">
                                <Users class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ formatNumber(stats.users?.total || 0) }}</div>
                            <p class="text-xs text-muted-foreground flex items-center">
                                <ArrowUpRight class="h-3 w-3 text-green-500 mr-1" />
                                +{{ stats.users?.new_this_month || 0 }} ce mois-ci
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Catégories actives -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                Catégories
                            </CardTitle>
                            <div class="h-4 w-4 text-muted-foreground">
                                <Folder class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{ formatNumber(stats.categories?.total || 0) }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                <span class="inline-flex items-center">
                                    <CheckCircle class="h-3 w-3 text-green-500 mr-1" />
                                    {{ stats.categories?.active || 0 }} actives
                                </span>
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Nombre total de questions -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                Questions
                            </CardTitle>
                            <div class="h-4 w-4 text-muted-foreground">
                                <HelpCircle class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ formatNumber(stats.questions?.total || 0) }}</div>
                            <p class="text-xs text-muted-foreground">
                                Réparties sur {{ stats.categories?.total || 0 }} catégories
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Statistiques de jeu -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                Parties jouées
                            </CardTitle>
                            <div class="h-4 w-4 text-muted-foreground">
                                <Trophy class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ formatNumber(0) }}</div>
                            <p class="text-xs text-muted-foreground">
                                <span class="text-muted-foreground/70">Bientôt disponible</span>
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Graphique des questions par catégorie -->
                <div class="mt-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>Répartition des questions par catégorie</CardTitle>
                            <CardDescription v-if="stats.questions?.per_category?.length">
                                Répartition du nombre de questions dans chaque catégorie
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="pt-0">
                            <div v-if="stats.questions?.per_category?.length" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Graphique en barres -->
                                <div class="h-72">
                                    <div class="h-full flex items-end gap-3 px-2">
                                        <div 
                                            v-for="(category, index) in stats.questions.per_category"
                                            :key="category.id"
                                            class="group flex-1 flex flex-col items-center min-w-0"
                                        >
                                            <div class="flex-1 w-full flex items-end relative">
                                                <div
                                                    class="w-full rounded-t-md transition-all duration-300 hover:opacity-90 hover:shadow-lg relative"
                                                    :style="{
                                                        height: `${Math.max(10, ((category.questions_count || 0) / maxQuestions) * 90)}%`,
                                                        backgroundColor: chartColors[index % chartColors.length],
                                                        '--tw-shadow-color': chartColors[index % chartColors.length] + '40',
                                                        '--tw-shadow': 'var(--tw-shadow-colored)'
                                                    }"
                                                    :title="`${category.name}: ${category.questions_count} questions`"
                                                >
                                                    <div class="absolute -top-6 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 text-xs font-medium px-2 py-1 rounded-md bg-foreground text-background whitespace-nowrap">
                                                        {{ category.questions_count || 0 }} questions
                                                    </div>
                                                </div>
                                                <div class="absolute -bottom-6 left-0 w-full text-center">
                                                    <div class="text-xs text-muted-foreground font-medium">
                                                        {{ category.questions_count || 0 }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-8 text-xs text-muted-foreground text-center truncate w-full px-1">
                                                {{ category.name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Légende -->
                                <div class="border rounded-lg p-4">
                                    <h4 class="text-sm font-medium mb-3">
                                        Détails par catégorie
                                    </h4>
                                    <div class="space-y-2 max-h-60 overflow-y-auto pr-2 scrollbar-hide">
                                        <div 
                                            v-for="(category, index) in stats.questions.per_category" 
                                            :key="`legend-${category.id}`"
                                            class="flex items-center justify-between p-2 hover:bg-accent/50 rounded-md transition-colors"
                                        >
                                            <div class="flex items-center">
                                                <div class="w-3 h-3 rounded-full mr-3 flex-shrink-0" 
                                                    :style="{ backgroundColor: chartColors[index % chartColors.length] }"></div>
                                                <span class="text-sm truncate">{{ category.name }}</span>
                                            </div>
                                            <span class="text-sm font-medium whitespace-nowrap ml-2">
                                                {{ formatNumber(category.questions_count || 0) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Message si pas de données -->
                            <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                                <div class="flex items-center justify-center w-16 h-16 rounded-full bg-muted mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8 text-muted-foreground">
                                        <line x1="22" y1="12" x2="2" y2="12" />
                                        <path d="M12 2v15.5" />
                                        <path d="M5 19l7-7 7 7" />
                                        <path d="M5 5l7 7 7-7" />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-medium mb-2">Aucune donnée disponible</h4>
                                <p class="text-sm text-muted-foreground max-w-md">
                                    Ajoutez des catégories et des questions pour voir les statistiques.
                                    Les données s'afficheront automatiquement ici.
                                </p>
                                <Button variant="outline" class="mt-4" size="sm">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Ajouter une catégorie
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Dernières activités -->
                <div class="mt-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>Activité récente</CardTitle>
                            <CardDescription>Les dernières activités sur la plateforme</CardDescription>
                        </CardHeader>
                        <CardContent class="pt-0">
                            <div class="flex flex-col items-center justify-center py-8 text-center">
                                <div class="flex items-center justify-center w-16 h-16 rounded-full bg-muted mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8 text-muted-foreground">
                                        <circle cx="12" cy="12" r="10" />
                                        <polyline points="12 6 12 12 16 14" />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-medium mb-2">Aucune activité récente</h4>
                                <p class="text-sm text-muted-foreground max-w-md">
                                    Les activités récentes de la plateforme s'afficheront ici.
                                    Cette fonctionnalité sera bientôt disponible.
                                </p>
                            </div>
                            
                            <!-- Exemple d'activité (à décommenter plus tard) -->
                            <!--
                            <div class="space-y-6">
                                <div v-for="i in 3" :key="i" class="flex items-start gap-4 pb-4 border-b">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                                        <span class="text-muted-foreground">U{i}</span>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h4 class="font-medium">Utilisateur {i}</h4>
                                            <span class="text-xs text-muted-foreground">Il y a {i}h</span>
                                        </div>
                                        <p class="text-sm text-muted-foreground">
                                            A effectué une action sur la plateforme
                                        </p>
                                    </div>
                                </div>
                            </div>
                            -->
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Styles spécifiques au composant */
.scrollbar-hide {
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE and Edge */
}

.scrollbar-hide::-webkit-scrollbar {
  display: none; /* Chrome, Safari and Opera */
  width: 0;
  height: 0;
}
</style>
