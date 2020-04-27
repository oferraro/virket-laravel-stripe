

- Create a Stripe account
- Verify the phone in Stripe account (via SMS, if not, Stripe is not gonna work)
- Copy .env file and configure
    Copy .env.example file to .env
- Install libraries
    composer install
- Add the .env with the STRIPE_API_KEY (using the private key)
- Run the server (with 0.0.0.0 to publish in every host, not only localhost)
    php artisan serve --host 0.0.0.0

- Create database file
touch database/database.sqlite


