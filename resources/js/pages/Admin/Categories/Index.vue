<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { MoreHorizontal, Plus, Pencil, Trash2, Check, X } from 'lucide-vue-next';

interface Category {
    id: number;
    name: string;
    description: string | null;
    color: string;
    icon: string;
    is_active: boolean;
}

const props = defineProps<{
    categories: Category[];
}>();

const deleteCategory = (id: number) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
        router.delete(route('admin.categories.destroy', id));
    }
};
</script>

<template>
    <Head title="Gestion des catégories" />

    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="text-xl font-semibold leading-tight">
                    Gestion des catégories
                </h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader class="pb-0">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <CardTitle>Liste des catégories</CardTitle>
                                <CardDescription class="mt-1">
                                    Gérer les catégories de questions du quiz
                                </CardDescription>
                            </div>
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <Link :href="route('admin.categories.create')" class="flex items-center justify-center gap-2">
                                    <Plus class="h-4 w-4" />
                                    <span>Ajouter une catégorie</span>
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-[300px]">Nom</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead class="w-[120px]">Statut</TableHead>
                                        <TableHead class="w-[100px] text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="category in categories" :key="category.id">
                                        <TableCell class="font-medium">
                                            <div class="flex items-center space-x-3">
                                                <div
                                                    class="h-9 w-9 rounded-full flex items-center justify-center text-white flex-shrink-0"
                                                    :style="{ backgroundColor: category.color }"
                                                >
                                                    <i :class="`fas fa-${category.icon}`"></i>
                                                </div>
                                                <span class="truncate">{{ category.name }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="text-muted-foreground text-sm line-clamp-1">
                                                {{ category.description || 'Aucune description' }}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                :class="category.is_active
                                                    ? 'bg-green-500/10 text-green-600 dark:text-green-400 border-green-500/20'
                                                    : 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20'"
                                            >
                                                <span class="flex items-center">
                                                    <component
                                                        :is="category.is_active ? Check : X"
                                                        class="h-3 w-3 mr-1"
                                                    />
                                                    {{ category.is_active ? 'Active' : 'Inactive' }}
                                                </span>
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
                                                        <Link
                                                            :href="route('admin.categories.edit', category.id)"
                                                            class="cursor-pointer w-full flex items-center"
                                                        >
                                                            <Pencil class="mr-2 h-4 w-4" />
                                                            <span>Modifier</span>
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        class="text-red-600 dark:text-red-400 focus:bg-red-50 dark:focus:bg-red-900/20"
                                                        @click="deleteCategory(category.id)"
                                                    >
                                                        <Trash2 class="mr-2 h-4 w-4" />
                                                        <span>Supprimer</span>
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="categories.length === 0">
                                        <TableCell colspan="4" class="h-24 text-center">
                                            Aucune catégorie trouvée.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
