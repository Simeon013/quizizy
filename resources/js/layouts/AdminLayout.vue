<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
  Menu,
  LogOut,
  Moon,
  Sun,
  Bell,
  X,
  Users,
  BarChart2,
  LayoutDashboard,
  Folder,
  HelpCircle,
  Settings,
  User as UserIcon
} from 'lucide-vue-next';

const sidebarOpen = ref(false);
const page = usePage();

// Types pour les éléments de navigation
interface NavItem {
  name: string;
  href: string;
  icon: any; // Type plus précis pourrait être défini si nécessaire
  active: string[];
}

// Navigation principale
const navigation: NavItem[] = [
  {
    name: 'Tableau de bord',
    href: route('admin.dashboard'),
    icon: LayoutDashboard,
    active: ['admin/dashboard']
  },
  {
    name: 'Catégories',
    href: route('admin.categories.index'),
    icon: Folder,
    active: ['admin/categories']
  },
  {
    name: 'Questions',
    href: '#',
    icon: HelpCircle,
    active: ['admin/questions']
  },
  {
    name: 'Utilisateurs',
    href: '#',
    icon: Users,
    active: ['admin/users']
  },
  {
    name: 'Statistiques',
    href: '#',
    icon: BarChart2,
    active: ['admin/stats']
  },
];

// Vérifier si un élément de menu est actif
const isActive = (menuItem: NavItem): boolean => {
  if (!menuItem.active?.length) return false;
  return menuItem.active.some((path: string) => page.url.startsWith(`/${path}`));
};

// Fermer le menu latéral lors du changement de page
const closeSidebar = () => {
  if (window.innerWidth < 1024) {
    sidebarOpen.value = false;
  }
};

const userInitials = computed((): string => {
  if (!page.props.auth?.user?.name) return 'U';
  return page.props.auth.user.name
    .split(' ')
    .map((n: string) => n[0])
    .join('')
    .toUpperCase();
});

// Gestion du redimensionnement de la fenêtre
const handleResize = (): void => {
  if (window.innerWidth >= 1024) {
    sidebarOpen.value = false;
  }
};

// Gestion du thème
const theme = ref<string>('light');

const toggleTheme = (): void => {
  theme.value = theme.value === 'light' ? 'dark' : 'light';
  document.documentElement.classList.toggle('dark', theme.value === 'dark');
  localStorage.setItem('theme', theme.value);};

// Appliquer le thème au chargement
onMounted((): void => {
  // Appliquer le thème sauvegardé ou détecter la préférence système
  const savedTheme = localStorage.getItem('theme');
  const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

  theme.value = savedTheme || (systemPrefersDark ? 'dark' : 'light');
  document.documentElement.classList.toggle('dark', theme.value === 'dark');

  // Gestion du redimensionnement
  window.addEventListener('resize', handleResize);
  handleResize();
});

// Nettoyage des écouteurs d'événements
onUnmounted((): void => {
  window.removeEventListener('resize', handleResize);
});
</script>

<template>
  <div class="min-h-screen bg-background">
    <!-- Barre de navigation supérieure -->
    <header class="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
      <div class="container flex h-16 items-center">
        <!-- Logo et bouton menu mobile -->
        <div class="flex items-center justify-between pl-6">
          <Link :href="route('home')" class="flex items-center space-x-6">
            <span class="hidden text-lg font-bold md:inline-flex">Quizizy</span>
          </Link>
        </div>

        <!-- Bouton menu mobile -->
        <Sheet :open="sidebarOpen" @update:open="(val) => sidebarOpen = val">
          <SheetTrigger as-child class="md:hidden">
            <Button variant="ghost" size="icon" class="mr-2">
              <Menu class="h-5 w-5" />
              <span class="sr-only">Ouvrir le menu</span>
            </Button>
          </SheetTrigger>
          <SheetContent side="left" class="w-[300px] p-0">
            <div class="flex h-full flex-col overflow-hidden">
              <!-- En-tête du menu mobile -->
              <div class="flex h-16 items-center justify-between px-6">
                <h2 class="text-lg font-semibold">Menu</h2>
                <SheetTrigger as-child>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8"
                    @click="sidebarOpen = false"
                  >
                    <X class="h-4 w-4" />
                    <span class="sr-only">Fermer le menu</span>
                  </Button>
                </SheetTrigger>
              </div>
              <Separator />

              <!-- Navigation mobile -->
              <ScrollArea class="flex-1">
                <nav class="space-y-1 p-2">
                  <div v-for="item in navigation" :key="item.name" class="relative">
                    <Link
                      :href="item.href"
                      @click="closeSidebar"
                      class="group relative flex w-full items-center rounded-md px-3 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                      :class="{
                        'bg-accent text-accent-foreground': isActive(item),
                        'text-muted-foreground hover:bg-accent hover:text-accent-foreground': !isActive(item)
                      }"
                    >
                      <component
                        :is="item.icon"
                        class="mr-3 h-5 w-5 flex-shrink-0"
                        :class="{
                          'text-primary': isActive(item),
                          'text-muted-foreground': !isActive(item)
                        }"
                      />
                      <span class="truncate">{{ item.name }}</span>
                      <span
                        v-if="isActive(item)"
                        class="absolute right-3 h-1.5 w-1.5 rounded-full bg-primary"
                      />
                    </Link>
                  </div>
                </nav>
              </ScrollArea>

              <!-- Pied de page du menu mobile -->
              <div class="border-t p-4">
                <div class="flex items-center gap-3">
                  <Avatar class="h-9 w-9">
                    <AvatarFallback>{{ userInitials }}</AvatarFallback>
                  </Avatar>
                  <div class="grid flex-1">
                    <p class="text-sm font-medium leading-none">
                      {{ page.props.auth.user?.name || 'Utilisateur' }}
                    </p>
                    <p class="text-xs text-muted-foreground truncate">
                      {{ page.props.auth.user?.email || '' }}
                    </p>
                  </div>
                </div>
                <div class="mt-3 flex items-center justify-between">
                  <Button variant="ghost" size="sm" class="h-8 px-2 text-xs">
                    <Settings class="mr-2 h-3.5 w-3.5" />
                    Paramètres
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 px-2 text-xs text-destructive hover:text-destructive"
                    as-child
                  >
                    <Link :href="route('logout')" method="post" as="button">
                      <LogOut class="mr-2 h-3.5 w-3.5" />
                      Déconnexion
                    </Link>
                  </Button>
                </div>
              </div>
            </div>
          </SheetContent>
        </Sheet>

        <!-- Barre de recherche -->
        <div class="hidden flex-1 px-4 md:flex md:items-center md:justify-end">
          <div class="relative max-w-xl">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <SearchIcon class="h-4 w-4 text-muted-foreground" />
            </div>
            <Input
              type="search"
              placeholder="Recherche"
              class="pl-10 w-full"
            />
          </div>
        </div>

        <!-- Actions utilisateur -->
        <div class="ml-auto flex items-center space-x-1">
          <!-- Bouton de notification -->
          <Button variant="ghost" size="icon" class="h-9 w-9 rounded-full">
            <Bell class="h-5 w-5" />
            <!-- <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-primary"></span> -->
            <span class="sr-only">Notifications</span>
          </Button>

          <!-- Bouton de basculement de thème -->
          <Button
            variant="ghost"
            size="icon"
            class="h-9 w-9 rounded-full"
            @click="toggleTheme"
            :aria-label="theme === 'dark' ? 'Passer en mode clair' : 'Passer en mode sombre'"
          >
            <Moon v-if="theme === 'light'" class="h-5 w-5" />
            <Sun v-else class="h-5 w-5" />
            <span class="sr-only">
              {{ theme === 'dark' ? 'Passer en mode clair' : 'Passer en mode sombre' }}
            </span>
          </Button>

          <!-- Menu utilisateur -->
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" class="relative h-9 w-9 rounded-full p-0">
                <Avatar class="h-8 w-8">
                  <AvatarFallback class="text-xs font-medium">{{ userInitials }}</AvatarFallback>
                </Avatar>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent class="w-56" align="end" force-modal>
              <div class="flex items-center gap-2 p-2">
                <Avatar class="h-9 w-9">
                  <AvatarFallback class="text-xs font-medium">{{ userInitials }}</AvatarFallback>
                </Avatar>
                <div class="grid flex-1">
                  <p class="truncate text-sm font-medium">
                    {{ page.props.auth.user?.name || 'Utilisateur' }}
                  </p>
                  <p class="truncate text-xs text-muted-foreground">
                    {{ page.props.auth.user?.email || '' }}
                  </p>
                </div>
              </div>
              <DropdownMenuSeparator />
              <DropdownMenuItem asChild>
                <Link :href="route('profile.edit')" class="w-full cursor-pointer">
                  <UserIcon class="mr-2 h-4 w-4" />
                  <span>Profil</span>
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem asChild>
                <Link href="#" class="w-full cursor-pointer">
                  <Settings class="mr-2 h-4 w-4" />
                  <span>Paramètres</span>
                </Link>
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem asChild class="text-destructive focus:text-destructive">
                <Link :href="route('logout')" method="post" as="button" class="w-full cursor-pointer">
                  <LogOut class="mr-2 h-4 w-4" />
                  <span>Déconnexion</span>
                </Link>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </header>

    <!-- Contenu principal -->
    <div class="flex h-[calc(100vh-4rem)]">
      <!-- Barre latérale (version desktop) -->
      <aside class="hidden md:block w-64 border-r bg-background">
        <div class="flex h-full flex-col">
          <!-- En-tête de la barre latérale -->
          <div class="flex h-16 items-center border-b px-6">
            <h2 class="text-lg font-semibold">Tableau de bord</h2>
          </div>

          <!-- Navigation principale -->
          <ScrollArea class="flex-1">
            <nav class="space-y-1 p-2">
              <div v-for="item in navigation" :key="item.name" class="relative">
                <Link
                  :href="item.href"
                  class="group relative flex w-full items-center rounded-md px-3 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                  :class="{
                    'bg-accent text-accent-foreground': isActive(item),
                    'text-muted-foreground hover:bg-accent hover:text-accent-foreground': !isActive(item)
                  }"
                >
                  <component
                    :is="item.icon"
                    class="mr-3 h-5 w-5 flex-shrink-0"
                    :class="{
                      'text-primary': isActive(item),
                      'text-muted-foreground': !isActive(item)
                    }"
                  />
                  <span class="truncate">{{ item.name }}</span>
                  <span
                    v-if="isActive(item)"
                    class="absolute right-3 h-1.5 w-1.5 rounded-full bg-primary"
                  />
                </Link>
              </div>
            </nav>
          </ScrollArea>

          <!-- Pied de page de la barre latérale -->
          <div class="border-t p-4">
            <div class="flex items-center gap-3">
              <Avatar class="h-9 w-9">
                <AvatarFallback>{{ userInitials }}</AvatarFallback>
              </Avatar>
              <div class="grid flex-1">
                <p class="text-sm font-medium leading-none">
                  {{ page.props.auth.user?.name || 'Utilisateur' }}
                </p>
                <p class="text-xs text-muted-foreground truncate">
                  {{ page.props.auth.user?.email || '' }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </aside>

      <!-- Contenu de la page -->
      <main class="flex-1 overflow-auto">
        <!-- En-tête de la page -->
        <div v-if="$slots.header" class="sticky top-0 z-40 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
          <div class="container flex h-16 items-center px-6">
            <slot name="header" />
          </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="p-6">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
/* Transitions optimisées */
.slide-enter-active,
.slide-leave-active,
.fade-enter-active,
.fade-leave-active {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

.slide-enter-from,
.slide-leave-to {
  transform: translateX(-100%);
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Barre de défilement personnalisée */
::-webkit-scrollbar {
  width: 0.5rem;
  height: 0.5rem;
}

::-webkit-scrollbar-track {
  background-color: hsl(var(--background));
  border-radius: 9999px;
}

::-webkit-scrollbar-thumb {
  background-color: hsl(var(--muted-foreground) / 0.3);
  border-radius: 9999px;
}

::-webkit-scrollbar-thumb:hover {
  background-color: hsl(var(--muted-foreground) / 0.5);
}

.dark ::-webkit-scrollbar-thumb {
  background-color: hsl(var(--muted-foreground) / 0.5);
}

.dark ::-webkit-scrollbar-thumb:hover {
  background-color: hsl(var(--muted-foreground) / 0.7);
}

/* Optimisation des transitions */
* {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Correction du débordement sur mobile */
@media (max-width: 768px) {
  html, body {
    overflow-x: hidden;
  }
}
</style>
