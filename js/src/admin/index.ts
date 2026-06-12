import app from 'flarum/admin/app';

export { default as extend } from './extend';

app.initializers.add('peopleinside-first-post-approval', () => {
    // Inizializzazione estensione
});
