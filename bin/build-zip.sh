#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
version="$(sed -nE 's/^[[:space:]]*\*?[[:space:]]*Version:[[:space:]]*(.+)$/\1/p' "$root/YourStoryz.php" | head -n 1 | tr -d '[:space:]')"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

mkdir "$work/repo"
rsync -a --exclude='/.git' --exclude='/vendor' --exclude='/vendor-prefixed' --exclude='/build' --exclude='/node_modules' --exclude='/assets/build' --exclude='/.claude' --exclude='/.task-ledgers*' "$root/" "$work/repo/"

cd "$work/repo"
# Dev dependencies stay installed on purpose: Strauss itself is a dev dependency. Only vendor-prefixed/ is shipped.
composer install --no-interaction --no-progress --no-scripts
vendor/bin/strauss --deleteVendorPackages=true
npm ci
npm run build

package="$root/build/yourstoryz"
rm -rf "$root/build"
mkdir -p "$package"

for item in YourStoryz.php uninstall.php readme.txt src templates blocks vendor-prefixed; do
    if [ -e "$item" ]; then
        cp -R "$item" "$package/"
    fi
done

mkdir -p "$package/assets"
cp -R assets/images assets/build "$package/assets/"

cd "$root/build"
zip -rq "yourstoryz-$version.zip" yourstoryz

echo "Built build/yourstoryz-$version.zip"
