import app from 'flarum/admin/app';

app.initializers.add('clarkwinkelmann-first-post-approval', () => {
    app.extensionData
        .for('clarkwinkelmann-first-post-approval')
        .registerSetting({
            setting: 'clarkwinkelmann-first-post-approval.postCount',
            type: 'number',
            label: app.translator.trans('clarkwinkelmann-first-post-approval.admin.settings.postCount'),
            min: 0,
        })
        .registerSetting({
            setting: 'clarkwinkelmann-first-post-approval.discussionCount',
            type: 'number',
            label: app.translator.trans('clarkwinkelmann-first-post-approval.admin.settings.discussionCount'),
            min: 0,
        })
        .registerPermission({
            icon: 'fas fa-check',
            label: app.translator.trans('clarkwinkelmann-first-post-approval.admin.permissions.bypass'),
            permission: 'discussion.firstPostWithoutApproval',
        }, 'start');
});
