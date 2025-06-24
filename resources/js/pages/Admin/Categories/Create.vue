<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Plus, X, Palette, ChevronDown } from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';

// État du formulaire
const form = useForm({
    name: '',
    description: '',
    color: '#3B82F6',
    icon: 'question',
    is_active: false
});

// Références
const showIconPicker = ref(false);
const customColor = ref(form.color);
const iconPickerRef = ref<HTMLElement | null>(null);

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

// Gestion des événements de clic en dehors du sélecteur
const handleClickOutside = (event: MouseEvent) => {
    if (iconPickerRef.value && !iconPickerRef.value.contains(event.target as Node)) {
        showIconPicker.value = false;
    }
};

// Configuration des écouteurs d'événements
onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

// Méthodes
const setColor = (color: string) => {
    form.color = color;
    customColor.value = color;
};

const setCustomColor = (e: Event) => {
    form.color = (e.target as HTMLInputElement).value;
};

const toggleIconPicker = (e: Event) => {
    e.stopPropagation();
    showIconPicker.value = !showIconPicker.value;
};

const selectIcon = (icon: string, e: Event) => {
    e.stopPropagation();
    form.icon = icon;
    showIconPicker.value = false;
};

// Fonction pour basculer l'état actif/inactif
const toggleActive = (e: Event) => {
    e.preventDefault();
    form.is_active = !form.is_active;
};

const submit = () => {
    form.post(route('admin.categories.store'));
};
</script>

<template>
    <Head title="Créer une catégorie" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="text-xl font-semibold leading-tight">
                    Créer une catégorie
                </h2>
                <Button as-child variant="outline">
                    <Link :href="route('admin.categories.index')">
                        <X class="h-4 w-4 mr-2" />
                        Retour à la liste
                    </Link>
                </Button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Nouvelle catégorie</CardTitle>
                        <CardDescription>
                            Remplissez les détails de la nouvelle catégorie
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Nom -->
                            <div class="space-y-2">
                                <Label for="name">
                                    Nom de la catégorie <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Ex: Culture générale, Cinéma, Musique..."
                                    :class="{ 'border-destructive': form.errors.name }"
                                    required
                                />
                                <p v-if="form.errors.name" class="text-sm text-destructive">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Description -->
                            <div class="space-y-2">
                                <Label for="description">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Décrivez cette catégorie..."
                                />
                            </div>

                            <!-- Couleur et Icône -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Couleur -->
                                <div class="space-y-4">
                                    <Label>Couleur</Label>
                                    <div class="space-y-3">
                                        <div class="flex flex-wrap gap-2">
                                            <div
                                                v-for="color in colors"
                                                :key="color"
                                                class="w-8 h-8 rounded-full cursor-pointer border-2 transition-transform hover:scale-110"
                                                :class="{ 'ring-2 ring-offset-2 ring-primary': form.color === color }"
                                                :style="{
                                                    backgroundColor: color,
                                                    borderColor: form.color === color ? 'hsl(var(--border))' : 'transparent'
                                                }"
                                                @click="setColor(color)"
                                            />
                                        </div>
                                        <div class="flex items-center gap-2 mt-2">
                                            <div class="relative">
                                                <div class="w-10 h-10 rounded-full overflow-hidden">
                                                    <input
                                                        type="color"
                                                        v-model="customColor"
                                                        @input="setCustomColor"
                                                        class="w-full h-full cursor-pointer opacity-0"
                                                    />
                                                </div>
                                                <div class="absolute inset-0 pointer-events-none flex items-center justify-center text-muted-foreground">
                                                    <Palette class="h-5 w-5" />
                                                </div>
                                            </div>
                                            <span class="text-sm text-muted-foreground">Personnalisée</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Icône -->
                                <div class="space-y-4">
                                    <Label>Icône</Label>
                                    <div class="relative">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="w-full justify-between"
                                            @click="toggleIconPicker"
                                        >
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center" :style="{ backgroundColor: form.color + '20' }">
                                                    <i :class="`fas fa-${form.icon} text-sm`" :style="{ color: form.color }"></i>
                                                </div>
                                                <span class="capitalize">{{ form.icon }}</span>
                                            </div>
                                            <ChevronDown class="h-4 w-4 text-muted-foreground" />
                                        </Button>

                                        <Transition
                                            enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95"
                                        >
                                            <div
                                                v-if="showIconPicker"
                                                class="absolute z-50 mt-1 w-full bg-popover text-popover-foreground rounded-md border shadow-lg p-2 max-h-60 overflow-y-auto"
                                                @click.stop
                                            >
                                                <div class="grid grid-cols-6 gap-2">
                                                    <div
                                                        v-for="icon in icons"
                                                        :key="icon"
                                                        @click="selectIcon(icon, $event)"
                                                        class="w-10 h-10 flex items-center justify-center rounded-md cursor-pointer hover:bg-accent transition-colors"
                                                        :class="{ 'bg-accent/50': form.icon === icon }"
                                                        :style="{
                                                            border: form.icon === icon ? `2px solid ${form.color}` : '2px solid transparent',
                                                            '--tw-ring-color': form.color
                                                        }"
                                                    >
                                                        <i
                                                            :class="`fas fa-${icon} text-lg`"
                                                            :style="{ color: form.color }"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </Transition>
                                    </div>
                                </div>
                            </div>

                            <!-- Statut -->
                            <div class="flex items-center space-x-2 pt-2">
                                <Switch
                                    id="is_active"
                                    :checked="form.is_active"
                                    @click="toggleActive"
                      @update:checked="(val) => form.is_active = val"
                                    :aria-checked="form.is_active"
                                />
                                <Label for="is_active" class="cursor-pointer">
                                    {{ form.is_active ? 'Catégorie active' : 'Catégorie inactive' }}
                                </Label>
                            </div>

                            <!-- Aperçu -->
                            <Card class="mt-6">
                                <CardHeader class="pb-3">
                                    <h3 class="text-sm font-medium">Aperçu</h3>
                                </CardHeader>
                                <CardContent>
                                    <div class="flex items-center space-x-3 p-3  rounded-lg bg-muted/50">
                                        <div
                                            class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 shadow-sm"
                                            :style="{ backgroundColor: form.color }"
                                         >
                                            <i
                                                 :class="`fas fa-${form.icon} text-lg`"
                                                class="text-white"
                                            />
                                        </div>
                                         <div class="min-w-0">
                                            <h4
                                                 class="font-medium truncate"
                                                :style="{ color: form.color }"
                                            >
                                                {{ form.name || 'Nom de la catégorie' }}
                                            </h4>
                                            <p class="text-sm text-muted-foreground truncate">
                                                {{ form.description || 'Description de la catégorie' }}
                                            </p>
                                         </div>
                                         <Badge
                                            variant="outline"
                                            class="ml-auto py-1 px-2 rounded-full"
                                            :class="form.is_active ? 'border-green-500/20 text-green-600 dark:text-green-400 bg-green-500/10' : 'border-red-500/20 text-red-600 dark:text-red-400 bg-red-500/10'"
                                        >
                                            {{ form.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Boutons -->
                            <div class="flex justify-end gap-3 pt-4 border-t">
                                <Button
                                    type="button"
                                    variant="outline"
                                    as-child
                                    :disabled="form.processing"
                                >
                                    <Link :href="route('admin.categories.index')">
                                        Annuler
                                    </Link>
                                </Button>
                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="min-w-[120px]"
                                >
                                    <Plus class="h-4 w-4 mr-2" />
                                    <span>{{ form.processing ? 'Création en cours...' : 'Créer la catégorie' }}</span>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
