<x-mail::layout>
{{-- En-tête --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
Eurêka
</x-mail::header>
</x-slot:header>

{{-- Corps --}}
{!! $slot !!}

{{-- Sous-texte --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Pied --}}
<x-slot:footer>
<x-mail::footer>
Eurêka · Ici, se tromper fait partie du jeu.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
