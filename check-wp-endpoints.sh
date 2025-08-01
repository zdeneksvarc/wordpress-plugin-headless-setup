#!/bin/bash

# Get URL from command-line argument or prompt
if [ -n "$1" ]; then
  BASE_URL="$1"
else
  read -p "Enter base URL (e.g. example.com or https://example.com): " BASE_URL
fi

# Auto-prepend https:// if missing
if [[ ! $BASE_URL =~ ^https?:// ]]; then
  BASE_URL="https://$BASE_URL"
  echo "(Auto-corrected to: $BASE_URL)"
fi

echo "Checking WordPress endpoints for $BASE_URL"
echo ""
echo "Endpoint       Response Status Code (401 or 403 are ok)"
echo "-------------------------------------------------------"

CURL_OPTS=( --silent --output /dev/null --write-out "%{http_code}" )

check_code() {
  local name="$1"
  local url="$2"
  local method="$3"
  local headers="$4"
  local data="$5"

  local cmd=( curl "${CURL_OPTS[@]}" -X "$method" )

  if [ -n "$headers" ]; then
    IFS=$'\n'
    for h in $headers; do
      cmd+=( -H "$h" )
    done
    unset IFS
  fi

  if [ -n "$data" ]; then
    cmd+=( --data "$data" )
  fi

  cmd+=( "$url" )

  status=$("${cmd[@]}")
  printf "%-12s   %s\n" "$name" "$status"
}

check_code "/"        "$BASE_URL/" "GET" "" ""
check_code "REST API" "$BASE_URL/wp-json" "GET" "" ""
check_code "GraphQL"  "$BASE_URL/graphql" "POST" "" ""
check_code "XML-RPC"  "$BASE_URL/xmlrpc.php" "POST" "" ""
