# WritersBlock

**WritersBlock** is an author progress tracking application designed to help writers track their word counts, manage books, and visualize their progress.

> **Note**: This is an archived project developed around 2020 using AngularJS (1.x). It is no longer under active development.

## Project Overview

- **Goal**: Track daily writing progress and visualize data.
- **Key Features**:
  - Book & Word Count Management.
  - Progress Visualization (Charts).
  - Offline support via `localStorage`.
  - Cloud Sync (PHP Backend).
  - Settings & User Management.

## Technology Stack

- **Frontend**: AngularJS 1.7.9
- **Build Tool**: Parcel Bundler
- **Styling**: Sass / SCSS
- **Backend**: PHP (located in `/api`)

## Development Notes

This project uses a split architecture:
- **Frontend**: Served via `npm run dev` (uses `parcel` and a simple Node static server).
- **Backend**: The `/api` endpoints (`sync.php`, etc.) require a PHP server. The included `server.js` **does not** execute PHP; it only serves static files. To fully run the app with sync features, you would need to host the `api` folder on a PHP-capable server (e.g., Apache/Nginx + PHP-FPM) or configure a proxy.

### Scripts

- `npm install`: Install dependencies.
- `npm run dev`: Start the development environment.
  - Watches Sass and JS changes.
  - Starts a static file server on port 8080.
- `npm run build`: Build the project (compiles Sass and JS to root).

### Directory Structure

- `src/`: Source code (Sass, Scripts).
- `api/`: PHP backend scripts for syncing data.
- `templates/`: AngularJS HTML templates.
- `data/`: Data storage (implied, or JSON files).
- `app.js` / `app.css`: Compiled assets (in root).

## License

ISC
