#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
car_rental_root="$(cd "${script_dir}/../.." && pwd)"
gateway_root="${car_rental_root}/../vrooem-gateway"

car_rental_contract="${car_rental_root}/contracts/search-vehicle-v1.schema.json"
gateway_contract="${gateway_root}/contracts/search-vehicle-v1.schema.json"

if [[ ! -f "${car_rental_contract}" ]]; then
  echo "Missing CarRental contract: ${car_rental_contract}" >&2
  exit 1
fi

if [[ ! -f "${gateway_contract}" ]]; then
  echo "Missing vrooem-gateway contract: ${gateway_contract}" >&2
  exit 1
fi

diff -u "${car_rental_contract}" "${gateway_contract}"
