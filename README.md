Project Description



The Criminal Management System is a web-based platform designed for law enforcement agencies to efficiently manage and track criminal activities. It features a dual-interface system:

Public/Front-End Portal

A responsive landing page that provides an overview of the system, displays statistics (e.g., total criminals, active cases), and lists active officers.
Secure Dashboard: A restricted area for authenticated users (Officers/Investigators) to manage:
Criminal Records: database of criminals, including status (e.g., wanted) and biometrics.
Case Management: tracking investigations, evidence, and case status.
Officer Management: directory of police officers and their assignments.
Court Management: tracking court dates and outcomes.
Crime Statistics: visual analytics of crime trends and activities.
Technology Stack
The project uses a monolithic architecture with server-side rendering.

Backend Language


PHP
Uses native/vanilla PHP (no major framework like Laravel/Symfony is currently visible).
Utilizes PDO (PHP Data Objects) for secure database interactions.
Database: MySQL
The database name is criminal_management.
Contains tables for users (officers), criminals, cases, arrests, etc.


Frontend


HTML: Embedded directly within PHP files (Inline HTML).
CSS:
Inline CSS: Extensive styles defined within <style> blocks in PHP files.
Frameworks: None (Custom vanilla CSS).
Features: Includes CSS3 animations (keyframe animations for backgrounds), Flexbox/Grid layouts, and responsive media queries.
JavaScript:
Inline JavaScript: Scripts are written directly in <script> tags at the bottom of PHP files.
Libraries: None (Vanilla JS used for DOM manipulation, IntersectionObserver for scroll animations, and form validation).


External Assets:


Font Awesome (via CDN): Used for UI icons throughout the application.


Project Structure Snapshot


criminal-management-front.php
: The main public landing page.
dashboard.php
: The main control panel for logged-in users.
database/: Contains the MySQL dump files (
criminal_management.sql).
modules/: Likely contains specific feature logic (though I primarily analyzed the main entry points).
