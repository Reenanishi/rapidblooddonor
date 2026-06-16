# Rapid Blood Donor

The goal of the project is to provide a web application to search nearby blood donors via geo fencing. An application will provide an option to contact individuals via email, sms or phone call. Users can set their preference to be contacted. An application will provide an option to contact a nearby user or hospital/organization. Application will allow searching by zip code or using geo fencing by providing current latitude and longitude and search radius in km.  Users can filter by using Blood group and Blood RhD. An application will only expose a phone number or email if allowed from User.

There are 4 main blood groups (types of blood) – A, B, AB and O. Your blood group is determined by the genes you inherit from your parents. Each group can be either RhD positive or RhD negative, which means in total there are 8 blood groups. 


# Pages:
* User login page
* User sign up page
* User preference page 
* User to contact user
* Home page / Search page to contact a nearby hospital or organization by providing 

<img width="1703" height="860" alt="Screenshot 2026-06-16 at 1 29 44 PM" src="https://github.com/user-attachments/assets/b82421b8-b18b-4b70-856d-d8afceed71bc" />

## React + Python implementation

This repository now includes a React frontend and SQLite-backed Python backend in addition to the original PHP files.

### Backend

```bash
python3 backend/app.py
```

The API runs at `http://127.0.0.1:8000`.

The backend creates and seeds `backend/rapidblooddonor.db` automatically on first run. To initialize it manually:

```bash
python3 backend/init_db.py
```

Endpoints:

* `GET /api/health`
* `GET /api/search?zipcode=94103&bloodGroup=A&rhd=positive&type=individual`
* `GET /api/search?latitude=37.7749&longitude=-122.4194&radiusKm=25`
* `GET /api/users/{id}`
* `POST /api/users`
* `PATCH /api/users/{id}`
* `PUT /api/users/{id}`
* `POST /api/contact`
* `GET /api/requests?userId={id}`
* `POST /api/requests`
* `PATCH /api/requests/{id}`

The backend returns only the contact channels a donor or hospital has allowed. Private email, phone, or SMS values are withheld from search results.

Current data storage:

* The React + Python backend uses SQLite at `backend/rapidblooddonor.db`.
* Initial test data is seeded from `backend/data.py` only when the SQLite database is empty.
* The original PHP app uses MySQL through `config.php`, with database name `rapiddonor`.
* For MySQL, the same `users` table shape can be reused with a Python MySQL driver such as `mysql-connector-python` or `PyMySQL`.

Create a test user:

```bash
curl -X POST http://127.0.0.1:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "id": "donor-test-o-neg",
    "name": "Test Donor",
    "type": "individual",
    "bloodGroup": "O",
    "rhd": "negative",
    "zipcode": "94103",
    "latitude": 37.7749,
    "longitude": -122.4194,
    "email": "test.donor@example.com",
    "phone": "+14155559999",
    "preferences": {"email": true, "phone": false, "sms": true},
    "availability": "Available for testing"
  }'
```

Change a user:

```bash
curl -X PATCH http://127.0.0.1:8000/api/users/donor-test-o-neg \
  -H "Content-Type: application/json" \
  -d '{
    "zipcode": "94110",
    "preferences": {"email": true, "phone": true, "sms": false},
    "availability": "Available within 1 hour"
  }'
```

Create and track a notification request:

```bash
curl -X POST http://127.0.0.1:8000/api/requests \
  -H "Content-Type: application/json" \
  -d '{
    "requesterId": "donor-sf-a-pos",
    "recipientId": "hospital-east-o-pos",
    "bloodType": "O+",
    "channel": "email",
    "note": "Urgent O+ request"
  }'
```

Accept or decline a request:

```bash
curl -X PATCH http://127.0.0.1:8000/api/requests/request-id-here \
  -H "Content-Type: application/json" \
  -d '{"status": "accepted"}'
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

The React app runs at `http://127.0.0.1:5173` and proxies API calls to the Python backend.
