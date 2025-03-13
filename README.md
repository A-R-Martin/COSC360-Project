# COSC360-Project

Group members: Alistair Martin, Zain Ali, Jaden Edgar.

This is our group project for COSC 360 - Web Programming. We will be building a online website.

## Database Setup

To set up the database for this project:

1. Start **XAMPP** and run **Apache** and **MySQL**.
2. Open **phpMyAdmin** (`http://localhost/phpmyadmin/`).
3. Click on the **SQL** tab.
4. Copy the contents of `virtual_library_schema.sql` and paste it into the SQL editor.
5. Click **Go** to execute the script.

Alternatively, use the command line:

```sh
mysql -u root -p virtual_library < virtual_library_schema.sql
