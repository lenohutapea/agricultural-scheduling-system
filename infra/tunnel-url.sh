#!/usr/bin/env bash
# Prints the public https://*.trycloudflare.com address of the running tunnel.
set -euo pipefail
url=$(docker logs pertanian-tunnel 2>&1 | grep -oE 'https://[a-z0-9-]+\.trycloudflare\.com' | tail -n 1 || true)
if [ -z "$url" ]; then
  echo "No tunnel address yet. Is enable_public_tunnel = true? Wait a few seconds and try again." >&2
  exit 1
fi
echo "$url"
