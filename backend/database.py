import sqlite3
from os import environ
from pathlib import Path
from uuid import uuid4

from data import DONORS


DB_PATH = Path(environ.get("RBD_DB_PATH", Path(__file__).resolve().parent / "rapidblooddonor.db"))


def get_connection():
    connection = sqlite3.connect(DB_PATH)
    connection.row_factory = sqlite3.Row
    return connection


def init_db():
    with get_connection() as connection:
        connection.execute(
            """
            CREATE TABLE IF NOT EXISTS users (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                type TEXT NOT NULL CHECK (type IN ('individual', 'hospital')),
                blood_group TEXT NOT NULL CHECK (blood_group IN ('A', 'B', 'AB', 'O')),
                rhd TEXT NOT NULL CHECK (rhd IN ('positive', 'negative')),
                zipcode TEXT NOT NULL,
                latitude REAL NOT NULL,
                longitude REAL NOT NULL,
                email TEXT NOT NULL,
                phone TEXT NOT NULL,
                allow_email INTEGER NOT NULL DEFAULT 0,
                allow_phone INTEGER NOT NULL DEFAULT 0,
                allow_sms INTEGER NOT NULL DEFAULT 0,
                availability TEXT NOT NULL DEFAULT 'Available',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
            """
        )
        connection.execute(
            """
            CREATE INDEX IF NOT EXISTS idx_users_search
            ON users (zipcode, blood_group, rhd, type)
            """
        )
        connection.execute(
            """
            CREATE TABLE IF NOT EXISTS blood_requests (
                id TEXT PRIMARY KEY,
                requester_id TEXT NOT NULL,
                recipient_id TEXT NOT NULL,
                blood_type TEXT NOT NULL,
                channel TEXT NOT NULL CHECK (channel IN ('email', 'phone', 'sms')),
                status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'declined')),
                note TEXT NOT NULL DEFAULT '',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (requester_id) REFERENCES users(id),
                FOREIGN KEY (recipient_id) REFERENCES users(id)
            )
            """
        )
        connection.execute(
            """
            CREATE INDEX IF NOT EXISTS idx_blood_requests_lookup
            ON blood_requests (requester_id, recipient_id, status)
            """
        )

        count = connection.execute("SELECT COUNT(*) FROM users").fetchone()[0]
        if count == 0:
            seed_users(connection)


def seed_users(connection):
    for user in DONORS:
        insert_user(user, connection=connection)


def row_to_user(row):
    if row is None:
        return None

    return {
        "id": row["id"],
        "name": row["name"],
        "type": row["type"],
        "blood_group": row["blood_group"],
        "rhd": row["rhd"],
        "zipcode": row["zipcode"],
        "latitude": row["latitude"],
        "longitude": row["longitude"],
        "email": row["email"],
        "phone": row["phone"],
        "preferences": {
            "email": bool(row["allow_email"]),
            "phone": bool(row["allow_phone"]),
            "sms": bool(row["allow_sms"]),
        },
        "availability": row["availability"],
    }


def list_users():
    with get_connection() as connection:
        rows = connection.execute("SELECT * FROM users").fetchall()
        return [row_to_user(row) for row in rows]


def get_user(user_id):
    with get_connection() as connection:
        row = connection.execute("SELECT * FROM users WHERE id = ?", (user_id,)).fetchone()
        return row_to_user(row)


def insert_user(user, connection=None):
    close_connection = connection is None
    connection = connection or get_connection()
    try:
        connection.execute(
            """
            INSERT INTO users (
                id, name, type, blood_group, rhd, zipcode, latitude, longitude,
                email, phone, allow_email, allow_phone, allow_sms, availability
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            """,
            (
                user["id"],
                user["name"],
                user["type"],
                user["blood_group"],
                user["rhd"],
                user["zipcode"],
                user["latitude"],
                user["longitude"],
                user["email"],
                user["phone"],
                int(user["preferences"].get("email", False)),
                int(user["preferences"].get("phone", False)),
                int(user["preferences"].get("sms", False)),
                user["availability"],
            ),
        )
        if close_connection:
            connection.commit()
    finally:
        if close_connection:
            connection.close()


def replace_user(user):
    with get_connection() as connection:
        connection.execute(
            """
            UPDATE users
            SET name = ?,
                type = ?,
                blood_group = ?,
                rhd = ?,
                zipcode = ?,
                latitude = ?,
                longitude = ?,
                email = ?,
                phone = ?,
                allow_email = ?,
                allow_phone = ?,
                allow_sms = ?,
                availability = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            """,
            (
                user["name"],
                user["type"],
                user["blood_group"],
                user["rhd"],
                user["zipcode"],
                user["latitude"],
                user["longitude"],
                user["email"],
                user["phone"],
                int(user["preferences"].get("email", False)),
                int(user["preferences"].get("phone", False)),
                int(user["preferences"].get("sms", False)),
                user["availability"],
                user["id"],
            ),
        )


def create_blood_request(requester_id, recipient_id, blood_type, channel, note=""):
    request_id = f"request-{uuid4().hex[:12]}"
    with get_connection() as connection:
        connection.execute(
            """
            INSERT INTO blood_requests (
                id, requester_id, recipient_id, blood_type, channel, note
            )
            VALUES (?, ?, ?, ?, ?, ?)
            """,
            (request_id, requester_id, recipient_id, blood_type, channel, note or ""),
        )
    return get_blood_request(request_id)


def row_to_request(row):
    if row is None:
        return None

    return {
        "id": row["id"],
        "requesterId": row["requester_id"],
        "requesterName": row["requester_name"],
        "recipientId": row["recipient_id"],
        "recipientName": row["recipient_name"],
        "recipientType": row["recipient_type"],
        "bloodType": row["blood_type"],
        "channel": row["channel"],
        "status": row["status"],
        "note": row["note"],
        "createdAt": row["created_at"],
        "updatedAt": row["updated_at"],
    }


REQUEST_SELECT = """
    SELECT
        r.id,
        r.requester_id,
        requester.name AS requester_name,
        r.recipient_id,
        recipient.name AS recipient_name,
        recipient.type AS recipient_type,
        r.blood_type,
        r.channel,
        r.status,
        r.note,
        r.created_at,
        r.updated_at
    FROM blood_requests r
    JOIN users requester ON requester.id = r.requester_id
    JOIN users recipient ON recipient.id = r.recipient_id
"""


def get_blood_request(request_id):
    with get_connection() as connection:
        row = connection.execute(
            REQUEST_SELECT + " WHERE r.id = ?",
            (request_id,),
        ).fetchone()
        return row_to_request(row)


def list_blood_requests(user_id=None):
    with get_connection() as connection:
        if user_id:
            rows = connection.execute(
                REQUEST_SELECT
                + " WHERE r.requester_id = ? OR r.recipient_id = ?"
                + " ORDER BY r.created_at DESC",
                (user_id, user_id),
            ).fetchall()
        else:
            rows = connection.execute(
                REQUEST_SELECT + " ORDER BY r.created_at DESC"
            ).fetchall()

        return [row_to_request(row) for row in rows]


def update_blood_request_status(request_id, status):
    with get_connection() as connection:
        connection.execute(
            """
            UPDATE blood_requests
            SET status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            """,
            (status, request_id),
        )
    return get_blood_request(request_id)
