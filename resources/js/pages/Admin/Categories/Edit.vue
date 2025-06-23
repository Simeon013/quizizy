<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    category: {
        type: Object,
        required: true
    }
});

const form = useForm({
    name: props.category.name,
    description: props.category.description,
    color: props.category.color || '#3B82F6',
    icon: props.category.icon || 'question',
    is_active: props.category.is_active ?? true
});

const submit = () => {
    form.put(route('admin.categories.update', props.category.id));
};

// Icônes disponibles
const icons = [
    'question', 'book', 'globe', 'film', 'music', 'gamepad', 'paint-brush', 'code',
    'flask', 'microscope', 'atom', 'brain', 'calculator', 'chart-line', 'chess', 'dice',
    'football-ball', 'futbol', 'basketball-ball', 'volleyball-ball', 'running', 'swimmer',
    'palette', 'camera', 'headphones', 'laptop', 'mobile-alt', 'tablet-alt', 'tv', 'wifi'
];

// Couleurs prédéfinies
const colors = [
    '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
    '#EC4899', '#14B8A6', '#F97316', '#6366F1', '#D946EF'
];
</script>

<template>
    <Head :title="`Éditer ${category.name}`" />
    
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Éditer la catégorie : {{ category.name }}
                </h2>
                <Link 
                    :href="route('admin.categories.index')" 
                    class="px-4 py-2 text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white"
                >
                    Retour à la liste
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Nom -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Nom de la catégorie *
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    required
                                >
                                <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                ></textarea>
                            </div>

                            <!-- Couleur -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Couleur
                                </label>
                                <div class="mt-2 flex items-center space-x-2">
                                    <div v-for="color in colors" :key="color" 
                                         @click="form.color = color"
                                         class="w-8 h-8 rounded-full cursor-pointer border-2"
                                         :class="{ 'border-blue-500': form.color === color, 'border-transparent': form.color !== color }"
                                         :style="{ backgroundColor: color }">
                                    </div>
                                    <div class="ml-2 relative">
                                        <input 
                                            type="color" 
                                            v-model="form.color"
                                            class="w-8 h-8 rounded-full overflow-hidden border border-gray-300 cursor-pointer"
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Icône -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Icône
                                </label>
                                <div class="mt-2 grid grid-cols-6 gap-2">
                                    <div v-for="icon in icons" :key="icon" 
                                         @click="form.icon = icon"
                                         class="w-10 h-10 flex items-center justify-center rounded-md cursor-pointer"
                                         :class="{ 'bg-blue-100 dark:bg-blue-900': form.icon === icon, 'bg-gray-100 dark:bg-gray-700': form.icon !== icon }"
                                         :style="{ color: form.color }">
                                        <i :class="`fas fa-${icon} text-lg`"></i>
                                    </div>
                                </div>
                                <input 
                                    type="text" 
                                    v-model="form.icon"
                                    class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                >
                            </div>

                            <!-- Statut -->
                            <div class="flex items-center">
                                <input
                                    id="is_active"
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                >
                                <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                    Catégorie active
                                </label>
                            </div>

                            <!-- Aperçu -->
                            <div class="p-4 border rounded-md bg-gray-50 dark:bg-gray-700">
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Aperçu :</h3>
                                <div class="flex items-center space-x-2">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white" 
                                         :style="{ backgroundColor: form.color }">
                                        <i :class="`fas fa-${form.icon}`"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium" :style="{ color: form.color }">
                                            {{ form.name || 'Nom de la catégorie' }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ form.description || 'Description de la catégorie' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Boutons -->
                            <div class="flex justify-between pt-4">
                                <button
                                    type="button"
                                    @click="if(confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) { form.delete(route('admin.categories.destroy', category.id)) }"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    :disabled="form.processing"
                                >
                                    Supprimer la catégorie
                                </button>
                                <div class="flex space-x-3">
                                    <Link 
                                        :href="route('admin.categories.index')" 
                                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600"
                                    >
                                        Annuler
                                    </Link>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="inline-flex justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                        :class="form.processing ? 'bg-blue-400' : 'bg-blue-600 hover:bg-blue-700'"
                                    >
                                        <span v-if="form.processing">Enregistrement...</span>
                                        <span v-else>Enregistrer les modifications</span>
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
