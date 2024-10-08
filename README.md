# Simple Short Link Generator

This is a simple URL shortener website built using PHP for managing short links. It allows you to create shortened URLs for easier sharing and tracking. The project also includes a future login system to manage sessions and automatic uploads using UploadCare.

## Features
- Create short URLs from long links
- Manage and track your short links
- Simple, user-friendly design
- Automatic file upload integration with UploadCare (upcoming)
- Login and logout functionality for session management (upcoming)

## Technologies Used
- **PHP**: Backend logic
- **Vue.js**: Frontend interactions
- **MySQL / MariaDB**: Database for storing URLs and short codes
- **TailwindCSS**: Styling and layout
- **jQuery**: Simplified DOM manipulation and event handling
- **ClipboardJS**: Copy short URLs to clipboard with ease
- **UploadCare**: Automatic file uploading service (upcoming feature)

## Prerequisites
Before running the project, ensure you have the following set up:
- PHP >= 7.4
- MySQL or MariaDB
- Web server (Apache, Nginx, etc.)

## Database Setup
To start, create the necessary database and table for storing short links. Use the following SQL command:

```sql
CREATE TABLE shortlinks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_url TEXT NOT NULL,
    short_code VARCHAR(10) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Once the table is created, configure the database connection in `config/config.php`.

## Project Setup
1. Clone this repository to your web server.
2. Create the database and table using the SQL command above.
3. Configure your database settings in the `config/config.php` file.
4. Point your web server's document root to the `homepage` folder for the main domain.
5. Configure your web server to use the `short-page` folder for handling shortened URLs.

## Customization
You are free to modify the pages to suit your needs. Both the homepage and short URL page designs are fully customizable.

## Future Updates
- **Login Page**: A login system to manage user sessions.
- **Logout Functionality**: Users can log out to destroy their session.
- **Improved Design**: Enhanced visual experience using TailwindCSS and Vue.js.
- **Automatic File Uploads**: Integrate UploadCare for automatic file uploads when creating short links.

## Contributing
Contributions are welcome! If you’d like to contribute, feel free to fork the project, make your changes, and submit a pull request. Please ensure that your code follows best practices and is well-documented.

## License
This project is open source under the [MIT License](https://opensource.org/licenses/MIT).

---

