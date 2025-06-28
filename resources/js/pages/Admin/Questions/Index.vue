<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed, onUnmounted } from 'vue';
import type { Ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Search, Edit, Trash2, MoreHorizontal } from 'lucide-vue-next';
import { toast } from 'sonner';

interface Question {
    id: number;
    question_text: string;
    explanation: string | null;
    difficulty: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    category?: {
        id: number;
        name: string;
        color: string;
    };
}

const props = defineProps<{
    questions: {
        data: Question[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number;
        to: number;
        total: number;
        current_page: number;
        last_page: number;
    };
    categories: any[];
    filters: {
        search: string;
        category: string;
        difficulty: string;
        status: string;
        sort: string;
    };
    flash?: {
        success?: string;
        error?: string;
    };
}>();

// Afficher les messages flash
const page = usePage();
if (page.props.flash?.success) {
    toast.success(page.props.flash.success, {
        description: '',
        duration: 5000,
    });
} else if (page.props.flash?.error) {
    toast.error(page.props.flash.error, {
        description: '',
        duration: 5000,
    });
}

// Filtres et tri
const loadingIds: Ref<number[]> = ref([]);
const isLoading = ref(false);

const filters = ref({
    search: props.filters?.search || '',
    category: props.filters?.category || '',
    difficulty: props.filters?.difficulty || '',
    status: props.filters?.status || 'active',
    sort: props.filters?.sort || 'newest'
});

// Fonction pour mettre en surbrillance le texte recherché (version sécurisée et optimisée)
const highlightSearch = (text: string) => {
    if (!filters.value.search || !text) return text || '';
    
    try {
        // Échapper les caractères spéciaux de regex de manière sécurisée
        const searchText = filters.value.search
            .replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
            .replace(/\s+/g, '\\s*'); // Gérer les espaces multiples
            
        const regex = new RegExp(`(${searchText})`, 'gi');
        
        // Nettoyer le texte pour éviter les attaques XSS
        const cleanText = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
            
        return cleanText.replace(regex, '<span class="bg-yellow-200 dark:bg-yellow-800 text-foreground">$1</span>');
    } catch (e) {
        console.error('Erreur lors de la mise en surbrillance :', e);
        return text;
    }
};

// Options de tri
const sortOptions = [
    { name: 'Plus récentes', value: 'newest' },
    { name: 'Plus anciennes', value: 'oldest' },
    { name: 'Texte (A-Z)', value: 'text_asc' },
    { name: 'Texte (Z-A)', value: 'text_desc' },
];

// Difficultés
const difficulties = [
    { value: 'easy', label: 'Facile' },
    { value: 'medium', label: 'Moyen' },
    { value: 'hard', label: 'Difficile' },
];

// Statuts
const statuses = [
    { value: 'active', label: 'Actives' },
    { value: 'inactive', label: 'Inactives' },
];

// Référence pour le timer de debounce
let debounceTimer: number | null = null;

// Annuler le debounce en cours
const cancelDebounce = () => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
        debounceTimer = null;
    }
};

// Appliquer les filtres avec debounce optimisé
const applyFilters = () => {
    // Annuler le debounce précédent s'il existe
    cancelDebounce();
    
    // Démarrer le minuteur de debounce
    debounceTimer = window.setTimeout(() => {
        isLoading.value = true;
        
        router.get(route('admin.questions.index'), {
            search: filters.value.search,
            category: filters.value.category,
            difficulty: filters.value.difficulty,
            status: filters.value.status,
            sort: filters.value.sort
        }, {
            preserveState: true,
            replace: true,
            preserveScroll: true,
            only: ['questions', 'filters'],
            onSuccess: () => {
                // Mettre à jour l'URL sans recharger la page
                window.history.replaceState(
                    {},
                    '',
                    route('admin.questions.index', {
                        search: filters.value.search,
                        category: filters.value.category,
                        difficulty: filters.value.difficulty,
                        status: filters.value.status,
                        sort: filters.value.sort
                    })
                );
            },
            onFinish: () => {
                isLoading.value = false;
            },
            onError: () => {
                toast.error('Une erreur est survenue lors du filtrage des questions');
            }
        });
    }, 350); // Délai réduit pour une meilleure réactivité
};

// Watcher pour la recherche avec gestion du cas où l'utilisateur efface le champ
watch(() => filters.value.search, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        if (newVal === '' && oldVal) {
            // Si l'utilisateur efface le champ, appliquer immédiatement
            cancelDebounce();
            applyFilters();
        } else {
            applyFilters();
        }
    }
});

// Watcher pour les autres filtres (déclenchement immédiat)
const otherFilters = computed(() => ({
    category: filters.value.category,
    difficulty: filters.value.difficulty,
    status: filters.value.status,
    sort: filters.value.sort
}));

watch(otherFilters, () => {
    applyFilters();
}, { deep: true });

// Nettoyer les ressources lors de la destruction du composant
onUnmounted(() => {
    cancelDebounce();
});

// Réinitialiser les filtres
const resetFilters = () => {
    // Annuler tout debounce en cours
    cancelDebounce();
    
    // Réinitialiser les filtres
    filters.value = {
        search: '',
        category: '',
        difficulty: '',
        status: 'active',
        sort: 'newest'
    };
    
    // Appliquer immédiatement les filtres réinitialisés
    applyFilters();
    // Utiliser un petit délai pour s'assurer que les valeurs sont mises à jour avant d'appliquer
    setTimeout(() => {
        applyFilters();
    }, 50);
};

// Méthodes utilitaires
const getDifficultyVariant = (difficulty: string) => {
    switch (difficulty) {
        case 'easy': return 'success';
        case 'medium': return 'warning';
        case 'hard': return 'destructive';
        default: return 'secondary';
    }
};

const getDifficultyLabel = (difficulty: string) => {
    switch (difficulty) {
        case 'easy': return 'Facile';
        case 'medium': return 'Moyen';
        case 'hard': return 'Difficile';
        default: return 'Non défini';
    }
};

const getDifficultyColor = (difficulty: string): string => {
    const colors: Record<string, string> = {
        easy: 'bg-green-500/10 text-green-600 dark:text-green-400 border-green-500/20',
        medium: 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 border-yellow-500/20',
        hard: 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20',
    };
    return colors[difficulty] || 'bg-gray-500/10 text-gray-600 dark:text-gray-400 border-gray-500/20';
};



// Confirmer la suppression d'une question
const confirmDelete = (question: Question) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette question ?')) {
        loadingIds.value = [...loadingIds.value, question.id];
        router.delete(route('admin.questions.destroy', question.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Question supprimée avec succès');
            },
            onError: () => {
                toast.error('Une erreur est survenue lors de la suppression de la question');
            },
            onFinish: () => {
                loadingIds.value = loadingIds.value.filter((id: number) => id !== question.id);
            },
        });
    }
};

// Gestion de la pagination
const handlePagination = (url: string | null) => {
    if (!url) return;
    
    const page = new URL(url).searchParams.get('page');
    router.get(route('admin.questions.index'), { page }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Gestion des questions" />

    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="text-xl font-semibold leading-tight">
                    Gestion des questions
                </h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader class="pb-0">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <CardTitle>Liste des questions</CardTitle>
                                <CardDescription>
                                    Gérer les questions du quiz
                                </CardDescription>
                            </div>
                            <Button as-child>
                                <Link :href="route('admin.questions.create')" class="gap-2">
                                    <Plus class="h-4 w-4" />
                                    Ajouter une question
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <!-- Filtres -->
                        <div class="mb-6 p-4 bg-muted/50 rounded-md">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                                <!-- Recherche -->
                                <div class="lg:col-span-2">
                                    <div class="relative">
                                        <Input
                                            v-model="filters.search"
                                            placeholder="Rechercher une question..."
                                            class="w-full pl-10"
                                            :disabled="isLoading"
                                        >
                                            <template #left>
                                                <Search class="h-4 w-4 text-muted-foreground" />
                                            </template>
                                            <template #right v-if="isLoading">
                                                <svg class="animate-spin h-4 w-4 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </template>
                                        </Input>
                                    </div>
                                </div>

                                <!-- Catégorie -->
                                <div>
                                    <Select v-model="filters.category" @update:modelValue="applyFilters">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Toutes les catégories" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Toutes les catégories</SelectItem>
                                            <SelectItem v-for="category in categories" :key="category.id" :value="category.id.toString()">
                                                {{ category.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Difficulté -->
                                <div>
                                    <Select v-model="filters.difficulty" @update:modelValue="applyFilters">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Toutes les difficultés" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Toutes les difficultés</SelectItem>
                                            <SelectItem v-for="difficulty in difficulties" :key="difficulty.value" :value="difficulty.value">
                                                {{ difficulty.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Statut -->
                                <div>
                                    <Select v-model="filters.status" @update:modelValue="applyFilters">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Tous les statuts" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Tous les statuts</SelectItem>
                                            <SelectItem v-for="status in statuses" :key="status.value" :value="status.value">
                                                {{ status.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Tri -->
                                <div>
                                    <Select v-model="filters.sort" @update:modelValue="applyFilters">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Trier par" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="option in sortOptions" :key="option.value" :value="option.value">
                                                {{ option.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Boutons d'action -->
                                <div class="flex items-end gap-2 lg:col-span-5">
                                    <Button variant="outline" class="w-full" @click="resetFilters">
                                        Réinitialiser
                                    </Button>
                                    <Button class="w-full" @click="applyFilters">
                                        <Search class="h-4 w-4 mr-2" />
                                        Filtrer
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Tableau des questions -->
                        <div class="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-[400px]">Question</TableHead>
                                        <TableHead>Catégorie</TableHead>
                                        <TableHead>Difficulté</TableHead>
                                        <TableHead class="w-[120px]">Statut</TableHead>
                                        <TableHead class="w-[100px] text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="question in questions.data" :key="question.id">
                                        <TableCell class="font-medium">
                                            <div class="space-y-1">
                                                <div class="line-clamp-2" v-html="highlightSearch(question.question_text)"></div>
                                                <div v-if="question.explanation" class="text-sm text-muted-foreground line-clamp-2">
                                                    {{ question.explanation }}
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge v-if="question.category" :style="{ backgroundColor: question.category.color }" class="text-white">
                                                {{ question.category.name }}
                                            </Badge>
                                            <span v-else class="text-muted-foreground">-</span>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :variant="getDifficultyVariant(question.difficulty)">
                                                {{ getDifficultyLabel(question.difficulty) }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :variant="question.is_active ? 'success' : 'secondary'">
                                                {{ question.is_active ? 'Active' : 'Inactive' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <div class="flex justify-end">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger as-child>
                                                        <Button variant="ghost" class="h-8 w-8 p-0">
                                                            <span class="sr-only">Ouvrir le menu</span>
                                                            <MoreHorizontal class="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem as-child>
                                                            <Link :href="route('admin.questions.edit', question.id)" class="w-full cursor-pointer">
                                                                <Edit class="mr-2 h-4 w-4" />
                                                                <span>Modifier</span>
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            @click="confirmDelete(question)"
                                                            class="text-destructive focus:text-destructive cursor-pointer"
                                                        >
                                                            <Trash2 class="mr-2 h-4 w-4" />
                                                            <span>Supprimer</span>
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="questions.data.length === 0">
                                        <TableCell colspan="5" class="h-24 text-center">
                                            Aucune question trouvée.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Pagination -->
                        <div class="flex items-center justify-between px-2 mt-4">
                            <div class="text-sm text-muted-foreground">
                                Affichage de {{ questions.from }} à {{ questions.to }} sur {{ questions.total }} question(s)
                            </div>
                            <div class="flex space-x-2">
                                <Button
                                    v-for="(link, index) in questions.links"
                                    :key="index"
                                    :variant="link.active ? 'default' : 'outline'"
                                    :disabled="!link.url"
                                    @click="handlePagination(link.url)"
                                    size="sm"
                                    class="h-8 w-8 p-0"
                                >
                                    <span v-html="link.label"></span>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
