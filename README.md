**Tech Blog Pro - Content Management System**

<img width="2215" height="1222" alt="image" src="https://github.com/user-attachments/assets/46ea16e1-34f2-4dcc-9f71-b26f4cfa6098" />

A modern, responsive tech blog platform with complete content management system (CMS) for publishing technology articles, tutorials, and industry insights.

🚀 Features
Frontend
Responsive Design: Fully responsive layout for all devices

Blog Post Management: Create, edit, publish, and archive articles

Category System: Organize posts with hierarchical categories

Tag System: Add keywords for better content discoverability

Comment System: Readers can comment on posts (with moderation)

Search Functionality: Full-text search across all content

User Authentication: Secure admin login system

Analytics: Track post views and engagement metrics

Backend (Admin Panel)
Content Management: Full CRUD operations for blog posts

User Management: Role-based access control (Super Admin, Admin, Editor, Author)

Media Library: Upload and manage images/files

Comment Moderation: Approve, spam, or delete comments

Site Settings: Configure blog settings through admin panel

Activity Log: Track all administrative actions

SEO Management: Custom meta tags for each post
Homepage
<img width="1936" height="1202" alt="image" src="https://github.com/user-attachments/assets/3b7802cf-7787-4e3b-9de3-ee481e5a065f" />
<img width="3408" height="1221" alt="image" src="https://github.com/user-attachments/assets/3d8d2f24-679a-4048-8a27-990a0ff53491" />
Clean, modern design with featured articles

Statistics dashboard (articles, views, comments)

Newsletter subscription

Footer with quick links and contact info

Blog Listing
<img width="2215" height="1222" alt="image" src="https://github.com/user-attachments/assets/9fa79235-b293-4575-90c3-d71cc9e1de49" />


Grid/list view of all articles

Post metadata (author, date, views, comments)

Filter by category/tag

Search functionality

Admin Dashboard
<img width="1900" height="1188" alt="image" src="https://github.com/user-attachments/assets/5f3e727e-cc64-469a-be1c-4bb74faa1c11" />

Secure login interface
Demo credentials provided

Session timeout protection

Role-based access control

Comment Management
<img width="2021" height="1162" alt="image" src="https://github.com/user-attachments/assets/98dd8465-a0bf-4d3f-a136-79605ea6e975" />
Bulk actions for comment moderation

Filter by status (pending/approved/spam)

View comment details

Quick approve/delete actions

🛠️ Technology Stack
Backend
PHP 8+ - Server-side scripting

MySQL/MariaDB - Database management

Apache/Nginx - Web server

Frontend
HTML5 - Semantic markup

CSS3 - Styling with modern features

JavaScript - Interactive elements

Bootstrap 5 - Responsive framework (optional)

Security Features
Password hashing (MD5 in demo, upgrade to bcrypt for production)

SQL injection prevention

XSS protection

CSRF tokens

Session management

Input validation/sanitization

📁 Database Structure
The system uses a relational database with the following main tables:

admins - User accounts with roles

blog_posts - Blog articles content

categories - Post categorization

tags - Keywords for posts

comments - User comments on posts

media - Uploaded files/images

settings - Site configuration

activity_log - Administrative actions log

🔧 Installation
Prerequisites
PHP 7.4 or higher

MySQL 5.7+ or MariaDB 10.2+

Web server (Apache/Nginx)

Composer (for dependency management)

Setup Steps
Clone the repository

bash
[git clone https://github.com/yourusername/tech-blog-pro.git](https://github.com/Benson117/Tech-Blog-Web-App.git)
cd tech-blog-pro
Database Setup

Import the provided SQL dump file

Update database configuration in config/database.php

Configuration

bash
cp config.example.php config.php
Edit config.php with your database credentials and site settings.

File Permissions

bash
chmod 755 uploads/
chmod 644 config.php
Access the Application

Open your browser: http://localhost/tech-blog-pro

Admin panel: http://localhost/tech-blog-pro/admin

Demo Credentials
text
Username: admin      Password: admin123
Username: editor     Password: editor123  
Username: author     Password: author123
🧑‍💻 User Roles & Permissions
Role	Permissions
Super Admin	Full system access, user management, all settings
Admin	Content management, comment moderation, settings (limited)
Editor	Create/edit/publish posts, manage categories/tags
Author	Create/edit own posts (draft only), view analytics
📊 Features in Detail
Blog Post Features
Rich text editor for content creation

Featured image upload

SEO optimization (meta titles, descriptions, keywords)

Excerpt generation

Slug customization

Scheduled publishing

Draft/Published/Archived statuses

View count tracking

Comment System
Nested comments (reply functionality)

Email notifications

Spam detection

Manual moderation

User can edit/delete own comments

Comment sorting (newest/oldest)

Media Management
Image upload with resizing

File type restrictions

Alt text and captions

Organized media library

Bulk delete operations

SEO Features
Custom URL slugs

Meta tags per post

Sitemap generation

Open Graph tags

Twitter Cards support

Robots.txt configuration

🔒 Security Considerations
For production deployment, consider:

Upgrade Password Hashing

Replace MD5 with bcrypt or Argon2

Implement password strength requirements

HTTPS Enforcement

Force SSL for all pages

HSTS headers

Additional Security Measures

Rate limiting for login attempts

Two-factor authentication

Regular security updates

Input validation on all forms

Output escaping

Backup Strategy

Automated database backups

Off-site storage

Version control for content

🚀 Deployment
Shared Hosting
Upload files via FTP

Create MySQL database

Import SQL dump

Update configuration

Set file permissions

VPS/Dedicated Server
bash
# Example deployment script
git clone https://github.com/yourusername/tech-blog-pro.git /var/www/techblog
cd /var/www/techblog
mysql -u root -p < database.sql
# Configure virtual host
# Set up SSL certificate
Docker (Optional)
dockerfile
# Dockerfile example
FROM php:8.1-apache
COPY . /var/www/html/
RUN docker-php-ext-install mysqli pdo pdo_mysql
EXPOSE 80
📈 Performance Optimization
Database
Index optimization on frequently queried columns

Query caching

Regular table maintenance

Frontend
Image optimization

CSS/JS minification

Browser caching

CDN for static assets

Server
OPcache for PHP

Gzip compression

HTTP/2 support

🤝 Contributing
Fork the repository

Create a feature branch (git checkout -b feature/AmazingFeature)

Commit your changes (git commit -m 'Add some AmazingFeature')

Push to the branch (git push origin feature/AmazingFeature)

Open a Pull Request

Development Guidelines
Follow PSR coding standards

Write meaningful commit messages

Add tests for new features

Update documentation

📄 License
This project is licensed under the MIT License - see the LICENSE file for details.

🆘 Support
Documentation: docs.techblogpro.com

Issues: GitHub Issues

Email: bensonmunjanja@gmail.com

Community Forum: community.techblogpro.com

🙏 Acknowledgments
Bootstrap for responsive components

TinyMCE for rich text editing

Font Awesome for icons

All contributors who have helped improve this project
