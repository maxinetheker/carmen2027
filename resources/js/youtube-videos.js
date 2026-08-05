document.querySelectorAll('[data-youtube-editor]').forEach((editor) => {
    const form = editor.closest('form');
    const list = editor.querySelector('[data-youtube-list]');
    const empty = editor.querySelector('[data-youtube-empty]');
    const template = editor.querySelector('[data-youtube-template]');
    const sync = () => {
        [...list.children].forEach((row, index) => row.querySelectorAll('[name]')
            .forEach((input) => input.name = input.name.replace(
                /youtube_videos\[(?:\d+|__INDEX__)\]/, `youtube_videos[${index}]`
            )));
        empty.hidden = list.children.length > 0;
        form.dispatchEvent(new Event('input', { bubbles: true }));
    };
    editor.querySelector('[data-youtube-add]').addEventListener('click', () => {
        list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll(
            '__INDEX__', list.children.length
        ));
        list.lastElementChild.querySelector('input').focus();
        sync();
    });
    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-youtube-remove]');
        if (! button) return;
        button.closest('.youtube-editor-row').remove();
        sync();
    });
});
