#!/bin/sh
# Headless screenshots of the shop pages at the design's breakpoints.
# Usage: tests/shots.sh [outdir]   (default /tmp/gt-shots)
OUT="${1:-/tmp/gt-shots}"
mkdir -p "$OUT"
CHROME="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
shot() { # name url width height
  "$CHROME" --headless=new --disable-gpu --ignore-certificate-errors --hide-scrollbars \
    --window-size="$3,$4" --screenshot="$OUT/$1-$3.png" "$2" >/dev/null 2>&1
  echo "$OUT/$1-$3.png"
}
for w in 1440 1024 768 390; do
  shot shop "https://growth-tech.local/shop/" "$w" 2600
  shot category "https://growth-tech.local/product-category/propagation/" "$w" 3000
  shot product "https://growth-tech.local/product/clonex-mist/" "$w" 3400
  shot brand "https://growth-tech.local/brand/clonex/" "$w" 3800
  shot stockists "https://growth-tech.local/find-a-stockist/" "$w" 1800
done
