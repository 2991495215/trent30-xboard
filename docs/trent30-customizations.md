# Trent30 XBoard

This fork carries the production customizations used by `sub.zinc.run`.

## Branch policy

- `trent30` is the production branch.
- `upstream` points to `https://github.com/cedar2025/Xboard`.
- The branch started from upstream commit `8e4864b4c7f6240e3ef08ecd7b59447e5d9dd363`, matching the image that was running when the fork was created.
- Upstream upgrades are merged deliberately and deployed only after image and endpoint verification.

## Custom behavior

- `/api/v1/user/server/fetch` returns `online_total`, counting unique active account IDs from Redis cache DB1 keys matching `USER_ONLINE_CONN_*`.
- The online count is part of the response ETag so changes are not hidden by a stale `304 Not Modified` response.
- `/api/v1/user/server/machine` returns authorized machine status and bounded load history for a node visible to the current user's group.
- Node list responses expose `machine_id`, and machine detail responses include authorized `related_nodes`, so all nodes on the same physical server can be grouped reliably.

## Image policy

GitHub Actions publishes:

- `ghcr.io/2991495215/trent30-xboard:stable`
- `ghcr.io/2991495215/trent30-xboard:trent30`
- immutable `sha-<commit>` tags

Production should use the immutable SHA tag. `latest` is intentionally not published.

## Upstream sync

```bash
git fetch upstream
git switch trent30
git merge --no-ff upstream/master
```

Resolve conflicts, validate the custom endpoints, then publish and deploy a new immutable image tag.
