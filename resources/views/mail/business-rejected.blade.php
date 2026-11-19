<x-mail::message>
# K registrácii potrebujeme doplnenie

Podnik **{{ $business }}** sme zatiaľ nemohli overiť.

**Dôvod:** {{ $reason }}

Údaje môžete upraviť v profile podniku. Keď budú v poriadku, napíšte nám na ahoj@campusbox.sk a žiadosť prejdeme znova.

<x-mail::button :url="$url">
Otvoriť profil podniku
</x-mail::button>

Tím CampusBox
</x-mail::message>
