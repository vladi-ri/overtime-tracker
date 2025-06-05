# overtime-tracker
overtime tracker for working hours

## Browser - How to use

### Run locally

Clone repository:
```
git clone https://github.com/vladi-ri/overtime-tracker.git
```

For a fresh start after cloning you need to initialize a new laravel project:

In the command line (in your project directory):

Install composer:
```
composer install
```

Copy .env file:
```
copy .env.example .env
```

Generate Laravel application key:
```
php artisan key:generate
```

Clear config cache:
```
php artisan config:clear
```

Migrate database with the existing migrations:
```
php artisan migrate
```

Start local environment:
```
php artisan serve
```

Open your browser and type in
```
localhost:8000/time-entry
```

For further information look at the official Laravel documentation.

### /time-entry

Here it is possible to create new time entries.

--

On top you can input your date, start and end time.

--

In the middle you see the entries you've already submitted.
You can filter by month and year.

--

At the bottom you see your tracked time for the current month.
Furthermore you see the total tracked time.

--

In the code there is a fixed total for working hours in a month.
If this is exceeded, the box appears to be red.
If it is still below the fixed total, the box appears to be green.

#### Currently tested & working on ...

- Google Chrome 137

## Upcoming Features

- Add Screenshots
- Export to PDF function
- Responsive Design for mobile
- Android version
