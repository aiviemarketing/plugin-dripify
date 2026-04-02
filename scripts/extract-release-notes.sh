#!/usr/bin/env bash
# Prints the top (latest) release section from a Keep-a-Changelog / conventional CHANGELOG.
# Stops at the next version heading (## or ### with semver).
set -euo pipefail

CHANGELOG="${1:-CHANGELOG.md}"
if [[ ! -f "$CHANGELOG" ]]; then
  echo "Missing changelog: $CHANGELOG" >&2
  exit 1
fi

awk '
  function is_version_heading(line) {
    # ## [1.2.3] or ### [1.2.3](compare...) — conventional / standard-version
    if (line ~ /^##[[:space:]]+\[[0-9]/) return 1
    if (line ~ /^###[[:space:]]+\[[0-9]/) return 1
    # ### 1.2.3 (2024-01-01) — some entries without compare link
    if (line ~ /^###[[:space:]]+[0-9]+\.[0-9]+\.[0-9]+[[:space:]]*\(/) return 1
    return 0
  }
  is_version_heading($0) {
    if (started) exit
    started = 1
  }
  started { print }
' "$CHANGELOG"
