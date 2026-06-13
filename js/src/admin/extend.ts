import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';

export default [
    new Extend.Admin()
        .setting(() => ({
            setting: 'clarkwinkelmann-first-post-approval.postCount',
            type: 'number',
            label: app.translator.trans('peopleinside-first-post-approval.admin.settings.postCount'),
            min: 0,
        }))
        .setting(() => ({
            setting: 'clarkwinkelmann-first-post-approval.discussionCount',
            type: 'number',
            label: app.translator.trans('peopleinside-first-post-approval.admin.settings.discussionCount'),
            min: 0,
        }))
        .permission(() => ({
            icon: 'fas fa-check',
            label: app.translator.trans('peopleinside-first-post-approval.admin.permissions.bypass'),
            permission: 'discussion.firstPostWithoutApproval',
        }), 'start'),
];
