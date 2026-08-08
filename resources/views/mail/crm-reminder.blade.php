@php
    $banner = [
        'overdue' => '🔴 **Ya venció.** Esto debió atenderse antes de la fecha indicada.',
        'now' => '🟠 **Es ahora.** Empieza en este momento.',
        'soon' => '🟡 **Falta poco.** Está por empezar.',
    ][$urgency ?? 'normal'] ?? null;
@endphp
<x-mail::message>
# {{ $heading }}

{{ $intro }}

@if($banner)
{{ $banner }}
@endif

@foreach($items as $item)
<x-mail::panel>
**{{ $item['title'] }}**
{{ $item['meta'] }}
@if(!empty($item['detail']))

{{ $item['detail'] }}
@endif

[{{ $item['action'] ?? 'Abrir en el CRM' }}]({{ $item['url'] }})
</x-mail::panel>
@endforeach

<x-mail::button :url="$adminUrl">
Abrir panel del CRM
</x-mail::button>

Puedes cambiar qué avisos recibes, por qué canal y a qué hora en **CRM → Notificaciones**.

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
