import { PageProps as InertiaPageProps } from '@inertiajs/core';

declare module '@inertiajs/vue3' {
  export * from '@inertiajs/core';
  
  export function usePage<T = PageProps>(): {
    props: T & InertiaPageProps;
    url: string;
    component?: string;
    version?: string;
  };
}

declare global {
  interface Window {
    // Déclarez ici les propriétés globales de window si nécessaire
  }
}
