<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
  Table, 
  TableBody, 
  TableCell, 
  TableHead, 
  TableHeader, 
  TableRow 
} from '@/components/ui/table';

const props = defineProps({
  users: {
    type: Object,
    required: true
  }
});
</script>

<template>
  <Head title="Gestion des utilisateurs" />
  
  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Gestion des utilisateurs
        </h2>
        <Button>
          <Plus class="mr-2 h-4 w-4" />
          Ajouter un utilisateur
        </Button>
      </div>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
          <div class="p-6">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nom</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Rôle</TableHead>
                  <TableHead>Statut</TableHead>
                  <TableHead class="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="user in users.data" :key="user.id">
                  <TableCell class="font-medium">{{ user.name }}</TableCell>
                  <TableCell>{{ user.email }}</TableCell>
                  <TableCell>
                    <Badge variant="outline">
                      {{ user.is_admin ? 'Administrateur' : 'Utilisateur' }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Badge :variant="user.is_active ? 'default' : 'secondary'">
                      {{ user.is_active ? 'Actif' : 'Désactivé' }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-right">
                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                      <span class="sr-only">Modifier</span>
                      <Pencil class="h-4 w-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
            
            <!-- Pagination -->
            <div class="mt-4 flex items-center justify-between">
              <div class="text-sm text-muted-foreground">
                Affichage de <span class="font-medium">{{ users.from }}</span> à 
                <span class="font-medium">{{ users.to }}</span> sur 
                <span class="font-medium">{{ users.total }}</span> utilisateurs
              </div>
              <Pagination
                :links="users.links"
                :current-page="users.current_page"
                :last-page="users.last_page"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
