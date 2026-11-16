# Agriculture Information System (Agriculture_BSIS-3A)

AP3 Midterm Project, BSIS 3-A, Cebu Technological University.
A PHP 8 (PDO) web application on Supabase PostgreSQL for farmers, farms, crops, plantings and harvests,
with a dashboard, six reports, login with two roles (Administrator and Staff) and a Render deployment.

Live site: https://agriculture-bsis-3a.onrender.com/

## Project structure

```
public/        The only folder the web server shows: index.php (front controller) and style.css
config/        Database.php (connection and .env / environment variables) and routes.php (route table)
core/          Shared code: Controller and Model base classes, helper functions, chart drawing
models/        One class per table: all SQL queries and validation rules
controllers/   One class per module: reads the request, calls the model, chooses the view
views/         The HTML pages (layout, lists, forms, details, reports)
database/      agriculture_db.sql: creates the 14 tables and sample data
docs/          Documentation, ERD and rubric
```

### How a request flows

1. The browser opens `index.php?route=farmers`.
2. `public/index.php` looks up `farmers` in `config/routes.php` and finds `FarmerController::index`.
3. The controller reads the search, sort and page from the address and asks the `Farmer` model for the rows.
4. The controller sends the rows to `views/farmers/index.php`, which draws the page inside `views/layout/`.

Saving a form works the same way: the controller reads the form, the model validates it and saves it,
then the controller redirects to the list with a message.

## Run locally (XAMPP)

1. Create a Supabase project and run `database/agriculture_db.sql` in the SQL Editor.
2. Copy `.env.example` to `.env` in the project folder and fill in your Supabase details (Connect, Session pooler).
3. Put the project folder in `htdocs` and open `http://localhost/Agriculture_BSIS-3A/`.

## Deploy on Render

Docker web service, branch `main`, Free instance. Enter `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`,
`DB_PASSWORD`, `DB_SSLMODE` and `APP_DEBUG` under Environment. The `Dockerfile` serves the `public` folder.
Never commit the `.env` file.
