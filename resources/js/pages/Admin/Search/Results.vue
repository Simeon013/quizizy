<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { 
  Users, 
  HelpCircle, 
  Folder, 
  ArrowRight,
  Search as SearchIcon
} from 'lucide-vue-next';

const props = defineProps({
  query: {
    type: String,
    required: true
  },
  results: {
    type: Object,
    required: true,
    default: () => ({
      categories: [],
      questions: [],
      users: []
    })
  }
});

const hasResults = computed(() => {
  return (
    props.results.categories?.length > 0 ||
    props.results.questions?.length > 0 ||
    props.results.users?.length > 0
  );
});
</script>

<template>
  <Head :title="`Résultats pour "${query}"`" />
  
  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Résultats pour "{{ query }}"
        </h2>
      </div>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div v-if="!hasResults" class="text-center py-12">
          <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted">
            <SearchIcon class="h-6 w-6 text-muted-foreground" />
          </div>
          <h3 class="mt-4 text-lg font-medium text-foreground">Aucun résultat trouvé</h3>
          <p class="mt-2 text-sm text-muted-foreground">
            Aucun élément ne correspond à votre recherche "{{ query }}".
          </p>
        </div>

        <div v-else class="space-y-8">
          <!-- Résultats des catégories -->
          <Card v-if="results.categories?.length > 0">
            <CardHeader class="pb-3">
              <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2 text-lg">
                  <Folder class="h-5 w-5" />
                  Catégories
                </CardTitle>
                <Button variant="ghost" size="sm" class="h-8 px-2" as-child>
                  <Link :href="route('admin.categories.index', { search: query })" class="text-xs">
                    Voir tout <ArrowRight class="ml-1 h-3 w-3" />
                  </Link>
                </Button>
              </div>
            </CardHeader>
            <CardContent class="p-0">
              <ul class="divide-y divide-border">
                <li v-for="category in results.categories" :key="`category-${category.id}`" class="px-6 py-3 hover:bg-muted/50">
                  <Link :href="route('admin.categories.edit', category)" class="flex items-center justify-between">
                    <div>
                      <p class="font-medium">{{ category.name }}</p>
                      <p class="text-sm text-muted-foreground line-clamp-1">{{ category.description }}</p>
                    </div>
                    <Badge variant="outline" class="ml-2">
                      {{ category.questions_count }} question{{ category.questions_count !== 1 ? 's' : '' }}
                    </Badge>
                  </Link>
                </li>
              </ul>
            </CardContent>
          </Card>

          <!-- Résultats des questions -->
          <Card v-if="results.questions?.length > 0">
            <CardHeader class="pb-3">
              <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2 text-lg">
                  <HelpCircle class="h-5 w-5" />
                  Questions
                </CardTitle>
                <Button variant="ghost" size="sm" class="h-8 px-2" as-child>
                  <Link :href="route('admin.questions.index', { search: query })" class="text-xs">
                    Voir tout <ArrowRight class="ml-1 h-3 w-3" />
                  </Link>
                </Button>
              </div>
            </CardHeader>
            <CardContent class="p-0">
              <ul class="divide-y divide-border">
                <li v-for="question in results.questions" :key="`question-${question.id}" class="px-6 py-3 hover:bg-muted/50">
                  <Link :href="route('admin.questions.edit', question)" class="block">
                    <div class="flex items-start justify-between">
                      <p class="font-medium">{{ question.question_text }}</p>
                      <Badge variant="outline" class="ml-2 whitespace-nowrap">
                        {{ question.category?.name || 'Sans catégorie' }}
                      </Badge>
                    </div>
                    <p v-if="question.explanation" class="mt-1 text-sm text-muted-foreground line-clamp-2">
                      {{ question.explanation }}
                    </p>
                  </Link>
                </li>
              </ul>
            </CardContent>
          </Card>

          <!-- Résultats des utilisateurs -->
          <Card v-if="results.users?.length > 0">
            <CardHeader class="pb-3">
              <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2 text-lg">
                  <Users class="h-5 w-5" />
                  Utilisateurs
                </CardTitle>
                <Button variant="ghost" size="sm" class="h-8 px-2" as-child>
                  <Link :href="route('admin.users.index', { search: query })" class="text-xs">
                    Voir tout <ArrowRight class="ml-1 h-3 w-3" />
                  </Link>
                </Button>
              </div>
            </CardHeader>
            <CardContent class="p-0">
              <ul class="divide-y divide-border">
                <li v-for="user in results.users" :key="`user-${user.id}`" class="px-6 py-3 hover:bg-muted/50">
                  <Link :href="route('admin.users.edit', user)" class="flex items-center justify-between">
                    <div>
                      <p class="font-medium">{{ user.name }}</p>
                      <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                    </div>
                    <Badge :variant="user.is_admin ? 'default' : 'outline'" class="ml-2">
                      {{ user.is_admin ? 'Administrateur' : 'Utilisateur' }}
                    </Badge>
                  </Link>
                </li>
              </ul>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
