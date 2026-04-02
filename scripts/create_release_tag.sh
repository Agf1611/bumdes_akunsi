#!/usr/bin/env bash
set -euo pipefail

if [ $# -lt 1 ]; then
  echo "Usage: $0 <tag> [message]"
  exit 1
fi

tag="$1"
message="${2:-Release $tag}"

git rev-parse --git-dir >/dev/null 2>&1 || {
  echo "This directory is not a git repository."
  exit 1
}

git tag -a "$tag" -m "$message"
echo "Created annotated tag: $tag"
