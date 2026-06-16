from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from json import dumps, loads
from math import asin, cos, radians, sin, sqrt
from pathlib import Path
from urllib.parse import parse_qs, urlparse

from database import (
    create_blood_request,
    get_blood_request,
    get_user,
    init_db,
    insert_user,
    list_blood_requests,
    list_users,
    replace_user,
    update_blood_request_status,
)


HOST = "127.0.0.1"
PORT = 8000
ROOT = Path(__file__).resolve().parents[1]
FRONTEND_DIST = ROOT / "frontend" / "dist"
VALID_TYPES = ("individual", "hospital")
VALID_BLOOD_GROUPS = ("A", "B", "AB", "O")
VALID_RHD = ("positive", "negative")
VALID_CONTACT_CHANNELS = ("email", "phone", "sms")
USER_FIELDS = (
    "id",
    "name",
    "type",
    "blood_group",
    "rhd",
    "zipcode",
    "latitude",
    "longitude",
    "email",
    "phone",
    "preferences",
    "availability",
)


def haversine_km(lat1, lon1, lat2, lon2):
    earth_radius_km = 6371
    dlat = radians(lat2 - lat1)
    dlon = radians(lon2 - lon1)
    a = sin(dlat / 2) ** 2 + cos(radians(lat1)) * cos(radians(lat2)) * sin(dlon / 2) ** 2
    return 2 * earth_radius_km * asin(sqrt(a))


def first(query, key, default=""):
    values = query.get(key, [default])
    return values[0].strip() if isinstance(values[0], str) else values[0]


def to_float(value):
    try:
        return float(value)
    except (TypeError, ValueError):
        return None


def find_user(user_id):
    return get_user(user_id)


def public_profile(record):
    return {
        "id": record["id"],
        "name": record["name"],
        "type": record["type"],
        "bloodGroup": record["blood_group"],
        "rhd": record["rhd"],
        "bloodType": f"{record['blood_group']}{'+' if record['rhd'] == 'positive' else '-'}",
        "zipcode": record["zipcode"],
        "latitude": record["latitude"],
        "longitude": record["longitude"],
        "email": record["email"],
        "phone": record["phone"],
        "preferences": record["preferences"],
        "availability": record["availability"],
    }


def normalize_user_payload(payload, existing=None):
    source = existing or {}
    record = {}
    errors = []

    for field in USER_FIELDS:
        if field in payload:
            record[field] = payload[field]
        elif field in source:
            record[field] = source[field]

    if "bloodGroup" in payload:
        record["blood_group"] = payload["bloodGroup"]

    required = ("id", "name", "type", "blood_group", "rhd", "zipcode", "latitude", "longitude", "email", "phone")
    for field in required:
        if record.get(field) in (None, ""):
            errors.append(f"{field} is required")

    if record.get("type") not in VALID_TYPES:
        errors.append("type must be individual or hospital")
    if record.get("blood_group") not in VALID_BLOOD_GROUPS:
        errors.append("bloodGroup must be A, B, AB, or O")
    if record.get("rhd") not in VALID_RHD:
        errors.append("rhd must be positive or negative")

    latitude = to_float(record.get("latitude"))
    longitude = to_float(record.get("longitude"))
    if latitude is None or not -90 <= latitude <= 90:
        errors.append("latitude must be a number between -90 and 90")
    if longitude is None or not -180 <= longitude <= 180:
        errors.append("longitude must be a number between -180 and 180")
    record["latitude"] = latitude
    record["longitude"] = longitude

    preferences = record.get("preferences") or {}
    if not isinstance(preferences, dict):
        errors.append("preferences must be an object")
        preferences = {}

    existing_preferences = source.get("preferences", {})
    record["preferences"] = {
        channel: bool(preferences[channel]) if channel in preferences else bool(existing_preferences.get(channel, False))
        for channel in VALID_CONTACT_CHANNELS
    }
    record["availability"] = record.get("availability") or "Available"

    return record, errors


def create_user(payload):
    record, errors = normalize_user_payload(payload)
    if find_user(record.get("id")):
        errors.append("id already exists")
    if errors:
        return None, errors

    insert_user(record)
    return record, []


def update_user(user_id, payload, replace=False):
    existing = find_user(user_id)
    if existing is None:
        return None, ["user not found"]

    next_payload = payload if replace else {**existing, **payload}
    next_payload["id"] = user_id
    record, errors = normalize_user_payload(next_payload, existing)
    if errors:
        return None, errors

    replace_user(record)
    return record, []


def public_match(record, distance_km=None):
    preferences = record["preferences"]
    contact_options = {
        "email": preferences.get("email", False),
        "phone": preferences.get("phone", False),
        "sms": preferences.get("sms", False),
    }
    contact = {}

    if contact_options["email"]:
        contact["email"] = record["email"]
    if contact_options["phone"]:
        contact["phone"] = record["phone"]
    if contact_options["sms"]:
        contact["sms"] = record["phone"]

    return {
        "id": record["id"],
        "name": record["name"],
        "type": record["type"],
        "bloodType": f"{record['blood_group']}{'+' if record['rhd'] == 'positive' else '-'}",
        "bloodGroup": record["blood_group"],
        "rhd": record["rhd"],
        "zipcode": record["zipcode"],
        "availability": record["availability"],
        "distanceKm": round(distance_km, 1) if distance_km is not None else None,
        "contactOptions": contact_options,
        "contact": contact,
    }


def search_donors(query):
    zipcode = first(query, "zipcode")
    blood_group = first(query, "bloodGroup")
    rhd = first(query, "rhd")
    recipient_type = first(query, "type")
    latitude = to_float(first(query, "latitude"))
    longitude = to_float(first(query, "longitude"))
    radius_km = to_float(first(query, "radiusKm"))
    use_geofence = latitude is not None and longitude is not None and radius_km is not None and radius_km > 0

    matches = []
    for record in list_users():
        if blood_group and record["blood_group"] != blood_group:
            continue
        if rhd and record["rhd"] != rhd:
            continue
        if recipient_type and record["type"] != recipient_type:
            continue

        distance = None
        if use_geofence:
            distance = haversine_km(latitude, longitude, record["latitude"], record["longitude"])
            if distance > radius_km:
                continue
        elif zipcode and record["zipcode"] != zipcode:
            continue

        matches.append(public_match(record, distance))

    matches.sort(key=lambda item: (item["distanceKm"] is None, item["distanceKm"] or 0, item["type"]))
    return {
        "matches": matches,
        "count": len(matches),
        "mode": "geofence" if use_geofence else "zipcode" if zipcode else "all",
    }


class RapidBloodDonorHandler(BaseHTTPRequestHandler):
    def end_headers(self):
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, PUT, PATCH, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        super().end_headers()

    def do_OPTIONS(self):
        self.send_response(204)
        self.end_headers()

    def do_GET(self):
        parsed = urlparse(self.path)

        if parsed.path == "/api/health":
            return self.json_response({"status": "ok", "service": "rapid-blood-donor"})

        if parsed.path == "/api/search":
            return self.json_response(search_donors(parse_qs(parsed.query)))

        if parsed.path.startswith("/api/users/"):
            user_id = parsed.path.rsplit("/", 1)[-1]
            user = find_user(user_id)
            if user is None:
                return self.json_response({"error": "User not found"}, status=404)
            return self.json_response({"user": public_profile(user)})

        if parsed.path == "/api/requests":
            query = parse_qs(parsed.query)
            user_id = first(query, "userId")
            return self.json_response({"requests": list_blood_requests(user_id)})

        return self.serve_frontend(parsed.path)

    def do_POST(self):
        parsed = urlparse(self.path)
        body, error = self.read_json_body()
        if error:
            return self.json_response({"error": error}, status=400)

        if parsed.path == "/api/users":
            user, errors = create_user(body)
            if errors:
                return self.json_response({"errors": errors}, status=400)
            return self.json_response({"user": public_profile(user)}, status=201)

        if parsed.path == "/api/requests":
            request, errors = self.create_request(body)
            if errors:
                return self.json_response({"errors": errors}, status=400)
            return self.json_response({"request": request}, status=201)

        if parsed.path != "/api/contact":
            return self.json_response({"error": "Not found"}, status=404)

        donor = find_user(body.get("id"))
        channel = body.get("channel")
        if donor is None:
            return self.json_response({"error": "Match not found"}, status=404)
        if channel not in VALID_CONTACT_CHANNELS:
            return self.json_response({"error": "Unsupported contact channel"}, status=400)
        if not donor["preferences"].get(channel, False):
            return self.json_response({"error": "This contact channel is private"}, status=403)

        return self.json_response({
            "status": "ready",
            "channel": channel,
            "target": donor["email"] if channel == "email" else donor["phone"],
            "message": "Use the returned target to initiate contact from the client.",
        })

    def do_PATCH(self):
        return self.handle_user_update(replace=False)

    def do_PUT(self):
        return self.handle_user_update(replace=True)

    def handle_user_update(self, replace=False):
        parsed = urlparse(self.path)
        if parsed.path.startswith("/api/requests/"):
            return self.handle_request_update(parsed)

        if not parsed.path.startswith("/api/users/"):
            return self.json_response({"error": "Not found"}, status=404)

        body, error = self.read_json_body()
        if error:
            return self.json_response({"error": error}, status=400)

        user_id = parsed.path.rsplit("/", 1)[-1]
        user, errors = update_user(user_id, body, replace=replace)
        if errors:
            status = 404 if errors == ["user not found"] else 400
            return self.json_response({"errors": errors}, status=status)

        return self.json_response({"user": public_profile(user)})

    def create_request(self, body):
        requester_id = body.get("requesterId")
        recipient_id = body.get("recipientId")
        channel = body.get("channel")
        blood_type = body.get("bloodType")
        note = body.get("note", "")
        errors = []

        requester = find_user(requester_id)
        recipient = find_user(recipient_id)
        if requester is None:
            errors.append("requesterId was not found")
        if recipient is None:
            errors.append("recipientId was not found")
        if channel not in VALID_CONTACT_CHANNELS:
            errors.append("channel must be email, phone, or sms")
        if not blood_type:
            errors.append("bloodType is required")
        if recipient and channel in VALID_CONTACT_CHANNELS and not recipient["preferences"].get(channel, False):
            errors.append("recipient has not allowed this channel")
        if errors:
            return None, errors

        request = create_blood_request(requester_id, recipient_id, blood_type, channel, note)
        return request, []

    def handle_request_update(self, parsed):
        body, error = self.read_json_body()
        if error:
            return self.json_response({"error": error}, status=400)

        request_id = parsed.path.rsplit("/", 1)[-1]
        status = body.get("status")
        if status not in ("pending", "accepted", "declined"):
            return self.json_response({"errors": ["status must be pending, accepted, or declined"]}, status=400)

        if get_blood_request(request_id) is None:
            return self.json_response({"error": "Request not found"}, status=404)

        request = update_blood_request_status(request_id, status)
        return self.json_response({"request": request})

    def read_json_body(self):
        length = int(self.headers.get("Content-Length", 0))
        try:
            return loads(self.rfile.read(length) or "{}"), None
        except ValueError:
            return None, "Invalid JSON"

    def serve_frontend(self, path):
        if not FRONTEND_DIST.exists():
            return self.json_response({
                "message": "API is running. Build the React frontend with npm run build from frontend/ to serve it here.",
                "api": ["/api/health", "/api/search", "/api/contact"],
            })

        requested = FRONTEND_DIST / path.lstrip("/")
        if path == "/" or not requested.exists():
            requested = FRONTEND_DIST / "index.html"

        content_type = "text/html"
        if requested.suffix == ".js":
            content_type = "text/javascript"
        elif requested.suffix == ".css":
            content_type = "text/css"

        self.send_response(200)
        self.send_header("Content-Type", content_type)
        self.end_headers()
        self.wfile.write(requested.read_bytes())

    def json_response(self, payload, status=200):
        body = dumps(payload).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def log_message(self, format, *args):
        return


if __name__ == "__main__":
    init_db()
    server = ThreadingHTTPServer((HOST, PORT), RapidBloodDonorHandler)
    print(f"Rapid Blood Donor API running at http://{HOST}:{PORT}")
    server.serve_forever()
