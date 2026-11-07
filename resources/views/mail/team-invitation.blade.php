<x-mail::message>
# Pozvánka do tímu {{ $business }}

Dostali ste pozvánku do panela CampusBox pre podnik **{{ $business }}** s rolou **{{ $role }}**.

<x-mail::button :url="$url">
Prijať pozvánku
</x-mail::button>

Ak pozvánku nečakáte, tento e‑mail ignorujte.

Tím CampusBox
</x-mail::message>
