#!/usr/bin/env python3
"""Minimal client for the site's WebSensePro MCP endpoint (154 WP/Woo/ACF/RankMath tools).

Key: env GILLLDA_MCP_KEY or file ~/.gilllda-mcp-key (never commit it).
  python3 -I tools/mcp.py list                 # tool names (+ saves tools.json next to this file)
  python3 -I tools/mcp.py schema wsp_get_posts # one tool's input schema
  python3 -I tools/mcp.py wsp_get_site_info
  python3 -I tools/mcp.py wsp_get_post '{"id":123}'
In Python:  from mcp import tool; tool("wsp_get_post", {"id": 123})
Notes: meta keys starting with "_" are refused by the server (use plain keys).
"""
import json, os, sys, urllib.request, urllib.error

URL = "https://gilllda.ir/wp-json/wsp-mcp/v1/mcp"
_KEY_FILE = os.path.expanduser("~/.gilllda-mcp-key")
KEY = os.environ.get("GILLLDA_MCP_KEY") or (open(_KEY_FILE).read().strip() if os.path.exists(_KEY_FILE) else "")
_SID = None


def _raw(method, params=None, sid=None):
    h = {"Content-Type": "application/json", "Accept": "application/json, text/event-stream",
         "Authorization": "Bearer " + KEY, "User-Agent": "gilllda-tools"}
    if sid:
        h["Mcp-Session-Id"] = sid
    body = json.dumps({"jsonrpc": "2.0", "id": 1, "method": method, "params": params or {}}).encode()
    try:
        r = urllib.request.urlopen(urllib.request.Request(URL, data=body, headers=h), timeout=180)
        return r.headers.get("Mcp-Session-Id"), json.load(r)
    except urllib.error.HTTPError as e:
        return None, {"error": e.code, "body": e.read().decode()[:2000]}


def call(method, params=None):
    global _SID
    if not KEY:
        sys.exit("No MCP key: set GILLLDA_MCP_KEY or create ~/.gilllda-mcp-key")
    if not _SID:
        _SID, _ = _raw("initialize", {"protocolVersion": "2025-03-26", "capabilities": {}, "clientInfo": {"name": "cli", "version": "1"}})
    return _raw(method, params, _SID)[1]


def tool(name, args=None):
    r = call("tools/call", {"name": name, "arguments": args or {}})
    if "error" in r:
        return json.dumps(r, ensure_ascii=False)
    return "\n".join(c.get("text", "") for c in r["result"].get("content", []))


if __name__ == "__main__":
    here = os.path.dirname(os.path.abspath(__file__))
    if len(sys.argv) < 2:
        sys.exit(__doc__)
    if sys.argv[1] == "list":
        tools = call("tools/list")["result"]["tools"]
        json.dump(tools, open(os.path.join(here, "tools.json"), "w"), ensure_ascii=False, indent=1)
        for t in tools:
            print(t["name"], "|", (t.get("description") or "")[:90])
    elif sys.argv[1] == "schema":
        tools = call("tools/list")["result"]["tools"]
        print(json.dumps(next(t for t in tools if t["name"] == sys.argv[2])["inputSchema"], ensure_ascii=False, indent=1))
    else:
        print(tool(sys.argv[1], json.loads(sys.argv[2]) if len(sys.argv) > 2 else {}))
