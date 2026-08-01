# Ticket Management System (TicketMS)

A modern, full-stack **Role-Based Ticket Management System** built with **Laravel RESTful API** (PHP 8.3) and **Vue 3** (Vite, Pinia, Vue Router, and TailwindCSS).

---

## 🚀 Features & Functionalities

### 🔐 Authentication & Session Management
- **User Registration**: New users can register accounts (`User` role assigned by default).
- **Sanctum Authentication**: Secure bearer token-based API authentication for stateful and stateless requests.
- **Profile & Session Handling**: Real-time user session fetch (`/api/me`) and secure logout token revocation.

### 🛡️ Role-Based Access Control (RBAC)
The application enforces strict server-side and client-side authorization across 3 roles using `Spatie/laravel-permission`:

1. **Admin**:
   - Complete visibility over all support tickets in the system.
   - Assign tickets to specific **Staff** members (`PATCH /api/tickets/{ticket}/assign`).
   - Access **User & Role Management** (`GET /api/users`, `PATCH /api/users/{user}/role`).
   - Manage system roles (`GET /api/roles`).

2. **Staff**:
   - Scoped view to see tickets assigned to them.
   - Update ticket status (`Open`, `In Progress`, `Resolved`, `Closed`).
   - Post comments and communicate with ticket creators.

3. **User (Client)**:
   - Create new support tickets (`POST /api/tickets`) specifying subject, description, and priority (`Low`, `Medium`, `High`).
   - Track personal ticket history and view status updates.
   - Add comments/replies to active discussion threads.

### 📊 Dashboard & Frontend Features
- **Analytics Cards**: Real-time ticket counts categorized by status (*Open*, *In Progress*, *Resolved*, *Closed*).
- **Dynamic Role-Based Navigation**: Sidebar links dynamically adapt based on the logged-in user's role (e.g., hiding User Management from non-admins).
- **Route Guards**: Vue Router guards prevent unauthorized navigation.
- **Dark / Light Theme Toggle**: Built-in theme switcher.
- **API Documentation**: Integrated Swagger OpenAPI interface (`/api/documentation`).

---

## 🛠️ Technology Stack

- **Backend**: PHP 8.3, Laravel 11/12, Laravel Sanctum, Spatie Permission, MySQL / SQLite, Swagger (`l5-swagger`).
- **Frontend**: Vue 3 (Composition API), Vite, Pinia (State Management), Vue Router, TailwindCSS v4, Lucide Icons, Axios.
- **DevOps**: Docker, Docker Compose, Nginx, Hostinger / Cloudflare CDN.

---

## 📥 Installation & Setup Instructions

Choose either **Method 1 (Docker - Recommended)** or **Method 2 (Local / Manual)**.

---

### Method 1: Docker Setup (Recommended)

#### Prerequisites
- [Docker](https://www.docker.com/) and Docker Compose installed.

#### Steps

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/kiranaryal317/ticket.git assignment
   cd assignment
   ```

2. **Clone Frontend Repository into `frontend/`**:
   ```bash
   git clone -b kiran https://github.com/kiranaryal317/ticket-mgmt-frontend.git frontend
   ```

3. **Configure Environment Files**:
   - Backend `.env` is pre-configured for MySQL inside Docker.
   - Frontend `.env` (`frontend/.env`):
     ```ini
     VITE_API_BASE_URL=http://localhost:2000/api
     ```

4. **Build and Start Docker Containers**:
   ```bash
   docker compose up -d --build
   ```

5. **Run Database Migrations & Seeders**:
   ```bash
   docker exec ticket-app php artisan migrate:fresh --seed
   ```

6. **Access Services**:
   - 🎨 **Frontend App**: [http://localhost:3005](http://localhost:3005)
   - ⚡ **Backend API**: [http://localhost:2000/api](http://localhost:2000/api)
   - 📖 **Swagger API Docs**: [http://localhost:2000/api/documentation](http://localhost:2000/api/documentation)
   - 🗄️ **phpMyAdmin**: [http://localhost:2001](http://localhost:2001)

---

### Method 2: Local / Manual Setup (Without Docker)

#### Prerequisites
- PHP >= 8.3
- Composer
- Node.js >= 20 & npm
- MySQL Server

#### 1. Backend Setup (`src/`)

```bash
cd src

# Install PHP dependencies
composer install

# Copy environment file and set DB credentials
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your MySQL database settings in .env:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=ticket
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Run database migrations & seeders
php artisan migrate:fresh --seed

# Start Laravel development server
php artisan serve --port=8000
```
*Backend API will be running at `http://localhost:8000/api`.*

#### 2. Frontend Setup (`frontend/`)

```bash
cd ../frontend

# Install Node dependencies
npm install

# Create environment file
cp .env.example .env

# Ensure VITE_API_BASE_URL points to your backend:
# VITE_API_BASE_URL=http://localhost:8000/api

# Start Vite dev server
npm run dev
```
*Frontend application will be running at `http://localhost:5173` (or the URL printed by Vite).*

---

## 🔑 Test User Credentials

After running `php artisan migrate:fresh --seed`, the following default test accounts are available:

| Role | Email | Password | Permissions |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@example.com` | `password123` | Full access (User management, ticket assign, view all) |
| **Staff** | `staff@example.com` | `password123` | View assigned tickets, change ticket status, post comments |
| **User** | `user@example.com` | `password123` | Create support tickets, view own tickets, post comments |

---

## 🌐 Production Deployment (Hostinger / Cloudflare)

The production build is deployed at: **[https://network.kiran-aryal.com.np](https://network.kiran-aryal.com.np)**

- **Frontend Assets**: Built with `npm run build` and served from `src/public`.
- **Server Configuration**: `.htaccess` handles API routing to Laravel and SPA fallback to `index.html`.
- **SSL**: Automated via Cloudflare CDN.
