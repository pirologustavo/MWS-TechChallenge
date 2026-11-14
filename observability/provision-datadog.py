#!/usr/bin/env python3
"""Create the MWS dashboard and monitors through the Datadog v1 API."""

import json
import os
import urllib.error
import urllib.request
from pathlib import Path


root = Path(__file__).resolve().parent
env_file = root.parent / ".env"
if env_file.exists():
    for raw_line in env_file.read_text().splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#"):
            continue
        if line.startswith("export "):
            line = line[7:].strip()
        name, separator, value = line.partition("=")
        if not separator:
            continue
        value = value.strip()
        if len(value) >= 2 and value[0] == value[-1] and value[0] in "\"'":
            value = value[1:-1]
        if not os.environ.get(name.strip()):
            os.environ[name.strip()] = value

site = os.environ.get("DD_SITE", "datadoghq.com")
api_key = os.environ.get("DD_API_KEY", "")
app_key = os.environ.get("DD_APP_KEY", "")
if not api_key or not app_key:
    raise SystemExit("Preencha DD_API_KEY e DD_APP_KEY no .env antes de provisionar o Datadog.")
base_url = f"https://api.{site}"
headers = {
    "DD-API-KEY": api_key,
    "DD-APPLICATION-KEY": app_key,
    "Content-Type": "application/json",
}


def post(path: str, body: dict) -> dict:
    request = urllib.request.Request(
        base_url + path,
        data=json.dumps(body).encode(),
        headers=headers,
        method="POST",
    )
    try:
        with urllib.request.urlopen(request, timeout=20) as response:
            return json.load(response)
    except urllib.error.HTTPError as error:
        detail = error.read().decode(errors="replace")
        raise SystemExit(f"Datadog API {path} falhou ({error.code}): {detail}") from error


dashboard = json.loads((root / "datadog-dashboard.json").read_text())
dashboard_result = post("/api/v1/dashboard", dashboard)
print(f"Dashboard criado: {dashboard_result.get('url', dashboard_result.get('id'))}")

monitors = json.loads((root / "datadog-monitors.json").read_text())
for monitor in monitors:
    result = post("/api/v1/monitor", monitor)
    print(f"Monitor criado: {result.get('name')} (id={result.get('id')})")
