# First Post Approval

[![Latest Stable Version](https://img.shields.io/packagist/v/peopleinside/flarum-ext-first-post-approval.svg)](https://packagist.org/packages/peopleinside/flarum-ext-first-post-approval)
[![Total Downloads](https://img.shields.io/packagist/dt/peopleinside/flarum-ext-first-post-approval.svg)](https://packagist.org/packages/peopleinside/flarum-ext-first-post-approval)
[![License](https://img.shields.io/github/license/PeopleInside/flarum-ext-first-post-approval.svg)](LICENSE)
[![GitHub issues](https://img.shields.io/github/issues/PeopleInside/flarum-ext-first-post-approval.svg)](https://github.com/PeopleInside/flarum-ext-first-post-approval/issues)

This extension holds the first n posts and/or discussions from users for approval.

Some groups can be excluded from the rule on the permissions page.

When a post is approved, it counts +1 towards the number of first posts to approve.

When a discussion is approved, it counts +1 towards the number of first discussions to approve, and also +1 towards the number of first posts.

If you don't set a number of discussions to approve, new discussions will be held for approval based on the number of posts of the user.
For example if you require 2 posts to be approved but 0 discussions, if one of the first two interactions of the user is to create a discussion, that discussion will be held for approval.
But if they first create two replies that get approved, they can then create their first discussion without approval.

## Existing users

If you install this extension on a forum with an existing user base, you might want to manually update the `first_post_approval_count` and `first_discussion_approval_count` columns on the `users` table to prevent existing users from being subjected to the first post approval.
Any number equal or higher than the number configured in the extension settings will cause the approval to be skipped.

Alternatively, you can exclude some groups on the permissions page.

## Installation

Flarum's **Approval** and **Flags** extensions must be enabled.

    composer require peopleinside/flarum-ext-first-post-approval

## Support

This extension is under **minimal maintenance**.

It was developed for a client and released as open-source for the benefit of the community.
I might publish simple bugfixes or compatibility updates for free.

Support is offered on a "best effort" basis through the Flarum community thread.

## Links

- [GitHub](https://github.com/clarkwinkelmann/flarum-ext-first-post-approval)
- [Packagist](https://packagist.org/packages/peopleinside/flarum-ext-first-post-approval)

This is a fork of https://github.com/clarkwinkelmann/flarum-ext-first-post-approval that introduce support for Flarum 2.0 while try also to have a good code security and some improvements.
If you need support you can open an issue https://github.com/PeopleInside/flarum-ext-first-post-approval/issues
For security report please see https://github.com/PeopleInside/flarum-ext-first-post-approval/security/policy
