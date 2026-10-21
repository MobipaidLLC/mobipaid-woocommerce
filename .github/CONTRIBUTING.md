# Contributing Guidelines

Contributions are welcome! To maintain a clean codebase, seamless collaboration, and predictable release management, please follow the Git branching, code quality, and release workflow outlined below.

---

## Git Branching & Release Workflow

All development follows a strict branching model: `develop-<version>` ➔ `v<version>` ➔ `master`.

### 1. Create a Development Branch

Always branch off from the latest `master` using the `develop-<version>` naming convention (e.g., `develop-1.0.0` or `develop-1.1.4`):

```bash
git checkout master
git pull origin master
git checkout -b develop-1.0.0
```

### 2. Make Changes & Validate

- Implement features, bug fixes, or documentation updates.
- Update plugin version numbers across:
  - `mobipaid/mobipaid.php` (Header `Version:` and constant `MOBIPAID_PLUGIN_VERSION`)
  - `mobipaid/readme.txt` (`Stable tag:`)
  - `mobipaid/changelog.txt`
- Ensure all PHP files pass syntax validation without errors:
  ```bash
  php -l mobipaid/includes/class-mobipaid.php
  php -l mobipaid/includes/class-mobipaid-api.php
  ```

### 3. Commit Changes

Use descriptive, conventional commit messages following the Conventional Commits specification:

```bash
git add .
git commit -m "feat(gateway): add support for api v2 payment response"
```

Common commit prefixes:

- `feat:` New features or capabilities
- `fix:` Bug fixes or hotfixes
- `refactor:` Code restructuring without behavior changes
- `perf:` Performance optimizations
- `chore:` Maintenance, version bumps, or build tasks
- `docs:` Documentation improvements

### 4. Merge to Release Branch

Create a Pull Request or merge your `develop-<version>` branch into the release branch `v<version>` (e.g., `v1.0.0` or `v1.1.4`) for final staging and QA verification:

```bash
git checkout -b v1.0.0
git merge develop-1.0.0
```

### 5. Merge into Master

Once verified and approved on staging, merge the release branch into `master`:

```bash
git checkout master
git merge v1.0.0
git push origin master
```

### 6. Cleanup Temporary Branches

Delete temporary development and release branches both locally and remotely to keep the repository clean:

```bash
# Delete local branches
git branch -d develop-1.0.0
git branch -d v1.0.0

# Delete remote branches (if pushed)
git push origin --delete develop-1.0.0
git push origin --delete v1.0.0
```

### 7. Tag & Publish GitHub Release

Create an annotated release tag on `master` and push it to GitHub:

```bash
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

Draft and publish the formal release notes at:
👉 **[Mobipaid for WooCommerce Releases](https://github.com/MobipaidLLC/mobipaid-woocommerce/releases)**

You can automatically generate release notes using:

```bash
node .github/scripts/generate-release-notes.mjs
```

---

## WordPress.org SVN Deployment

In addition to GitHub releases, new plugin versions must be synchronized with the official WordPress.org plugin directory at [https://wordpress.org/plugins/mobipaid/](https://wordpress.org/plugins/mobipaid/).

### 1. Checkout or Update WordPress.org SVN Repository

```bash
svn co https://plugins.svn.wordpress.org/mobipaid/ mobipaid-svn
cd mobipaid-svn
```

### 2. Synchronize Plugin Files to `trunk/`

Copy the plugin code from `mobipaid/` into the SVN `trunk/` directory:

```bash
cp -r /path/to/mobipaid-woocommerce/mobipaid/* trunk/
```

### 3. Create Release Tag

Tag the new version (e.g., `1.1.4`) from `trunk/`:

```bash
cp -r trunk/ tags/1.0.0/
```

### 4. Stage & Commit to SVN

Check file status, add untracked files, and commit:

```bash
svn status
svn add --force trunk/* tags/1.0.0/*
svn commit -m "Tagging version 1.0.0 and updating trunk" --username <your-wordpress-username>
```

### 5. Verify WordPress Plugin Directory

After committing, verify that the new version and changelog are live on [https://wordpress.org/plugins/mobipaid/](https://wordpress.org/plugins/mobipaid/).
