# Qube Attendance API Documentation

**Base URL:** `https://yourdomain.com/qube/` (or `http://localhost/qube/` for local)

**Authentication:** All endpoints marked "Auth: Yes" require the `Authorization` header:
```
Authorization: Bearer <your-jwt-token>
```

**Content-Type:** All POST requests accept `application/json` body.

---

## 1. Authentication

---

### 1.1 POST /api/auth/login

Login with email and password. Returns JWT token + user details.

**Auth Required:** No

**Request:**
```
POST /api/auth/login
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "john@company.com",
  "password": "your_password"
}
```

**Success Response (200):**
```json
{
  "status": true,
  "message": "Login successful.",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdGFmZl9pZCI6MSwiZW1haWwiOiJhZG1pbkBjb21wYW55LmNvbSIsImlzX2FkbWluIjp0cnVlLCJpYXQiOjE3MjM3MTUwMDAsImV4cCI6MTcyNDMxOTgwMH0.abc123",
    "token_type": "Bearer",
    "expires_in": 604800,
    "user": {
      "staff_id": 1,
      "email": "john@company.com",
      "firstname": "John",
      "lastname": "Doe",
      "full_name": "John Doe",
      "phonenumber": "+919876543210",
      "profile_image": "staff_1.png",
      "is_admin": true,
      "last_login": "2026-08-15 10:30:00",
      "active": true
    },
    "role": {
      "id": 2,
      "name": "Manager"
    },
    "permissions": {
      "invoices": ["view", "create", "edit", "delete"],
      "customers": ["view", "create", "edit"],
      "projects": ["view", "create"],
      "staff": ["view"]
    }
  }
}
```

**Error Responses:**

*Missing fields (422):*
```json
{
  "status": false,
  "message": "Email and password are required."
}
```

*Invalid credentials (401):*
```json
{
  "status": false,
  "message": "Invalid email or password."
}
```

*Inactive account (403):*
```json
{
  "status": false,
  "message": "Your account is inactive. Please contact the administrator."
}
```

---

### 1.2 POST /api/auth/refresh

Refresh an expiring JWT token to get a new one.

**Auth Required:** Yes

**Request:**
```
POST /api/auth/refresh
Authorization: Bearer <current-valid-token>
```

**Request Body:** None (empty)

**Success Response (200):**
```json
{
  "status": true,
  "message": "Token refreshed successfully.",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.new_token_here.signature",
    "token_type": "Bearer",
    "expires_in": 604800
  }
}
```

**Error Response (401):**
```json
{
  "status": false,
  "message": "Unauthorized: Expired token"
}
```

---

### 1.3 GET /api/auth/me

Get the current authenticated user's profile details.

**Auth Required:** Yes

**Request:**
```
GET /api/auth/me
Authorization: Bearer <token>
```

**Success Response (200):**
```json
{
  "status": true,
  "data": {
    "user": {
      "staff_id": 1,
      "email": "john@company.com",
      "firstname": "John",
      "lastname": "Doe",
      "full_name": "John Doe",
      "phonenumber": "+919876543210",
      "profile_image": "staff_1.png",
      "is_admin": true,
      "last_login": "2026-08-15 10:30:00",
      "active": true
    },
    "role": {
      "id": 2,
      "name": "Manager"
    },
    "permissions": {
      "invoices": ["view", "create", "edit", "delete"],
      "customers": ["view", "create", "edit"],
      "projects": ["view", "create"],
      "staff": ["view"]
    }
  }
}
```

**Error Response (401):**
```json
{
  "status": false,
  "message": "Unauthorized: Token not provided"
}
```

---

## 2. Face Registration & Verification

---

### 2.1 POST /api/face/register

Register a face embedding for the authenticated user. One face per user (overwrites previous).

**Auth Required:** Yes

**Request:**
```
POST /api/face/register
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "embedding": [0.0234, -0.0567, 0.1234, -0.0891, 0.0456, ...],
  "device_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| embedding | array of floats | Yes | 128, 192, or 512-dim face embedding vector from MobileFaceNet/FaceNet |
| device_id | string | No | Unique device identifier |

**Success Response (201):**
```json
{
  "status": true,
  "message": "Face registered successfully.",
  "data": {
    "template_id": 15,
    "embedding_size": 128,
    "registered_at": "2026-08-15 10:35:00"
  }
}
```

**Error Responses:**

*Missing embedding (422):*
```json
{
  "status": false,
  "message": "Face embedding is required and must be an array."
}
```

*Wrong dimension (422):*
```json
{
  "status": false,
  "message": "Invalid embedding dimension. Expected 128, 192, or 512 floats."
}
```

*Non-numeric values (422):*
```json
{
  "status": false,
  "message": "Embedding values must be numeric."
}
```

---

### 2.2 POST /api/face/verify

Verify a face embedding against the stored template. Returns match result + similarity score.

**Auth Required:** Yes

**Request:**
```
POST /api/face/verify
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "embedding": [0.0234, -0.0567, 0.1234, -0.0891, 0.0456, ...],
  "threshold": 0.6
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| embedding | array of floats | Yes | Face embedding to verify |
| threshold | float | No | Similarity threshold (default: 0.6, range: 0.0-1.0) |

**Success Response — Match (200):**
```json
{
  "status": true,
  "message": "Face verified successfully.",
  "data": {
    "matched": true,
    "similarity": 0.8723,
    "distance": 0.4102,
    "threshold": 0.6,
    "stored_dimension": 128,
    "sent_dimension": 128,
    "registered_from": "web"
  }
}
```

**Success Response — No Match (200):**
```json
{
  "status": true,
  "message": "Face verification failed.",
  "data": {
    "matched": false,
    "similarity": 0.3214,
    "distance": 1.1642,
    "threshold": 0.6,
    "stored_dimension": 128,
    "sent_dimension": 128,
    "registered_from": "web"
  }
}
```

**Error — No face registered (404):**
```json
{
  "status": false,
  "message": "No face registered. Please register your face first.",
  "data": {
    "face_registered": false
  }
}
```

**Error — Model mismatch (400):**
```json
{
  "status": false,
  "message": "Embedding dimension mismatch. The registered face was created with a different model (128-dim) than the one sent (192-dim). Both registration and verification must use the same face model.",
  "data": {
    "matched": false,
    "stored_dimension": 128,
    "sent_dimension": 192
  }
}
```

---

### Debugging "always not matching"

`similarity` tells you which of the two problems you have:

| similarity | Meaning | Fix |
|-----------|---------|-----|
| Exactly `0` + dimension mismatch error | Registration and verification used **different models** | Use the same model on both sides |
| Roughly `-0.1` to `0.2` on the same person | Same dimension but **different models** (e.g. face-api.js vs MobileFaceNet — both are 128-dim but incompatible) | Use the same model on both sides |
| `0.4` – `0.6` on the same person | Same model, but poor capture quality | Improve lighting, face the camera straight on, or lower `threshold` to `0.5` |
| `> 0.7` on the same person | Working correctly | — |

**The most common cause:** the web admin page registers faces with **face-api.js** (`faceRecognitionNet`, 128-dim), while the mobile app generates embeddings with **TFLite MobileFaceNet**. Both output 128 floats, so the dimension check passes, but the vector spaces are completely unrelated — the similarity score is effectively random and never crosses the threshold.

Two ways to resolve it:

1. **Register and verify from the same source.** If the mobile app punches attendance, register the face from the mobile app too (`POST /api/face/register`). Faces registered from the web will only verify against the web.
2. **Use one model everywhere.** Run face-api.js in a WebView in the mobile app, or swap the web admin page to the same TFLite MobileFaceNet model the app uses.

The `registered_from` field in the response (`web` or `mobile`) tells you where the stored template came from, so you can confirm the source matches.

---

### 2.3 GET /api/face/status

Check if the authenticated user has a registered face template.

**Auth Required:** Yes

**Request:**
```
GET /api/face/status
Authorization: Bearer <token>
```

**Success Response — Face registered (200):**
```json
{
  "status": true,
  "data": {
    "face_registered": true,
    "registered_at": "2026-08-15 10:35:00",
    "device_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890"
  }
}
```

**Success Response — No face (200):**
```json
{
  "status": true,
  "data": {
    "face_registered": false,
    "registered_at": null,
    "device_id": null
  }
}
```

---

### 2.4 POST /api/face/delete

Delete the face template. Users can delete their own; admins can delete any staff member's face.

**Auth Required:** Yes

**Request:**
```
POST /api/face/delete
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body (delete own face):**
```json
{}
```

**Request Body (admin deleting another staff's face):**
```json
{
  "staff_id": 5
}
```

**Success Response (200):**
```json
{
  "status": true,
  "message": "Face template deleted."
}
```

**Error — Not admin (403):**
```json
{
  "status": false,
  "message": "Only administrators can delete other users' face data."
}
```

---

## 3. Attendance

---

### 3.1 GET /api/attendance/employees

Get list of all active employees for dropdown selection (admin only).

**Auth Required:** Yes (Admin only)

**Request:**
```
GET /api/attendance/employees
Authorization: Bearer <token>
```

**Success Response (200):**
```json
{
  "status": true,
  "data": {
    "total": 15,
    "employees": [
      {
        "staff_id": 1,
        "firstname": "John",
        "lastname": "Doe",
        "full_name": "John Doe",
        "email": "john@company.com",
        "phonenumber": "+919876543210",
        "profile_image": "staff_1.png",
        "face_registered": true
      },
      {
        "staff_id": 2,
        "firstname": "Jane",
        "lastname": "Smith",
        "full_name": "Jane Smith",
        "email": "jane@company.com",
        "phonenumber": "+919876543211",
        "profile_image": null,
        "face_registered": false
      },
      {
        "staff_id": 3,
        "firstname": "Ravi",
        "lastname": "Kumar",
        "full_name": "Ravi Kumar",
        "email": "ravi@company.com",
        "phonenumber": "+919876543212",
        "profile_image": "staff_3.png",
        "face_registered": true
      }
    ]
  }
}
```

**Error — Not admin (403):**
```json
{
  "status": false,
  "message": "Only administrators can access the employee list."
}
```

---

### 3.2 POST /api/attendance/punch_for

Admin marks attendance (login/logout) for a selected employee from dropdown. Works for both `in` and `out`, unlimited times per day.

**Auth Required:** Yes (Admin only)

> **The punch is gated on face verification.** The record is inserted only when the supplied embedding matches the employee's registered face (cosine similarity ≥ 0.6). A mismatch returns `status: false` and writes nothing to the database.

**Request:**
```
POST /api/attendance/punch_for
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "staff_id": 5,
  "type": "in",
  "project_id": 3,
  "embedding": [0.12, -0.45, 0.67, 0.23, -0.11, ...],
  "latitude": 12.971599,
  "longitude": 77.594566
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| staff_id | int | Yes | Employee ID from the employees dropdown |
| type | string | Yes | `"in"` for login, `"out"` for logout |
| embedding | array of floats | Yes | Live face embedding. Must match the registered face |
| project_id | int | No | Project ID from the projects dropdown. Unknown IDs are ignored |
| latitude | float | No | GPS latitude |
| longitude | float | No | GPS longitude |
| punch_time | string (Y-m-d H:i:s) | No | Custom punch time. Defaults to now if omitted or unparseable |
| device_id | string | No | Device identifier |
| note | string | No | Optional note |

**Face matched — punch recorded (201):**
```json
{
  "status": true,
  "message": "Attendance recorded for John Doe. Punch in at 09:15:30.",
  "data": {
    "punch_id": 143,
    "staff_id": 5,
    "type": "in",
    "punch_time": "2026-08-15 09:15:30",
    "matched": true,
    "similarity": 0.8723
  }
}
```

**Face did not match — nothing recorded (403):**
```json
{
  "status": false,
  "message": "Face did not match. Attendance not recorded.",
  "data": {
    "matched": false,
    "similarity": 0.0582,
    "distance": 1.3821,
    "threshold": 0.6,
    "stored_dimension": 128,
    "sent_dimension": 122,
    "registered_from": "web"
  }
}
```

**Model mismatch — nothing recorded (403):**
```json
{
  "status": false,
  "message": "Embedding dimension mismatch. The registered face was created with a different model (128-dim) than the one sent (122-dim). Both registration and verification must use the same face model.",
  "data": {
    "matched": false,
    "similarity": 0.0,
    "stored_dimension": 128,
    "sent_dimension": 122
  }
}
```

**Other error cases**

*Not admin (403):*
```json
{ "status": false, "message": "Only administrators can mark attendance for other employees." }
```

*Missing staff_id (422):*
```json
{ "status": false, "message": "Employee staff_id is required." }
```

*Employee not found or inactive (404):*
```json
{ "status": false, "message": "Employee not found or inactive." }
```

*Invalid punch type (422):*
```json
{ "status": false, "message": "Punch type must be \"in\" or \"out\"." }
```

*Missing embedding (422):*
```json
{ "status": false, "message": "Face embedding is required for attendance." }
```

*Employee has no registered face (400):*
```json
{
  "status": false,
  "message": "No face registered for this employee. Please register their face first.",
  "data": { "matched": false, "face_registered": false }
}
```

---

### 3.3 POST /api/attendance/punch

Record a clock-in or clock-out with mandatory face verification.

**Auth Required:** Yes

**Request:**
```
POST /api/attendance/punch
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "type": "in",
  "embedding": [0.0234, -0.0567, 0.1234, -0.0891, 0.0456, ...],
  "latitude": 12.971599,
  "longitude": 77.594566,
  "device_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "note": "Working from office"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| type | string | Yes | `"in"` for clock-in, `"out"` for clock-out |
| embedding | array of floats | Yes | Live face embedding for verification |
| latitude | float | No | GPS latitude |
| longitude | float | No | GPS longitude |
| device_id | string | No | Device identifier |
| note | string | No | Optional note (max 500 chars) |

**Success Response (201):**
```json
{
  "status": true,
  "message": "Attendance recorded. Punch in at 09:15:30.",
  "data": {
    "punch_id": 142,
    "type": "in",
    "punch_time": "2026-08-15 09:15:30",
    "face_similarity": 0.8723,
    "today": {
      "date": "2026-08-15",
      "status": "punched_in",
      "first_in": "2026-08-15 09:15:30",
      "last_out": null,
      "total_punches": 1,
      "working_hours": "00:00:00",
      "records": [
        {
          "id": "142",
          "staff_id": "1",
          "type": "in",
          "punch_time": "2026-08-15 09:15:30",
          "punch_date": "2026-08-15",
          "latitude": "12.97159900",
          "longitude": "77.59456600",
          "device_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
          "ip_address": "192.168.1.100",
          "face_similarity": "0.8723",
          "note": "Working from office",
          "created_at": "2026-08-15 09:15:30"
        }
      ]
    }
  }
}
```

**Error — Invalid type (422):**
```json
{
  "status": false,
  "message": "Punch type must be \"in\" or \"out\"."
}
```

**Error — No embedding (422):**
```json
{
  "status": false,
  "message": "Face embedding is required for attendance."
}
```

**Error — No face registered (400):**
```json
{
  "status": false,
  "message": "No face registered. Please register your face before punching.",
  "data": {
    "face_registered": false
  }
}
```

**Error — Face mismatch (403):**
```json
{
  "status": false,
  "message": "Face verification failed. Please try again.",
  "data": {
    "face_matched": false,
    "similarity": 0.3214
  }
}
```

**Error — Too frequent (429):**
```json
{
  "status": false,
  "message": "Please wait at least 1 minute between punches."
}
```

---

### 3.4 GET /api/attendance/status

Get today's attendance status for the authenticated user.

**Auth Required:** Yes

**Request:**
```
GET /api/attendance/status
Authorization: Bearer <token>
```

**Success Response — Punched in (200):**
```json
{
  "status": true,
  "data": {
    "date": "2026-08-15",
    "status": "punched_in",
    "first_in": "2026-08-15 09:15:30",
    "last_out": null,
    "total_punches": 1,
    "working_hours": "03:45:12",
    "records": [
      {
        "id": "142",
        "staff_id": "1",
        "type": "in",
        "punch_time": "2026-08-15 09:15:30",
        "punch_date": "2026-08-15",
        "latitude": "12.97159900",
        "longitude": "77.59456600",
        "device_id": "a1b2c3d4",
        "ip_address": "192.168.1.100",
        "face_similarity": "0.8723",
        "note": "Working from office",
        "created_at": "2026-08-15 09:15:30"
      }
    ]
  }
}
```

**Success Response — Punched out (200):**
```json
{
  "status": true,
  "data": {
    "date": "2026-08-15",
    "status": "punched_out",
    "first_in": "2026-08-15 09:15:30",
    "last_out": "2026-08-15 18:30:00",
    "total_punches": 4,
    "working_hours": "08:45:30",
    "records": [
      {
        "id": "142",
        "type": "in",
        "punch_time": "2026-08-15 09:15:30",
        "note": ""
      },
      {
        "id": "143",
        "type": "out",
        "punch_time": "2026-08-15 13:00:00",
        "note": "Lunch break"
      },
      {
        "id": "144",
        "type": "in",
        "punch_time": "2026-08-15 13:45:00",
        "note": ""
      },
      {
        "id": "145",
        "type": "out",
        "punch_time": "2026-08-15 18:30:00",
        "note": ""
      }
    ]
  }
}
```

**Success Response — Not punched today (200):**
```json
{
  "status": true,
  "data": {
    "date": "2026-08-15",
    "status": "not_punched",
    "first_in": null,
    "last_out": null,
    "total_punches": 0,
    "working_hours": "00:00:00",
    "records": []
  }
}
```

---

### 3.5 GET /api/attendance/history

Get attendance history with pagination.

**Auth Required:** Yes

**Request:**
```
GET /api/attendance/history?from_date=2026-08-01&to_date=2026-08-15&limit=30&offset=0
Authorization: Bearer <token>
```

**Query Parameters:**

| Param | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| from_date | string (Y-m-d) | No | 30 days ago | Start date |
| to_date | string (Y-m-d) | No | Today | End date |
| limit | int | No | 30 | Records per page (max 100) |
| offset | int | No | 0 | Pagination offset |

**Success Response (200):**
```json
{
  "status": true,
  "data": {
    "records": [
      {
        "id": "145",
        "staff_id": "1",
        "type": "out",
        "punch_time": "2026-08-15 18:30:00",
        "punch_date": "2026-08-15",
        "latitude": "12.97159900",
        "longitude": "77.59456600",
        "device_id": "a1b2c3d4",
        "ip_address": "192.168.1.100",
        "face_similarity": "0.8612",
        "note": "",
        "created_at": "2026-08-15 18:30:00"
      },
      {
        "id": "144",
        "staff_id": "1",
        "type": "in",
        "punch_time": "2026-08-15 13:45:00",
        "punch_date": "2026-08-15",
        "latitude": "12.97159900",
        "longitude": "77.59456600",
        "device_id": "a1b2c3d4",
        "ip_address": "192.168.1.100",
        "face_similarity": "0.9102",
        "note": "",
        "created_at": "2026-08-15 13:45:00"
      }
    ],
    "daily_summary": [
      {
        "date": "2026-08-15",
        "first_in": "2026-08-15 09:15:30",
        "last_out": "2026-08-15 18:30:00",
        "working_hours": "08:45:30",
        "total_punches": 4
      },
      {
        "date": "2026-08-14",
        "first_in": "2026-08-14 09:00:00",
        "last_out": "2026-08-14 17:45:00",
        "working_hours": "08:15:00",
        "total_punches": 2
      },
      {
        "date": "2026-08-13",
        "first_in": "2026-08-13 09:30:00",
        "last_out": "2026-08-13 18:00:00",
        "working_hours": "08:00:00",
        "total_punches": 2
      }
    ],
    "total": 45,
    "from_date": "2026-08-01",
    "to_date": "2026-08-15"
  }
}
```

---

### 3.6 GET /api/attendance/summary

Get a monthly attendance summary.

**Auth Required:** Yes

**Request:**
```
GET /api/attendance/summary?month=2026-08
Authorization: Bearer <token>
```

**Query Parameters:**

| Param | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| month | string (Y-m) | No | Current month | Month to summarize |

**Success Response (200):**
```json
{
  "status": true,
  "data": {
    "month": "2026-08",
    "total_days_present": 12,
    "total_working_time": "102:30",
    "average_per_day": "08:32",
    "daily": [
      {
        "date": "2026-08-01",
        "first_in": "2026-08-01 09:00:00",
        "last_out": "2026-08-01 18:00:00",
        "working_hours": "08:30:00",
        "total_punches": 2
      },
      {
        "date": "2026-08-02",
        "first_in": "2026-08-02 09:15:00",
        "last_out": "2026-08-02 17:45:00",
        "working_hours": "08:00:00",
        "total_punches": 2
      },
      {
        "date": "2026-08-04",
        "first_in": "2026-08-04 09:30:00",
        "last_out": "2026-08-04 18:30:00",
        "working_hours": "08:15:00",
        "total_punches": 4
      }
    ]
  }
}
```

**Error — Invalid month format (422):**
```json
{
  "status": false,
  "message": "Invalid month format. Use Y-m (e.g., 2026-08)."
}
```

---

## Error Response Format (All Endpoints)

All error responses follow this consistent format:

```json
{
  "status": false,
  "message": "Human-readable error description."
}
```

Some errors include additional data:

```json
{
  "status": false,
  "message": "Face verification failed. Please try again.",
  "data": {
    "face_matched": false,
    "similarity": 0.3214
  }
}
```

---

## HTTP Status Codes Used

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created (punch recorded, face registered) |
| 400 | Bad request (missing prerequisite, e.g., no face registered) |
| 401 | Unauthorized (invalid/missing/expired token) |
| 403 | Forbidden (inactive account, face mismatch, not admin) |
| 404 | Not found (user not found, no face registered) |
| 405 | Method not allowed (wrong HTTP method) |
| 422 | Validation error (missing/invalid fields) |
| 429 | Too many requests (punch cooldown) |
| 500 | Server error |

---

## 4. Face Descriptor Generator (WebView helper)

---

### GET /face/embed

A page that converts a face image into a 128-dimension face-api.js descriptor.

**Auth Required:** No — the page holds no data and reads no database. Images are processed in memory on the client and never uploaded to or stored on the server.

**Why it exists:** the web admin registration page generates descriptors with face-api.js. A descriptor from any other model (TFLite MobileFaceNet included) sits in an unrelated vector space and will never match, even at the same 128 length. Having the mobile app generate its descriptors through this page puts both registration and verification on the identical model.

Model weights load from `cdn.jsdelivr.net`, so the device needs internet access at punch time.

**Self-test:** open `https://yourdomain.com/face/embed` in a desktop browser. Once the status turns green, pick a photo with one face in it. The page prints the dimension, detection confidence, value range, and a copy-pasteable embedding array you can drop straight into a Postman request.

---

### JavaScript bridge

**Wait for ready.** The page posts `{"event":"ready"}` once models finish loading (a few seconds on first run). `window.faceEmbedReady` is also set to `true`. Calling before then returns a `NOT_READY` error.

**Request a descriptor:**
```js
generateEmbedding("data:image/jpeg;base64,/9j/4AAQSkZJRg...")
```
A bare base64 string without the `data:` prefix is accepted too.

**Messages posted back** on channel `FaceChannel` (supports both `flutter_inappwebview` and `webview_flutter`):

*Success:*
```json
{
  "event": "result",
  "success": true,
  "embedding": [-0.1234, 0.0456, ...],
  "dimension": 128,
  "score": 0.997
}
```

*Failure:*
```json
{
  "event": "result",
  "success": false,
  "error": "NO_FACE",
  "message": "No face detected in the image."
}
```

| Error code | Meaning |
|-----------|---------|
| `NOT_READY` | Called before the `ready` event |
| `BAD_IMAGE` | No image data supplied |
| `NO_FACE` | No face found in the image |
| `INFERENCE_FAILED` | Model threw during inference |
| `MODEL_LOAD_FAILED` | CDN unreachable — check connectivity |

---

### Flutter wiring

```
Native camera captures a still
        ↓
base64 image passed into the hidden WebView
        ↓
generateEmbedding() runs face-api.js
        ↓
128 floats returned on FaceChannel
        ↓
POST /api/attendance/punch_for { "embedding": [...] }
```

Sketch using `webview_flutter`:

```dart
final controller = WebViewController()
  ..setJavaScriptMode(JavaScriptMode.unrestricted)
  ..addJavaScriptChannel('FaceChannel', onMessageReceived: (msg) {
      final res = jsonDecode(msg.message);
      if (res['event'] == 'ready') {
        setState(() => _ready = true);
        return;
      }
      if (res['success'] == true) {
        _punch(List<double>.from(res['embedding']));   // 128 floats
      } else {
        _showError(res['message']);
      }
  })
  ..loadRequest(Uri.parse('https://yourdomain.com/face/embed'));

// after capturing a photo
final b64 = base64Encode(await file.readAsBytes());
await controller.runJavaScript(
  'generateEmbedding("data:image/jpeg;base64,$b64")'
);
```

The WebView needs no visible size — keep it offscreen or 1×1. Load it once at app start so the models are warm before the first punch.

Drop `tflite_flutter` and the MobileFaceNet asset; they're no longer used. ML Kit is still fine for framing and liveness checks before capture, just don't send its landmarks as the embedding.

---

## Flutter/Mobile Integration Flow

```
┌─────────────────────────────────────────────────────┐
│                    APP STARTUP                        │
├─────────────────────────────────────────────────────┤
│ 1. Check stored JWT token                           │
│ 2. GET /api/auth/me → validate token                │
│    - If 401 → show login screen                     │
│    - If 200 → check face status                     │
│ 3. GET /api/face/status                             │
│    - If face_registered: false → show registration  │
│    - If face_registered: true → show dashboard      │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│                 FACE REGISTRATION                     │
├─────────────────────────────────────────────────────┤
│ 1. Open camera                                       │
│ 2. ML Kit: detect face + liveness (blink/turn)      │
│ 3. TFLite MobileFaceNet: generate 128-dim embedding │
│ 4. POST /api/face/register { embedding: [...] }     │
│ 5. Show success → navigate to dashboard             │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│               ATTENDANCE PUNCH                       │
├─────────────────────────────────────────────────────┤
│ 1. User taps "Punch In" or "Punch Out"             │
│ 2. Open camera                                       │
│ 3. ML Kit: face detection + liveness check          │
│ 4. TFLite: generate embedding                        │
│ 5. Get GPS coordinates                               │
│ 6. POST /api/attendance/punch {                     │
│      type: "in",                                     │
│      embedding: [...],                               │
│      latitude: 12.97,                                │
│      longitude: 77.59,                               │
│      device_id: "uuid"                               │
│    }                                                 │
│ 7. Server verifies face → records attendance        │
│ 8. Show result (success + working hours)            │
└─────────────────────────────────────────────────────┘
```

---

## Notes

- **Token expiry:** 7 days (604800 seconds). Refresh before expiry.
- **Face verification threshold:** 0.6 (60% cosine similarity). Adjustable per request.
- **Punch cooldown:** 1 minute between punches to prevent duplicates.
- **Embedding dimensions:** 128 (MobileFaceNet), 192, or 512 (FaceNet) accepted.
- **GPS is optional** but recommended for audit trail.
- **Working hours** are calculated from in/out pairs. If still punched in, time-until-now is included.

