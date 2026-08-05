<section class="form-card">
    <div class="form-card-heading">
        <div><h2>Descripción avanzada</h2><p>Da formato al contenido y agrega emojis sin escribir código.</p></div>
    </div>
    <div class="rich-editor" data-rich-wrap>
        <div class="rich-toolbar" role="toolbar" aria-label="Formato de descripción">
            <button type="button" data-rich-command="formatBlock" data-rich-value="h2">Título</button>
            <button type="button" data-rich-command="formatBlock" data-rich-value="p">Texto</button>
            <button type="button" data-rich-command="bold"><strong>N</strong></button>
            <button type="button" data-rich-command="italic"><em>C</em></button>
            <button type="button" data-rich-command="underline"><u>S</u></button>
            <button type="button" data-rich-command="insertUnorderedList">• Lista</button>
            <button type="button" data-rich-command="insertOrderedList">1. Lista</button>
            <button type="button" data-rich-link>Enlace</button>
            <button type="button" data-emoji-toggle aria-expanded="false">😊 Emoji</button>
            <button type="button" data-rich-command="removeFormat">Limpiar</button>
        </div>
        <div class="emoji-picker" data-emoji-picker data-emoji-render hidden>
            @foreach(['🏡','🏢','📍','📐','🛏️','🛁','🚗','🌳','✨','✅','🔑','📞','💰','📄','☀️','🌊','🐾','🛡️'] as $emoji)
                <button type="button" data-emoji="{{ $emoji }}" aria-label="Insertar {{ $emoji }}">{{ $emoji }}</button>
            @endforeach
        </div>
        <div class="rich-canvas" contenteditable="true" role="textbox" aria-multiline="true"
            data-rich-editor>{!! $description !!}</div>
        <textarea name="description" data-rich-input hidden>{{ $description }}</textarea>
    </div>
</section>
