<x-mail::message>
# Nový podnik čaká na overenie

**{{ $business->name }}** ({{ $business->type }})

- IČO: {{ $business->ico }}
- Adresa: {{ $business->address }}
- Majiteľ: {{ $owner?->name ?? '–' }}, {{ $owner?->email ?? '–' }}, {{ $owner?->phone ?? '–' }}
- Registrácia: {{ $business->created_at->format('j. n. Y H:i') }}

Majiteľovi sme sľúbili overenie do 24 hodín.

<x-mail::button :url="$url">
Otvoriť žiadosti
</x-mail::button>

CampusBox
</x-mail::message>
