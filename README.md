# Symfony Vue.js Job Listing App

This is a Symfony-based job listing and application system with a Vue.js frontend. The project integrates with the Recruitis API to fetch job listings and details, and allows users to apply for jobs.

## Features
- Job listing with pagination
- Job details view
- Job application submission
- Cached API responses for better performance
- Vue.js frontend with Bootstrap for styling

## Setup Instructions

### Prerequisites
Ensure you have the following installed:
- PHP 8.2+
- Composer
- Node.js (LTS version) & npm
- Symfony CLI (optional but recommended)

### Backend (Symfony)
1. Clone the repository:
   ```sh
   git clone <https://github.com/Stuler/symfony-vue-api>
   cd <project-folder>
   ```
2. Install dependencies:
   ```sh
   composer install
   ```
3. Set up environment variables:
   ```sh
   cp .env.example .env
   ```
    - Update `RECRUITIS_API_TOKEN` in the `.env` file


4. Start the Symfony server:
   ```sh
   symfony server:start
   ```

### Frontend (Vue.js)
1. Install dependencies:
   ```sh
   npm install
   ```
2. Start development server:
   ```sh
   npm run watch
   ```
3. To build for production:
   ```sh
   npm run build
   ```

## API Endpoints
- `GET /api/jobs?page={page}&limit={limit}` - Fetch paginated job listings
- `GET /api/jobs/{jobId}` - Fetch job details
- `POST /api/respond` - Submit job application

## Running Tests
To run PHPUnit tests, execute:
```sh
vendor/bin/phpunit tests/Service/JobServiceTest.php
```

## License
This project is licensed under the GNU GENERAL PUBLIC license.
