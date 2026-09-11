#!/bin/sh
# Run every tests/*.test.php through WP-CLI. Exit 1 if any file fails.
cd "$(dirname "$0")/.." || exit 1
status=0
for f in tests/*.test.php; do
  echo "== $f"
  tests/bin/wpx eval-file "$f" || status=1
done
exit $status
