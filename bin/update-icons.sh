#!/bin/sh

# Download icons, create sprite sheet, save to assets/icons/icons.svg.
# The generated sprite sheet should be committed to the git repository.

# Note: this must be kept compatible with regular /bin/sh so it runs under the
# Alpine-based docker container.

set -e

PROJECT_ROOT=$(dirname $(dirname $(realpath $0)))
PATH="$PROJECT_ROOT/node_modules/.bin:$PATH"
TEMP=$(mktemp -d)
OUT="$PROJECT_ROOT/assets/icons/icons.svg"

trap 'rm -rf "$TEMP"' EXIT

SESSION_ID=$(curl -fsSL -X POST -F "config=@$PROJECT_ROOT/assets/fontello.json" http://fontello.com/)
TEMP_ZIP=$(mktemp)
curl -fsSL -o "$TEMP_ZIP" "http://fontello.com/$SESSION_ID/get"
unzip -jn "$TEMP_ZIP" -d "$TEMP"

font-blast "$TEMP/postmill.svg" "$TEMP"

# no webpack svg sprite loaders, they suck and don't work
echo '<svg xmlns="http://www.w3.org/2000/svg"
           xmlns:xlink="http://www.w3.org/1999/xlink"
           display="none"
           width="0"
           height="0"><defs>' > "$TEMP/icons.svg"

for file in "$TEMP"/svg/*.svg; do
    icon=$(basename "${file%.svg}")

    sed -e "s/^<svg\(>\| \)/<symbol id=\"$icon\" /" \
        -e 's/ xmlns="[^"]\+"\( \|\)/ /g' \
        -e "s!</svg>!</symbol>!" "$file" >> "$TEMP/icons.svg"

    echo >> "$TEMP/icons.svg"
done

echo '</defs></svg>' >> "$TEMP/icons.svg"

mv "$TEMP/icons.svg" "$OUT"
