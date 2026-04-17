# Eventura 🎓🚀

**Eventura** is a comprehensive, web-based event management system built specifically for colleges and academic institutions. It provides a seamless platform for administrators, teachers, and students to plan, manage, and participate in campus events securely and efficiently.

---

## ✨ Features

Eventura is packed with powerful features designed to simplify event coordination:

*   **👥 Role-Based Access Control:** Secure, customized dashboards for:
    *   **Admin:** Full system control, user management, and global reports.
    *   **Teacher:** Create, approve, and manage departmental/club events.
    *   **Student:** Browse events, register, and track participation history.
*   **📅 Comprehensive Event Management:** Easily create, modify, view, and organize events with detailed descriptions and schedules.
*   **🎟️ QR Code Integration & Ticketing:** System-generated QR tickets for registered events, along with a built-in QR scanner for rapid on-site check-ins and attendance tracking.
*   **🤖 AI Chatbot Assistance:** Built-in automated chat support to guide users and address common event-related queries instantly.
*   **📊 Reporting & Analytics:** Generate real-time reports on event registrations, attendance, and user statistics.
*   **🔐 Secure Authentication:** Password recovery (forgot/reset password functionalities), robust session management, and integrated profile completion workflows.

---

## 🛠️ Technology Stack

*   **Frontend:** HTML5, CSS (Vanilla/Frameworks), JavaScript
*   **Backend:** PHP (Core)
*   **Database:** MySQL
*   **Local Server Environment:** XAMPP / WAMP / LAMP

---

## 📂 Directory Structure

A brief overview of the core project structure:

```text
Eventura/
├── admin/                 # Administration dashboard and functionality
├── assets/                # Static assets (CSS, JS, Images)
├── auth/                  # Authentication related modules
├── chatbot/               # Chatbot logic and UI scripts
├── config/                # Environment and system configuration
├── database/              # SQL schema and database backups (schema.sql)
├── includes/              # Shared PHP components (headers, footers, etc.)
├── qr/                    # QR code generation and scanning libraries
├── reports/               # Report generation templates and logic
├── student/               # Student portal and event registration logic
├── teacher/               # Teacher portal for event coordination
└── uploads/               # User-uploaded files (profile pics, event posters)
```

---

## 🚀 Installation & Setup

Follow these steps to run Eventura locally on your machine:

1.  **Install Requirements:** Make sure you have [XAMPP](https://www.apachefriends.org/index.html) (or an equivalent server environment) installed.
2.  **Clone the Repository:**
    ```bash
    git clone https://github.com/yourusername/Eventura.git
    ```
    *(Alternatively, download the ZIP and extract it).*
3.  **Move the Project:** Place the `Eventura` folder inside your server's root directory:
    *   XAMPP: `C:\xampp\htdocs\Eventura`
    *   WAMP: `C:\wamp\www\Eventura`
4.  **Database Configuration:**
    *   Open your web browser and go to `http://localhost/phpmyadmin`.
    *   Create a new database (e.g., `eventura_db`).
    *   Import the SQL schema file located at `Eventura/database/schema.sql` into this new database.
5.  **Configure the Application:**
    *   Open the `config.php` file located in the root directory (or inside the `/config/` folder if it exists there).
    *   Update the database configuration constants (Host, DB Name, User, Password) to match your local setup.
    ```php
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'eventura_db');
    ```
6.  **Run the App:**
    *   Start Apache and MySQL modules from the XAMPP Control Panel.
    *   Navigate to `http://localhost/Eventura` in your browser.

---

## 👨‍💻 Contributing

If you wish to contribute to the Eventura project:
1. Fork the project.
2. Create your feature branch (`git checkout -b feature/AmazingFeature`).
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`).
4. Push to the branch (`git push origin feature/AmazingFeature`).
5. Open a Pull Request.

---

## 📄 License

This project is open-source and created for academic purposes. Feel free to use and modify it as per your requirements.
