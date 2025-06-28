<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed, onUnmounted, nextTick, onMounted } from 'vue';
import type { Ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Search, Edit, Trash2, MoreHorizontal, X } from 'lucide-vue-next';
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
onMounted(() => {
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
});

// État de chargement et filtres
const loadingIds: Ref<number[]> = ref([]);
const isLoading = ref(false);
const searchInput = ref<HTMLInputElement | null>(null);

// Initialiser les filtres avec les valeurs du serveur
const filters = ref({
    search: props.filters?.search || '',
    category: props.filters?.category || '',
    difficulty: props.filters?.difficulty || '',
    status: props.filters?.status || '',
    sort: props.filters?.sort || 'newest'
});

// Timer pour le debounce
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

// Options de configuration
const DEBOUNCE_DELAY = 400;
const MIN_SEARCH_LENGTH = 2;

// Fonction pour mettre en surbrillance le texte recherché
const highlightSearch = (text: string) => {
    if (!filters.value.search || !text || filters.value.search.length < MIN_SEARCH_LENGTH) {
        return text || '';
    }

    try {
        // Échapper les caractères spéciaux et créer la regex
        const searchText = filters.value.search
            .replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
            .replace(/\s+/g, '\\s*');

        const regex = new RegExp(`(${searchText})`, 'gi');

        // Nettoyer le texte pour éviter XSS
        const cleanText = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        return cleanText.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-800 text-foreground px-1 rounded">$1</mark>');
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

// Fonction pour annuler le debounce
const cancelDebounce = () => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
        debounceTimer = null;
    }
};

// Fonction principale pour appliquer les filtres
const applyFilters = (immediate = false) => {
    cancelDebounce();

    const executeFilter = () => {
        // Éviter les requêtes inutiles si rien n'a changé
        const currentUrl = new URL(window.location.href);
        const newParams = new URLSearchParams();

        if (filters.value.search) newParams.set('search', filters.value.search);
        if (filters.value.category) newParams.set('category', filters.value.category);
        if (filters.value.difficulty) newParams.set('difficulty', filters.value.difficulty);
        if (filters.value.status) newParams.set('status', filters.value.status);
        if (filters.value.sort) newParams.set('sort', filters.value.sort);

        // Comparer avec l'URL actuelle
        if (currentUrl.search === '?' + newParams.toString()) {
            return;
        }

        isLoading.value = true;

        router.get(route('admin.questions.index'), {
            search: filters.value.search || undefined,
            category: filters.value.category || undefined,
            difficulty: filters.value.difficulty || undefined,
            status: filters.value.status || undefined,
            sort: filters.value.sort || undefined
        }, {
            preserveState: true,
            replace: true,
            preserveScroll: true,
            only: ['questions', 'filters'],
            onSuccess: () => {
                console.log('Filtres appliqués avec succès');
            },
            onError: (errors) => {
                console.error('Erreur lors du filtrage:', errors);
                toast.error('Une erreur est survenue lors du filtrage des questions');
            },
            onFinish: () => {
                isLoading.value = false;
            }
        });
    };

    if (immediate) {
        executeFilter();
    } else {
        debounceTimer = setTimeout(executeFilter, DEBOUNCE_DELAY);
    }
};

// Effacer la recherche
const clearSearch = () => {
    filters.value.search = '';
    nextTick(() => {
        searchInput.value?.focus();
    });
};

// Réinitialiser tous les filtres
const resetFilters = () => {
    cancelDebounce();

    filters.value = {
        search: '',
        category: '',
        difficulty: '',
        status: '',
        sort: 'newest'
    };

    applyFilters(true);
};

// Watchers pour les filtres
// Watcher spécial pour la recherche avec debounce
watch(() => filters.value.search, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        if (newVal === '' || newVal.length >= MIN_SEARCH_LENGTH) {
            applyFilters();
        }
    }
});

// Watcher pour les autres filtres (application immédiate)
watch(() => [
    filters.value.category,
    filters.value.difficulty,
    filters.value.status,
    filters.value.sort
], () => {
    applyFilters(true);
}, { deep: true });

// Nettoyer les ressources
onUnmounted(() => {
    cancelDebounce();
});

// Méthodes utilitaires pour les badges
const getDifficultyVariant = (difficulty: string) => {
    const variants: Record<string, string> = {
        easy: 'default',
        medium: 'secondary',
        hard: 'destructive'
    };
    return variants[difficulty] || 'secondary';
};

const getDifficultyLabel = (difficulty: string) => {
    const labels: Record<string, string> = {
        easy: 'Facile',
        medium: 'Moyen',
        hard: 'Difficile'
    };
    return labels[difficulty] || 'Non défini';
};

// Confirmer la suppression
const confirmDelete = (question: Question) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette question ?')) {
        loadingIds.value = [...loadingIds.value, question.id];

        router.delete(route('admin.questions.destroy', question.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Question supprimée avec succès');
            },
            onError: () => {
                toast.error('Une erreur est survenue lors de la suppression');
            },
            onFinish: () => {
                loadingIds.value = loadingIds.value.filter(id => id !== question.id);
            },
        });
    }
};

// Gestion de la pagination
const handlePagination = (url: string | null) => {
    if (!url) return;

    const urlObj = new URL(url);
    const page = urlObj.searchParams.get('page');

    router.get(route('admin.questions.index'), {
        ...Object.fromEntries(Object.entries(filters.value).filter(([_, v]) => v !== '')),
        page
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['questions']
    });
};

// Computed pour vérifier si des filtres sont actifs
const hasActiveFilters = computed(() => {
    return !!(filters.value.search ||
             filters.value.category ||
             filters.value.difficulty ||
             filters.value.status ||
             (filters.value.sort && filters.value.sort !== 'newest'));
});
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
                    <CardHeader class="pb-4">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <CardTitle>Liste des questions</CardTitle>
                                <CardDescription>
                                    Gérer les questions du quiz ({{ questions.total }} question{{ questions.total > 1 ? 's' : '' }})
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
                        <!-- Section des filtres -->
                        <div class="mb-6 p-4 bg-muted/30 rounded-lg border">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                                <!-- Recherche -->
                                <div class="lg:col-span-2">
                                    <div class="relative">
                                        <Input
                                            ref="searchInput"
                                            v-model="filters.search"
                                            type="text"
                                            placeholder="Rechercher une question..."
                                            class="pl-10 pr-10"
                                            :disabled="isLoading"
                                        />
                                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />

                                        <!-- Bouton clear search -->
                                        <Button
                                            v-if="filters.search"
                                            variant="ghost"
                                            size="sm"
                                            class="absolute right-1 top-1/2 -translate-y-1/2 h-6 w-6 p-0 hover:bg-muted"
                                            @click="clearSearch"
                                            :disabled="isLoading"
                                        >
                                            <X class="h-3 w-3" />
                                        </Button>

                                        <!-- Indicateur de chargement -->
                                        <div v-if="isLoading" class="absolute right-3 top-1/2 -translate-y-1/2">
                                            <div class="animate-spin h-4 w-4 border-2 border-primary border-t-transparent rounded-full"></div>
                                        </div>
                                    </div>
                                    <div v-if="filters.search && filters.search.length < MIN_SEARCH_LENGTH" class="text-xs text-muted-foreground mt-1">
                                        Tapez au moins {{ MIN_SEARCH_LENGTH }} caractères pour rechercher
                                    </div>
                                </div>

                                <!-- Catégorie -->
                                <div>
                                    <Select v-model="filters.category">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Toutes les catégories" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Toutes les catégories</SelectItem>
                                            <SelectItem
                                                v-for="category in categories"
                                                :key="category.id"
                                                :value="category.id.toString()"
                                            >
                                                {{ category.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Difficulté -->
                                <div>
                                    <Select v-model="filters.difficulty">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Toutes les difficultés" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Toutes les difficultés</SelectItem>
                                            <SelectItem
                                                v-for="difficulty in difficulties"
                                                :key="difficulty.value"
                                                :value="difficulty.value"
                                            >
                                                {{ difficulty.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Statut -->
                                <div>
                                    <Select v-model="filters.status">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Tous les statuts" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Tous les statuts</SelectItem>
                                            <SelectItem
                                                v-for="status in statuses"
                                                :key="status.value"
                                                :value="status.value"
                                            >
                                                {{ status.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <!-- Ligne séparée pour le tri et les actions -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                <!-- Tri -->
                                <div>
                                    <Select v-model="filters.sort">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Trier par" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="option in sortOptions"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Boutons d'action -->
                                <div class="md:col-span-2 flex gap-2">
                                    <Button
                                        variant="outline"
                                        @click="resetFilters"
                                        :disabled="!hasActiveFilters || isLoading"
                                        class="flex-1"
                                    >
                                        Réinitialiser
                                    </Button>
                                    <Button
                                        @click="() => applyFilters(true)"
                                        :disabled="isLoading"
                                        class="flex-1"
                                    >
                                        <Search class="h-4 w-4 mr-2" />
                                        {{ isLoading ? 'Recherche...' : 'Filtrer' }}
                                    </Button>
                                </div>
                            </div>

                            <!-- Indicateurs de filtres actifs -->
                            <div v-if="hasActiveFilters" class="flex flex-wrap gap-2 mt-3 pt-3 border-t">
                                <Badge v-if="filters.search" variant="secondary" class="gap-1">
                                    Recherche: "{{ filters.search }}"
                                    <Button variant="ghost" size="sm" class="h-4 w-4 p-0" @click="filters.search = ''">
                                        <X class="h-3 w-3" />
                                    </Button>
                                </Badge>
                                <Badge v-if="filters.category" variant="secondary" class="gap-1">
                                    Catégorie: {{ categories.find(c => c.id.toString() === filters.category)?.name }}
                                    <Button variant="ghost" size="sm" class="h-4 w-4 p-0" @click="filters.category = ''">
                                        <X class="h-3 w-3" />
                                    </Button>
                                </Badge>
                                <Badge v-if="filters.difficulty" variant="secondary" class="gap-1">
                                    Difficulté: {{ getDifficultyLabel(filters.difficulty) }}
                                    <Button variant="ghost" size="sm" class="h-4 w-4 p-0" @click="filters.difficulty = ''">
                                        <X class="h-3 w-3" />
                                    </Button>
                                </Badge>
                                <Badge v-if="filters.status" variant="secondary" class="gap-1">
                                    Statut: {{ statuses.find(s => s.value === filters.status)?.label }}
                                    <Button variant="ghost" size="sm" class="h-4 w-4 p-0" @click="filters.status = ''">
                                        <X class="h-3 w-3" />
                                    </Button>
                                </Badge>
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
                                            <Badge
                                                v-if="question.category"
                                                :style="{ backgroundColor: question.category.color }"
                                                class="text-white"
                                            >
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
                                            <Badge :variant="question.is_active ? 'default' : 'secondary'">
                                                {{ question.is_active ? 'Active' : 'Inactive' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-right">
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
                                                        :disabled="loadingIds.includes(question.id)"
                                                    >
                                                        <Trash2 class="mr-2 h-4 w-4" />
                                                        <span>{{ loadingIds.includes(question.id) ? 'Suppression...' : 'Supprimer' }}</span>
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>

                                    <!-- Message quand aucune question trouvée -->
                                    <TableRow v-if="questions.data.length === 0">
                                        <TableCell colspan="5" class="h-32 text-center">
                                            <div class="flex flex-col items-center gap-2">
                                                <Search class="h-8 w-8 text-muted-foreground" />
                                                <div>
                                                    <p class="text-lg font-medium">Aucune question trouvée</p>
                                                    <p class="text-sm text-muted-foreground">
                                                        {{ hasActiveFilters ? 'Essayez de modifier vos critères de recherche' : 'Commencez par ajouter une question' }}
                                                    </p>
                                                </div>
                                                <Button v-if="hasActiveFilters" variant="outline" @click="resetFilters">
                                                    Réinitialiser les filtres
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="questions.total > 0" class="flex items-center justify-between px-2 mt-6">
                            <div class="text-sm text-muted-foreground">
                                Affichage de {{ questions.from || 0 }} à {{ questions.to || 0 }} sur {{ questions.total }} question{{ questions.total > 1 ? 's' : '' }}
                            </div>
                            <div class="flex space-x-1">
                                <Button
                                    v-for="(link, index) in questions.links"
                                    :key="index"
                                    :variant="link.active ? 'default' : 'outline'"
                                    :disabled="!link.url || isLoading"
                                    @click="handlePagination(link.url)"
                                    size="sm"
                                    class="min-w-[32px] h-8"
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
