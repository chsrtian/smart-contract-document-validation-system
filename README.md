# Local Civil Registry Office (LCRO) Document Management System

A comprehensive document management system built for the Land Certification and Registration Office (LCRO) with blockchain integration for secure document verification and tracking.

## Features

- **Document Management**: Upload, store, and manage various document types (MAGS, PAT, COR, and more)
- **Blockchain Integration**: Secure document verification using Ethereum blockchain with Ganache
- **Role-Based Access Control**: Different access levels for Admin, Staff, and Supervisor roles
- **Document Processing**: OCR capabilities with PaddleOCR for text extraction from scanned documents
- **Audit Trail**: Complete logging of all document actions and modifications
- **Correction Workflow**: Systematic process for document corrections and approvals
- **Dashboard Analytics**: Visual insights into document statistics and processing metrics
- **Secure Authentication**: Laravel-based authentication with secure session management
- **RESTful API**: Well-documented API endpoints for integration with other systems
- **Responsive Design**: Mobile-friendly interface built with Bootstrap and Tailwind CSS

## Tech Stack

### Backend
- **PHP 8.2+** with Laravel 10.x framework
- **MySQL** database for document storage and metadata
- **Redis** for caching and session handling
- **Blockchain**: Ethereum compatible network (Ganache) for document verification
- **Smart Contracts**: Custom Solidity contracts for document hash storage and verification

### Frontend
- **Blade Templating Engine** for server-side rendering
- **Bootstrap 5** for responsive UI components
- **Tailwind CSS** for utility-first styling
- **Alpine.js** for interactive components
- **Chart.js** for data visualization in dashboards

### Development Tools
- **Composer** for PHP dependency management
- **npm** and **Vite** for frontend asset compilation
- **PHPUnit** for testing
- **Laravel Sail** for Docker-based development environment
- **Postman** for API testing and documentation

## Installation Steps

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+ and npm
- MySQL 8.0+
- Docker (optional, for Laravel Sail)
- Ganache (for blockchain functionality)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd lcro-document-management
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Configure environment variables**
   - Edit `.env` file with your database credentials
   - Configure blockchain settings if needed
   - Adjust PaddleOCR service URL if running locally

7. **Run database migrations**
   ```bash
   php artisan migrate
   ```

8. **Compile assets**
   ```bash
   npm run dev
   # For production:
   # npm run build
   ```

9. **Start the development server**
   ```bash
   php artisan serve
   ```

10. **Start PaddleOCR service** (if using local OCR)
    ```bash
    # Refer to paddle_ocr_service directory for setup instructions
    ```

11. **Start Ganache** (for blockchain functionality)
    ```bash
    # Start Ganache CLI or GUI on port 7545
    ganache-cli --port 7545 --deterministic
    ```

## Project Status

- ✅ Core document management functionality
- ✅ User authentication and role-based access
- ✅ Blockchain integration for document verification
- ✅ OCR integration with PaddleOCR
- ✅ Admin panel for system management
- ✅ Document correction workflow
- ✅ Audit logging and tracking
- ⏳ Advanced analytics dashboard (in progress)
- ⏳ Mobile application development (planned)
- ⏳ Multi-language support (planned)

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please make sure to update tests as appropriate.

## Security

If you discover any security vulnerabilities, please contact the development team immediately through official channels. Do not disclose security issues publicly until they have been resolved.

## License

This project is proprietary software developed for the Land Certification and Registration Office. All rights reserved.

## Acknowledgments

- Laravel Team for the excellent PHP framework
- Ethereum and Ganache teams for blockchain technology
- PaddleOCR developers for open-source OCR capabilities
- Bootstrap and Tailwind CSS teams for frontend frameworks
- All contributors who have helped shape this system
