## Installation

1. **Install PHP dependencies**

    ```bash
    composer install
    ```

2. **Install Node.js dependencies**

    ```bash
    npm install
    # or
    yarn install
    ```

3. **Copy and configure your environment file**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. **Set up your database**

    - Update `.env` with your database credentials.
    - Run migrations:
        ```bash
        php artisan migrate
        ```

5. **Build frontend assets**

    ```bash
    npm run build
    # or for development
    npm run dev
    ```
