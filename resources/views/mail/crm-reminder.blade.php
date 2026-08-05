<x-mail::message>
# {{ $heading }}

{{ $intro }}

@foreach($items as $item)
<x-mail::panel>
**{{ $item['title'] }}**  
{{ $item['meta'] }}

[Abrir registro]({{ $item['url'] }})
</x-mail::panel>
@endforeach

<x-mail::button :url="$adminUrl">
Abrir panel del CRM
</x-mail::button>

Este correo fue generado automáticamente según tu configuración de notificaciones.

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
