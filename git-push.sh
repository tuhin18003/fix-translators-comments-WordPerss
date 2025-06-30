#!/bin/bash

# Usage:
# bash git-push.sh --m "commit message" --release "1.0.0"

while [[ "$#" -gt 0 ]]; do
    case $1 in
        --m) commit_msg="$2"; shift ;;
        --release) version="$2"; shift ;;
        *) echo "❌ Unknown parameter: $1"; exit 1 ;;
    esac
    shift
done

if [ -z "$commit_msg" ]; then
    echo "❌ Commit message (--m) is required."
    exit 1
fi

echo "📦 Committing with message: $commit_msg"
git add .
git commit -m "$commit_msg"
git push  # push commits first

if [ -n "$version" ]; then
    [[ "$version" =~ ^v ]] && tag="$version" || tag="v$version"

    echo "🏷️  Tagging release: $tag"
    git tag -a "$tag" -m "Release $tag"
    git push origin "$tag"  # push tag separately so GitHub can trigger release
    echo "✅ Tag pushed. GitHub Actions will now handle the release."
else
    echo "✅ Code pushed. No version tag added."
fi