# Lesson Social Migration Runbook

This runbook covers rollout steps for decoupling `lessons` social data from `posts`.

## 1) Run schema and data migrations

```bash
php artisan migrate
```

This applies:

- lesson social columns (`like_count`, `comment_count`, `share_count`, `view_count`, `download_url`)
- polymorphic comment target columns (`target_type`, `target_id`)
- `lesson_likes` table (`lesson_id`, `user_id`) with backfill from legacy `mylikes`
- backfill from legacy lesson-post linkage (`lessons.date = posts.post_id`)

## 2) Verify migration integrity

Run:

```bash
php artisan legacy:verify-lesson-social
```

The command checks:

- invalid/null comment targets
- mismatches between lesson `comment_count` and aggregated lesson comments
- remaining legacy-linked lesson count (informational)

## 3) Deploy app code using new fields

Deploy controller changes that read lesson social values from `lessons` and comments via polymorphic targets.

## 4) Post-deploy monitoring

- Watch lesson detail/video endpoints for count regressions.
- Watch comment create/delete behavior for both `post` and `lesson` targets.
- Ensure discussion feeds exclude lesson-linked legacy posts.

## 5) Cleanup (later release)

After stable verification window:

- remove fallback use of `comment.post_id` in APIs
- remove any remaining lesson↔post coupling paths

