<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { BarChart, Users, HelpCircle, PieChart, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
  stats: {
    type: Object,
    required: true
  }
});

const stats = [
  {
    title: 'Utilisateurs',
    value: props.stats.total_users,
    icon: Users,
    description: 'Nombre total d\'utilisateurs inscrits',
    change: '+12%',
    changeType: 'increase'
  },
  {
    title: 'Catégories',
    value: props.stats.total_categories,
    icon: PieChart,
    description: 'Catégories disponibles',
    change: '+5%',
    changeType: 'increase'
  },
  {
    title: 'Questions',
    value: props.stats.total_questions,
    icon: HelpCircle,
    description: 'Questions dans la base de données',
    change: '+8%',
    changeType: 'increase'
  },
  {
    title: 'Score moyen',
    value: Math.round(props.stats.avg_score) + '%',
    icon: CheckCircle,
    description: 'Score moyen des utilisateurs',
    change: '+2%',
    changeType: 'increase'
  }
];
</script>

<template>
  <Head title="Statistiques" />
  
  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Tableau de bord - Statistiques
        </h2>
        <div class="flex items-center space-x-2">
          <Button variant="outline" size="sm" class="ml-auto">
            <Download class="mr-2 h-4 w-4" />
            Exporter
          </Button>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Cartes de statistiques -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <Card v-for="stat in stats" :key="stat.title">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">
                {{ stat.title }}
              </CardTitle>
              <component :is="stat.icon" class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold">{{ stat.value }}</div>
              <p class="text-xs text-muted-foreground">
                {{ stat.description }}
              </p>
            </CardContent>
          </Card>
        </div>

        <!-- Graphiques -->
        <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-7">
          <!-- Graphique des activités récentes -->
          <Card class="col-span-4">
            <CardHeader>
              <CardTitle>Activité récente</CardTitle>
            </CardHeader>
            <CardContent class="pl-2">
              <div class="h-[300px] flex items-center justify-center text-muted-foreground">
                <BarChart class="h-8 w-8 mr-2" />
                <span>Graphique d'activité à venir</span>
              </div>
            </CardContent>
          </Card>

          <!-- Derniers utilisateurs inscrits -->
          <Card class="col-span-3">
            <CardHeader>
              <CardTitle>Derniers utilisateurs</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div v-for="i in 5" :key="i" class="flex items-center">
                  <Avatar class="h-9 w-9">
                    <AvatarFallback>U{i}</AvatarFallback>
                  </Avatar>
                  <div class="ml-4 space-y-1">
                    <p class="text-sm font-medium leading-none">Utilisateur {i}</p>
                    <p class="text-sm text-muted-foreground">
                      utilisateur{i}@example.com
                    </p>
                  </div>
                  <div class="ml-auto font-medium">
                    <Badge variant="outline">Nouveau</Badge>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
