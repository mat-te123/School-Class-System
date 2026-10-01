# Branch guardian

The `Quality gates / quality-gates` check runs on pushes to `dev` and
`backend/dev`, and on pull requests targeting either branch.

An administrator must enable branch protection in GitHub repository settings
for **both** `dev` and `backend/dev`:

1. Require a pull request before merging.
2. Require the `quality-gates` status check to pass before merging.
3. Block force pushes and branch deletion.
4. Enable "Do not allow bypassing the above settings" if available.

The status check must run at least once before it appears in the selection list.
GitHub Actions checks on a direct push report failures after the push; only
branch protection can prevent changes from entering a protected branch.
