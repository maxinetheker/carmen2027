/**
 * Turns the Google Maps embed in the presentation modal into a PNG for the AI.
 *
 * The map is a cross-origin iframe, so its pixels are unreachable from JS: html2canvas
 * and friends render it as an empty box. The only way to get the real Google imagery is
 * to capture this tab through getDisplayMedia and crop the frame down to the iframe's
 * position, which is what this does. The user approves the share once per capture.
 */
const settle = () => new Promise((resolve) => requestAnimationFrame(() => setTimeout(resolve, 220)));

const grabFrame = async (stream) => {
    const video = document.createElement('video');
    video.srcObject = stream;
    video.muted = true;
    await video.play();
    // The first frame after play() is often still blank.
    await settle();

    return video;
};

const cropToElement = (video, element) => {
    const rect = element.getBoundingClientRect();
    const scaleX = video.videoWidth / window.innerWidth;
    const scaleY = video.videoHeight / window.innerHeight;
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(rect.width * scaleX));
    canvas.height = Math.max(1, Math.round(rect.height * scaleY));
    canvas.getContext('2d').drawImage(
        video,
        rect.left * scaleX, rect.top * scaleY, rect.width * scaleX, rect.height * scaleY,
        0, 0, canvas.width, canvas.height
    );

    return canvas;
};

const toPngFile = (canvas) => new Promise((resolve, reject) => {
    canvas.toBlob((blob) => {
        if (! blob) {
            reject(new Error('No se pudo convertir la captura en imagen.'));
            return;
        }
        resolve(new File([blob], 'croquis-mapa.png', { type: 'image/png' }));
    }, 'image/png');
});

const attach = (input, file) => {
    const transfer = new DataTransfer();
    transfer.items.add(file);
    input.files = transfer.files;
    input.dispatchEvent(new Event('change', { bubbles: true }));
};

export async function captureCroquisMap(form) {
    const frame = form.querySelector('[data-croquis-frame]');
    const input = form.querySelector('[data-croquis-file]');
    const preview = form.querySelector('[data-croquis-preview]');
    const state = form.querySelector('[data-croquis-state]');
    const clear = form.querySelector('[data-croquis-clear]');
    // say() renders the small amount of markup these hints use; sayPlain() is for text
    // that comes from an exception, which must never be parsed as HTML.
    const say = (message) => { if (state) state.innerHTML = message; };
    const sayPlain = (message) => { if (state) state.textContent = message; };

    if (! frame || ! input) return;
    if (! navigator.mediaDevices?.getDisplayMedia) {
        say('Este navegador no permite capturar la pestaña. Toma una captura de pantalla y súbela abajo.');
        return;
    }

    frame.scrollIntoView({ block: 'center' });
    let stream;
    try {
        stream = await navigator.mediaDevices.getDisplayMedia({
            video: { displaySurface: 'browser' },
            preferCurrentTab: true,
            audio: false,
        });
    } catch {
        say('Captura cancelada. Puedes intentarlo de nuevo o subir una captura manual abajo.');
        return;
    }

    try {
        const surface = stream.getVideoTracks()[0]?.getSettings()?.displaySurface;
        if (surface && surface !== 'browser') {
            say('Elegiste compartir una ventana o pantalla completa. Vuelve a intentarlo y selecciona <strong>esta pestaña</strong> para que el recorte coincida con el mapa.');
            return;
        }

        const video = await grabFrame(stream);
        const file = await toPngFile(cropToElement(video, frame));
        attach(input, file);

        if (preview) {
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
        }
        if (clear) clear.hidden = false;
        say('Captura lista: se enviará a la IA junto con las coordenadas de la propiedad.');
    } catch (error) {
        sayPlain(`No se pudo capturar el mapa (${error.message}). Sube una captura manual abajo.`);
    } finally {
        stream.getTracks().forEach((track) => track.stop());
    }
}

export function clearCroquisCapture(form) {
    const input = form.querySelector('[data-croquis-file]');
    const preview = form.querySelector('[data-croquis-preview]');
    const clear = form.querySelector('[data-croquis-clear]');
    const state = form.querySelector('[data-croquis-state]');

    if (input) input.value = '';
    if (preview) { preview.hidden = true; preview.removeAttribute('src'); }
    if (clear) clear.hidden = true;
    if (state) state.textContent = 'Captura descartada. Encuadra el mapa y vuelve a capturar.';
}
