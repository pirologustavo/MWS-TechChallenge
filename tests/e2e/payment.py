"""Only the external payment provider is simulated; no application database access."""
import json
from http.server import BaseHTTPRequestHandler, HTTPServer

checkout = {}


class Handler(BaseHTTPRequestHandler):
    def log_message(self, *args):
        pass

    def reply(self, body):
        self.send_response(200)
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        self.wfile.write(json.dumps(body).encode())

    def do_POST(self):
        global checkout
        body = json.loads(self.rfile.read(int(self.headers.get('Content-Length', 0))) or '{}')
        if self.path == '/checkout/preferences':
            checkout = body
            self.reply({'id': 'bdd-preference', 'init_point': 'http://payment:8080/checkout'})
        else:
            self.reply({'status': 'approved'})

    def do_GET(self):
        self.reply({'id': 123, 'external_reference': checkout['external_reference'],
                    'currency_id': 'BRL', 'transaction_amount': checkout['items'][0]['unit_price'],
                    'status': 'approved'})


HTTPServer(('0.0.0.0', 8080), Handler).serve_forever()
